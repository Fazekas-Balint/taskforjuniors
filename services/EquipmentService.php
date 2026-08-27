<?php

namespace app\services;

use Yii;

class EquipmentService
{
    const LATE_FEE_PER_DAY = 500;

    private $db;

    public function __construct()
    {
        $this->db = Yii::$app->db;
    }

    // A korábbi initialize() (PRAGMA foreign_keys + sqlite séma-bootstrap) megszűnt:
    // a séma innentől a MySQL migrációkból jön (milan-deletion), lásd `php yii migrate`.

    public function equipment($status = '', $lenderId = '', $categoryId = '')
    {
        $conditions = [];
        $params = [];
        if ($status === 'out') { $conditions[] = 'equipment.status = 1'; }
        if ($status === 'in') { $conditions[] = 'equipment.status = 0'; }
        if ($status === 'maintenance') { $conditions[] = 'equipment.status = 2'; }
        if ($lenderId !== '') { $conditions[] = 'open_loan.borrower_id = :lender_id'; $params[':lender_id'] = (int) $lenderId; }
        if ($categoryId !== '') { $conditions[] = 'equipment.category_id = :category_id'; $params[':category_id'] = (int) $categoryId; }
        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        return $this->db->createCommand('SELECT equipment.*, category.name AS category_name, open_loan.id AS active_loan_id, open_loan.loaned_at AS active_loaned_at, open_lender.full_name AS active_lender_name FROM equipment JOIN category ON category.id = equipment.category_id LEFT JOIN loan AS open_loan ON open_loan.equipment_id = equipment.id AND open_loan.returned_at IS NULL LEFT JOIN borrower AS open_lender ON open_lender.id = open_loan.borrower_id' . $where . ' ORDER BY category.name, equipment.name', $params)->queryAll();
    }

    public function activeLoans()
    {
        return $this->db->createCommand('SELECT loan.*, equipment.name AS equipment_name, equipment.inventory_no, borrower.full_name, borrower.email FROM loan JOIN equipment ON equipment.id = loan.equipment_id JOIN borrower ON borrower.id = loan.borrower_id WHERE loan.returned_at IS NULL ORDER BY loan.due_at')->queryAll();
    }

    public function report()
    {
            // MySQL-en nincs sqlite-os DATE("now"), ezért az app időzónája szerinti mai dátumot kötjük be.
            $today = date('Y-m-d');
            return ['total' => (int) $this->db->createCommand('SELECT COUNT(*) FROM equipment WHERE status <> 3')->queryScalar(), 'available' => (int) $this->db->createCommand('SELECT COUNT(*) FROM equipment WHERE status = 0')->queryScalar(), 'lended' => (int) $this->db->createCommand('SELECT COUNT(*) FROM loan WHERE returned_at IS NULL')->queryScalar(), 'maintenance' => (int) $this->db->createCommand('SELECT COUNT(*) FROM equipment WHERE status = 2')->queryScalar(), 'overdue' => (int) $this->db->createCommand('SELECT COUNT(*) FROM loan WHERE returned_at IS NULL AND due_at < :today', [':today' => $today])->queryScalar(), 'dueToday' => (int) $this->db->createCommand('SELECT COUNT(*) FROM loan WHERE returned_at IS NULL AND due_at = :today', [':today' => $today])->queryScalar()];
    }

