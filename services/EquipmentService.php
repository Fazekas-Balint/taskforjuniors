<?php

namespace app\services;

use app\models\Equipment;
use app\models\Loan;
use Yii;
use yii\db\Connection;

/**
 * Read and write operations behind the dashboard, the overdue report and the
 * lending screens.
 *
 * NOTE: some business rules implemented here (BR-6 late fee, BR-8 deletion
 * protection) also exist in the ActiveRecord models. Which layer is
 * authoritative is still an open team decision.
 */
class EquipmentService
{
    /**
     * Late fee per day in HUF (BR-6).
     * Kept as an alias so there is a single source of truth.
     */
    public const LATE_FEE_PER_DAY = Loan::DAILY_LATE_FEE;

    /** Maps the filter values used by the UI to equipment status codes. */
    private const STATUS_FILTERS = [
        'out' => Equipment::STATUS_LOANED,
        'in' => Equipment::STATUS_AVAILABLE,
        'maintenance' => Equipment::STATUS_MAINTENANCE,
    ];

    private Connection $db;

    public function __construct()
    {
        $this->db = Yii::$app->db;
    }

    /**
     * Creates the schema on first run if it is missing.
     *
     * TODO: running migrations from a web request is unsafe - it writes no
     * migration history and cannot be rolled back. This belongs to
     * `php yii migrate` and should be removed once the team agrees.
     */
    public function initialize(): void
    {
        if ($this->db->getSchema()->getTableSchema('category', true) !== null) {
            return;
        }

        $migrations = [
            'm250101_000001_create_category_table',
            'm250101_000002_create_equipment_table',
            'm250101_000003_create_borrower_table',
            'm250101_000004_create_loan_table',
            'm250101_000005_seed_demo_data',
        ];

        foreach ($migrations as $migration) {
            (new $migration())->up();
        }
    }

    /**
     * Equipment rows with their category and, when out on loan, the open loan
     * and its borrower.
     *
     * @param string $status One of the STATUS_FILTERS keys, or '' for all.
     * @param int|string $lenderId Borrower id, or '' for all.
     * @param int|string $categoryId Category id, or '' for all.
     * @return array
     */
    public function equipment(string $status = '', $lenderId = '', $categoryId = ''): array
    {
        $conditions = [];
        $params = [];

        if (isset(self::STATUS_FILTERS[$status])) {
            $conditions[] = 'equipment.status = :status';
            $params[':status'] = self::STATUS_FILTERS[$status];
        }
        if ($lenderId !== '') {
            $conditions[] = 'open_loan.borrower_id = :lender_id';
            $params[':lender_id'] = (int) $lenderId;
        }
        if ($categoryId !== '') {
            $conditions[] = 'equipment.category_id = :category_id';
            $params[':category_id'] = (int) $categoryId;
        }

        $sql = <<<SQL
            SELECT
                equipment.*,
                category.name         AS category_name,
                open_loan.id          AS active_loan_id,
                open_loan.loaned_at   AS active_loaned_at,
                open_lender.full_name AS active_lender_name
            FROM equipment
            JOIN category
                ON category.id = equipment.category_id
            LEFT JOIN loan AS open_loan
                ON open_loan.equipment_id = equipment.id
               AND open_loan.returned_at IS NULL
            LEFT JOIN borrower AS open_lender
                ON open_lender.id = open_loan.borrower_id
            SQL;

        if ($conditions !== []) {
            $sql .= "\nWHERE " . implode("\n  AND ", $conditions);
        }

        $sql .= "\nORDER BY category.name, equipment.name";

        return $this->db->createCommand($sql, $params)->queryAll();
    }

    /**
     * Every loan that has not been returned yet, earliest due date first.
     */
    public function activeLoans(): array
    {
        $sql = <<<SQL
            SELECT
                loan.*,
                equipment.name         AS equipment_name,
                equipment.inventory_no,
                borrower.full_name,
                borrower.email
            FROM loan
            JOIN equipment ON equipment.id = loan.equipment_id
            JOIN borrower  ON borrower.id  = loan.borrower_id
            WHERE loan.returned_at IS NULL
            ORDER BY loan.due_at
            SQL;

        return $this->db->createCommand($sql)->queryAll();
    }

    /**
     * The six figures shown on the dashboard.
     *
     * @return array<string, int>
     */
    public function report(): array
    {
        return [
            'total' => $this->countRows(
                'SELECT COUNT(*) FROM equipment WHERE status <> :status',
                [':status' => Equipment::STATUS_SCRAPPED]
            ),
            'available' => $this->countRows(
                'SELECT COUNT(*) FROM equipment WHERE status = :status',
                [':status' => Equipment::STATUS_AVAILABLE]
            ),
            'lended' => $this->countRows(
                'SELECT COUNT(*) FROM loan WHERE returned_at IS NULL'
            ),
            'maintenance' => $this->countRows(
                'SELECT COUNT(*) FROM equipment WHERE status = :status',
                [':status' => Equipment::STATUS_MAINTENANCE]
            ),
            'overdue' => $this->countRows(
                'SELECT COUNT(*) FROM loan WHERE returned_at IS NULL AND due_at < CURDATE()'
            ),
            'dueToday' => $this->countRows(
                'SELECT COUNT(*) FROM loan WHERE returned_at IS NULL AND due_at = CURDATE()'
            ),
        ];
    }