    public function lenders() { return $this->db->createCommand('SELECT id, full_name FROM borrower WHERE is_active = 1 ORDER BY full_name')->queryAll(); }
    public function categories() { return $this->db->createCommand('SELECT id, name FROM category ORDER BY name')->queryAll(); }
    public function categoriesWithUsage() { return $this->db->createCommand('SELECT category.id, category.name, COUNT(equipment.id) AS equipment_count FROM category LEFT JOIN equipment ON equipment.category_id = category.id GROUP BY category.id, category.name ORDER BY category.name')->queryAll(); }
    public function recentMovements()
    {
        return $this->db->createCommand('SELECT loan.created_at, loan.loaned_at, loan.returned_at, equipment.name AS equipment_name, borrower.full_name FROM loan JOIN equipment ON equipment.id = loan.equipment_id JOIN borrower ON borrower.id = loan.borrower_id ORDER BY loan.created_at DESC LIMIT 5')->queryAll();
    }
    public function overdueLoans($filters = [])
    {
        $today = date('Y-m-d');
        $conditions = ['loan.returned_at IS NULL', 'loan.due_at < :today'];
        $params = [':today' => $today];
        if (!empty($filters['lender_id'])) { $conditions[] = 'loan.borrower_id = :lender_id'; $params[':lender_id'] = (int) $filters['lender_id']; }
        if (!empty($filters['category_id'])) { $conditions[] = 'equipment.category_id = :category_id'; $params[':category_id'] = (int) $filters['category_id']; }
        if (!empty($filters['from'])) { $conditions[] = 'loan.due_at >= :from_date'; $params[':from_date'] = $filters['from']; }
        if (!empty($filters['to'])) { $conditions[] = 'loan.due_at <= :to_date'; $params[':to_date'] = $filters['to']; }
        $rows = $this->db->createCommand('SELECT loan.*, equipment.name AS equipment_name, equipment.inventory_no, equipment.deposit, category.name AS category_name, borrower.full_name, borrower.email FROM loan JOIN equipment ON equipment.id = loan.equipment_id JOIN category ON category.id = equipment.category_id JOIN borrower ON borrower.id = loan.borrower_id WHERE ' . implode(' AND ', $conditions) . ' ORDER BY loan.due_at', $params)->queryAll();
        foreach ($rows as $index => $row) {
            $daysLate = (int) round((strtotime($today) - strtotime($row['due_at'])) / 86400);
            $rows[$index]['days_late'] = $daysLate;
            $deposit = (int) $row['deposit'];
            // A letét csak akkor felső határ, ha van rögzítve; 0 letétnél a teljes napi díj jár.
            $rows[$index]['late_fee'] = $deposit > 0 ? min($daysLate * self::LATE_FEE_PER_DAY, $deposit) : $daysLate * self::LATE_FEE_PER_DAY;
        }
        return $rows;
    }

    public function overdueFee($filters = [])
    {
        $rows = $this->overdueLoans($filters);
        return array_sum(array_column($rows, 'late_fee'));
    }

    public function handleAction($post)
    {
        $action = $post['action'] ?? '';
        if ($action === 'create_equipment') return $this->createEquipment($post);
        if ($action === 'delete_equipment') return $this->deleteEquipment((int) ($post['equipment_id'] ?? 0));
        if ($action === 'delete_category') return $this->deleteCategory((int) ($post['category_id'] ?? 0));
        $equipmentId = (int) ($post['equipment_id'] ?? 0);
        $equipment = $this->db->createCommand('SELECT * FROM equipment WHERE id = :id', [':id' => $equipmentId])->queryOne();
        if (!$equipment) return ['success' => false, 'message' => 'Az eszköz nem található.'];
        if ($action === 'return') {
            $this->db->transaction(function () use ($equipmentId) {
                $this->db->createCommand('UPDATE loan SET returned_at = :today WHERE equipment_id = :id AND returned_at IS NULL', [':id' => $equipmentId, ':today' => date('Y-m-d')])->execute();
                $this->db->createCommand()->update('equipment', ['status' => 0], 'id = :id', [':id' => $equipmentId])->execute();
            });
            return ['success' => true, 'message' => 'Az eszköz visszavétele rögzítve.'];
        }
        if ($action === 'maintenance') {
            if ($this->isOut($equipmentId)) return ['success' => false, 'message' => 'Kölcsönzés alatt álló eszköz nem küldhető szervizbe.'];
            $this->db->createCommand()->update('equipment', ['status' => 2], 'id = :id', [':id' => $equipmentId])->execute();
            return ['success' => true, 'message' => 'Az eszköz szerviz státuszba került.'];
        }
        if ($action === 'maintenance_return') {
            if ((int) $equipment['status'] !== 2) return ['success' => false, 'message' => 'Az eszköz nincs szervizben.'];
            $this->db->createCommand()->update('equipment', ['status' => 0], 'id = :id', [':id' => $equipmentId])->execute();
            return ['success' => true, 'message' => 'Az eszköz visszakerült az elérhető eszközök közé.'];
        }
        if ($action === 'lend') {
            $lenderId = (int) ($post['lender_id'] ?? 0);
            $starts = $post['starts_on'] ?? '';
            $due = $post['due_on'] ?? '';
            if (!$lenderId || !$starts || !$due || $starts > $due) return ['success' => false, 'message' => 'Adj meg lendert és érvényes időszakot.'];
            $conflict = $this->db->createCommand('SELECT borrower.full_name, loan.loaned_at, loan.due_at FROM loan JOIN borrower ON borrower.id = loan.borrower_id WHERE loan.equipment_id = :id AND loan.returned_at IS NULL AND loan.loaned_at <= :due AND loan.due_at >= :starts', [':id' => $equipmentId, ':starts' => $starts, ':due' => $due])->queryOne();
            if ($conflict) return ['success' => false, 'message' => 'Ütközés: már ' . $conflict['full_name'] . ' használja ' . $conflict['loaned_at'] . ' és ' . $conflict['due_at'] . ' között.'];
            if ((int) $equipment['status'] !== 0) return ['success' => false, 'message' => 'Az eszköz jelenleg nem elérhető.'];
            $this->db->transaction(function () use ($equipmentId, $lenderId, $starts, $due) {
                $this->db->createCommand()->insert('loan', ['equipment_id' => $equipmentId, 'borrower_id' => $lenderId, 'loaned_at' => $starts, 'due_at' => $due, 'created_at' => date('Y-m-d H:i:s')])->execute();
                $this->db->createCommand()->update('equipment', ['status' => 1], 'id = :id', [':id' => $equipmentId])->execute();
            });
            return ['success' => true, 'message' => 'Kölcsönzés rögzítve.'];
        }
        return ['success' => false, 'message' => 'Ismeretlen művelet.'];
    }