    /**
     * Active borrowers, for the lending form's select.
     */
    public function lenders(): array
    {
        $sql = <<<SQL
            SELECT id, full_name
            FROM borrower
            WHERE is_active = 1
            ORDER BY full_name
            SQL;

        return $this->db->createCommand($sql)->queryAll();
    }

    /**
     * All categories, for the filter selects.
     */
    public function categories(): array
    {
        $sql = <<<SQL
            SELECT id, name
            FROM category
            ORDER BY name
            SQL;

        return $this->db->createCommand($sql)->queryAll();
    }

    /**
     * Categories with the number of items attached to each (BR-8 hint: a
     * category with a non-zero count cannot be deleted).
     */
    public function categoriesWithUsage(): array
    {
        $sql = <<<SQL
            SELECT
                category.id,
                category.name,
                COUNT(equipment.id) AS equipment_count
            FROM category
            LEFT JOIN equipment ON equipment.category_id = category.id
            GROUP BY category.id, category.name
            ORDER BY category.name
            SQL;

        return $this->db->createCommand($sql)->queryAll();
    }

    /**
     * The five most recent loan events, for the dashboard.
     */
    public function recentMovements(): array
    {
        $sql = <<<SQL
            SELECT
                loan.created_at,
                loan.loaned_at,
                loan.returned_at,
                equipment.name AS equipment_name,
                borrower.full_name
            FROM loan
            JOIN equipment ON equipment.id = loan.equipment_id
            JOIN borrower  ON borrower.id  = loan.borrower_id
            ORDER BY loan.created_at DESC
            LIMIT 5
            SQL;

        return $this->db->createCommand($sql)->queryAll();
    }