    private function createEquipment($post)
    {
        $inventoryNo = trim((string) ($post['inventory_no'] ?? ''));
        $name = trim((string) ($post['equipment_name'] ?? ''));
        $categoryId = (int) ($post['category_id'] ?? 0);
        $deposit = max(0, (int) ($post['deposit'] ?? 0));
        if ($inventoryNo === '' || $name === '' || !$categoryId) return ['success' => false, 'message' => 'Add meg a leltári számot, az eszköz nevét és a kategóriát.'];
        if (!$this->db->createCommand('SELECT 1 FROM category WHERE id = :id', [':id' => $categoryId])->queryScalar()) return ['success' => false, 'message' => 'A kiválasztott kategória nem található.'];
        if ($this->db->createCommand('SELECT 1 FROM equipment WHERE inventory_no = :no', [':no' => $inventoryNo])->queryScalar()) return ['success' => false, 'message' => 'Ez a leltári szám már foglalt: ' . $inventoryNo];
        $now = date('Y-m-d H:i:s');
        $this->db->createCommand()->insert('equipment', ['category_id' => $categoryId, 'inventory_no' => $inventoryNo, 'name' => $name, 'status' => 0, 'deposit' => $deposit, 'created_at' => $now, 'updated_at' => $now])->execute();
        return ['success' => true, 'message' => 'Az eszköz felvéve a leltárba: ' . $inventoryNo . ' - ' . $name];
    }

    private function deleteEquipment($equipmentId)
    {
        $equipment = $this->db->createCommand('SELECT * FROM equipment WHERE id = :id', [':id' => $equipmentId])->queryOne();
        if (!$equipment) return ['success' => false, 'message' => 'Az eszköz nem található.'];
        if ($this->isOut($equipmentId)) return ['success' => false, 'message' => 'Kölcsönzés alatt álló eszköz nem törölhető.'];
        if ($this->db->createCommand('SELECT 1 FROM loan WHERE equipment_id = :id', [':id' => $equipmentId])->queryScalar()) {
            if ((int) $equipment['status'] === 3) return ['success' => false, 'message' => 'Az eszköz már selejt státuszban van.'];
            $this->db->createCommand()->update('equipment', ['status' => 3, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $equipmentId])->execute();
            return ['success' => true, 'message' => 'Az eszközt már kölcsönözték, ezért törlés helyett selejt státuszba került.'];
        }
        $this->db->createCommand()->delete('equipment', 'id = :id', [':id' => $equipmentId])->execute();
        return ['success' => true, 'message' => 'Az eszköz törölve.'];
    }

    private function deleteCategory($categoryId)
    {
        $category = $this->db->createCommand('SELECT * FROM category WHERE id = :id', [':id' => $categoryId])->queryOne();
        if (!$category) return ['success' => false, 'message' => 'A kategória nem található.'];
        if ($this->db->createCommand('SELECT 1 FROM equipment WHERE category_id = :id', [':id' => $categoryId])->queryScalar()) return ['success' => false, 'message' => 'A kategória nem törölhető, amíg eszköz tartozik hozzá.'];
        $this->db->createCommand()->delete('category', 'id = :id', [':id' => $categoryId])->execute();
        return ['success' => true, 'message' => 'A kategória törölve: ' . $category['name']];
    }

    private function isOut($equipmentId)
    {
        return (bool) $this->db->createCommand('SELECT 1 FROM loan WHERE equipment_id = :id AND returned_at IS NULL', [':id' => $equipmentId])->queryScalar();
    }
}