    /**
     * Open loans past their due date, with days late and the late fee (BR-6).
     *
     * The day count and the fee are computed in PHP rather than SQL so the
     * result does not depend on the database's date functions.
     *
     * @param array $filters lender_id, category_id, from, to - all optional.
     * @return array
     */
    public function overdueLoans(array $filters = []): array
    {
        $today = date('Y-m-d');

        $conditions = [
            'loan.returned_at IS NULL',
            'loan.due_at < :today',
        ];
        $params = [':today' => $today];

        if (!empty($filters['lender_id'])) {
            $conditions[] = 'loan.borrower_id = :lender_id';
            $params[':lender_id'] = (int) $filters['lender_id'];
        }
        if (!empty($filters['category_id'])) {
            $conditions[] = 'equipment.category_id = :category_id';
            $params[':category_id'] = (int) $filters['category_id'];
        }
        if (!empty($filters['from'])) {
            $conditions[] = 'loan.due_at >= :from_date';
            $params[':from_date'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $conditions[] = 'loan.due_at <= :to_date';
            $params[':to_date'] = $filters['to'];
        }

        $sql = <<<SQL
            SELECT
                loan.*,
                equipment.name AS equipment_name,
                equipment.inventory_no,
                equipment.deposit,
                category.name  AS category_name,
                borrower.full_name,
                borrower.email
            FROM loan
            JOIN equipment ON equipment.id = loan.equipment_id
            JOIN category  ON category.id  = equipment.category_id
            JOIN borrower  ON borrower.id  = loan.borrower_id
            SQL;

        $sql .= "\nWHERE " . implode("\n  AND ", $conditions);
        $sql .= "\nORDER BY loan.due_at";

        $rows = $this->db->createCommand($sql, $params)->queryAll();

        foreach ($rows as $index => $row) {
            $daysLate = (int) round(
                (strtotime($today) - strtotime($row['due_at'])) / 86400
            );

            $rows[$index]['days_late'] = $daysLate;

            // BR-6: days x daily fee, capped at the item's deposit.
            $rows[$index]['late_fee'] = min(
                $daysLate * self::LATE_FEE_PER_DAY,
                (int) $row['deposit']
            );
        }

        return $rows;
    }

    /**
     * Total late fee for the rows matching the same filters.
     */
    public function overdueFee(array $filters = []): int
    {
        $rows = $this->overdueLoans($filters);

        return (int) array_sum(array_column($rows, 'late_fee'));
    }

    /**
     * Entry point for the lending screen's POST actions.
     *
     * @param array $post The raw request body.
     * @return array{success: bool, message: string}
     */
    public function handleAction(array $post): array
    {
        $action = (string) ($post['action'] ?? '');

        // These do not operate on an existing equipment row.
        switch ($action) {
            case 'create_equipment':
                return $this->createEquipment($post);
            case 'delete_equipment':
                return $this->deleteEquipment((int) ($post['equipment_id'] ?? 0));
            case 'delete_category':
                return $this->deleteCategory((int) ($post['category_id'] ?? 0));
        }

        $equipmentId = (int) ($post['equipment_id'] ?? 0);
        $equipment = $this->findEquipment($equipmentId);

        if ($equipment === null) {
            return $this->failure('Az eszköz nem található.');
        }

        switch ($action) {
            case 'return':
                return $this->returnEquipment($equipmentId);
            case 'maintenance':
                return $this->sendToMaintenance($equipmentId);
            case 'maintenance_return':
                return $this->returnFromMaintenance($equipmentId, $equipment);
            case 'lend':
                return $this->lend($equipmentId, $equipment, $post);
        }

        return $this->failure('Ismeretlen művelet.');
    }

    /**
     * Closes the open loan and makes the item available again (BR-5).
     */
    private function returnEquipment(int $equipmentId): array
    {
        $this->db->transaction(function () use ($equipmentId) {
            $this->db->createCommand(
                'UPDATE loan SET returned_at = CURDATE()'
                . ' WHERE equipment_id = :id AND returned_at IS NULL',
                [':id' => $equipmentId]
            )->execute();

            $this->updateStatus($equipmentId, Equipment::STATUS_AVAILABLE);
        });

        return $this->success('Az eszköz visszavétele rögzítve.');
    }

    private function sendToMaintenance(int $equipmentId): array
    {
        if ($this->isOut($equipmentId)) {
            return $this->failure('Kölcsönzés alatt álló eszköz nem küldhető szervizbe.');
        }

        $this->updateStatus($equipmentId, Equipment::STATUS_MAINTENANCE);

        return $this->success('Az eszköz szerviz státuszba került.');
    }

    private function returnFromMaintenance(int $equipmentId, array $equipment): array
    {
        if ((int) $equipment['status'] !== Equipment::STATUS_MAINTENANCE) {
            return $this->failure('Az eszköz nincs szervizben.');
        }

        $this->updateStatus($equipmentId, Equipment::STATUS_AVAILABLE);

        return $this->success('Az eszköz visszakerült az elérhető eszközök közé.');
    }

    /**
     * Issues the item to a borrower for a date range (BR-1, BR-2).
     */
    private function lend(int $equipmentId, array $equipment, array $post): array
    {
        $lenderId = (int) ($post['lender_id'] ?? 0);
        $starts = (string) ($post['starts_on'] ?? '');
        $due = (string) ($post['due_on'] ?? '');

        if ($lenderId === 0 || $starts === '' || $due === '' || $starts > $due) {
            return $this->failure('Adj meg kölcsönzőt és érvényes időszakot.');
        }

        $conflict = $this->findOverlappingLoan($equipmentId, $starts, $due);

        if ($conflict !== null) {
            return $this->failure(sprintf(
                'Ütközés: már %s használja %s és %s között.',
                $conflict['full_name'],
                $conflict['loaned_at'],
                $conflict['due_at']
            ));
        }

        if ((int) $equipment['status'] !== Equipment::STATUS_AVAILABLE) {
            return $this->failure('Az eszköz jelenleg nem elérhető.');
        }

        $this->db->transaction(function () use ($equipmentId, $lenderId, $starts, $due) {
            $this->db->createCommand()->insert('loan', [
                'equipment_id' => $equipmentId,
                'borrower_id' => $lenderId,
                'loaned_at' => $starts,
                'due_at' => $due,
                'created_at' => date('Y-m-d H:i:s'),
            ])->execute();

            $this->updateStatus($equipmentId, Equipment::STATUS_LOANED);
        });

        return $this->success('Kölcsönzés rögzítve.');
    }

    /**
     * An open loan overlapping the requested range, or null.
     */
    private function findOverlappingLoan(int $equipmentId, string $starts, string $due): ?array
    {
        $sql = <<<SQL
            SELECT borrower.full_name, loan.loaned_at, loan.due_at
            FROM loan
            JOIN borrower ON borrower.id = loan.borrower_id
            WHERE loan.equipment_id = :id
              AND loan.returned_at IS NULL
              AND loan.loaned_at <= :due
              AND loan.due_at    >= :starts
            SQL;

        $row = $this->db->createCommand($sql, [
            ':id' => $equipmentId,
            ':starts' => $starts,
            ':due' => $due,
        ])->queryOne();

        return $row === false ? null : $row;
    }

    private function createEquipment(array $post): array
    {
        $inventoryNo = trim((string) ($post['inventory_no'] ?? ''));
        $name = trim((string) ($post['equipment_name'] ?? ''));
        $categoryId = (int) ($post['category_id'] ?? 0);
        $deposit = max(0, (int) ($post['deposit'] ?? 0));

        if ($inventoryNo === '' || $name === '' || $categoryId === 0) {
            return $this->failure('Add meg a leltári számot, az eszköz nevét és a kategóriát.');
        }

        $categoryExists = $this->db->createCommand(
            'SELECT 1 FROM category WHERE id = :id',
            [':id' => $categoryId]
        )->queryScalar();

        if (!$categoryExists) {
            return $this->failure('A kiválasztott kategória nem található.');
        }

        $inventoryNoTaken = $this->db->createCommand(
            'SELECT 1 FROM equipment WHERE inventory_no = :no',
            [':no' => $inventoryNo]
        )->queryScalar();

        if ($inventoryNoTaken) {
            return $this->failure('Ez a leltári szám már foglalt: ' . $inventoryNo);
        }

        $now = date('Y-m-d H:i:s');

        $this->db->createCommand()->insert('equipment', [
            'category_id' => $categoryId,
            'inventory_no' => $inventoryNo,
            'name' => $name,
            'status' => Equipment::STATUS_AVAILABLE,
            'deposit' => $deposit,
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();

        return $this->success(
            'Az eszköz felvéve a leltárba: ' . $inventoryNo . ' - ' . $name
        );
    }

    /**
     * Deletes the item, or scraps it if it has ever been loaned (BR-8).
     */
    private function deleteEquipment(int $equipmentId): array
    {
        $equipment = $this->findEquipment($equipmentId);

        if ($equipment === null) {
            return $this->failure('Az eszköz nem található.');
        }

        if ($this->isOut($equipmentId)) {
            return $this->failure('Kölcsönzés alatt álló eszköz nem törölhető.');
        }

        if ($this->hasLoanHistory($equipmentId)) {
            if ((int) $equipment['status'] === Equipment::STATUS_SCRAPPED) {
                return $this->failure('Az eszköz már selejt státuszban van.');
            }

            $this->updateStatus($equipmentId, Equipment::STATUS_SCRAPPED);

            return $this->success(
                'Az eszközt már kölcsönözték, ezért törlés helyett selejt státuszba került.'
            );
        }

        $this->db->createCommand()
            ->delete('equipment', 'id = :id', [':id' => $equipmentId])
            ->execute();

        return $this->success('Az eszköz törölve.');
    }

    /**
     * Deletes the category unless equipment is still attached to it (BR-8).
     */
    private function deleteCategory(int $categoryId): array
    {
        $category = $this->db->createCommand(
            'SELECT * FROM category WHERE id = :id',
            [':id' => $categoryId]
        )->queryOne();

        if ($category === false) {
            return $this->failure('A kategória nem található.');
        }

        $hasEquipment = $this->db->createCommand(
            'SELECT 1 FROM equipment WHERE category_id = :id',
            [':id' => $categoryId]
        )->queryScalar();

        if ($hasEquipment) {
            return $this->failure('A kategória nem törölhető, amíg eszköz tartozik hozzá.');
        }

        $this->db->createCommand()
            ->delete('category', 'id = :id', [':id' => $categoryId])
            ->execute();

        return $this->success('A kategória törölve: ' . $category['name']);
    }

    private function findEquipment(int $equipmentId): ?array
    {
        $row = $this->db->createCommand(
            'SELECT * FROM equipment WHERE id = :id',
            [':id' => $equipmentId]
        )->queryOne();

        return $row === false ? null : $row;
    }

    /**
     * Whether the item is currently out on an open loan.
     */
    private function isOut(int $equipmentId): bool
    {
        return (bool) $this->db->createCommand(
            'SELECT 1 FROM loan WHERE equipment_id = :id AND returned_at IS NULL',
            [':id' => $equipmentId]
        )->queryScalar();
    }

    /**
     * Whether the item has ever been loaned, open or closed.
     */
    private function hasLoanHistory(int $equipmentId): bool
    {
        return (bool) $this->db->createCommand(
            'SELECT 1 FROM loan WHERE equipment_id = :id',
            [':id' => $equipmentId]
        )->queryScalar();
    }

    private function updateStatus(int $equipmentId, int $status): void
    {
        $this->db->createCommand()->update(
            'equipment',
            ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')],
            'id = :id',
            [':id' => $equipmentId]
        )->execute();
    }

    private function countRows(string $sql, array $params = []): int
    {
        return (int) $this->db->createCommand($sql, $params)->queryScalar();
    }

    /**
     * @return array{success: true, message: string}
     */
    private function success(string $message): array
    {
        return ['success' => true, 'message' => $message];
    }

    /**
     * @return array{success: false, message: string}
     */
    private function failure(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }
}
