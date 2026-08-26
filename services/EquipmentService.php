<?php

namespace app\services;

use Yii;

class EquipmentService
{
    private $db;

    public function __construct()
    {
        $this->db = Yii::$app->db;
    }

    public function initialize()
    {
        // A séma a yii migrate migrációkból jön létre (MySQL), itt nincs teendő.
    }

    public function equipment($status = '', $lenderId = '', $categoryId = '')
    {
        $conditions = [];
        $params = [];
        if ($status === 'out') { $conditions[] = 'equipment.status = 1'; }
        if ($status === 'in') { $conditions[] = 'equipment.status = 0'; }
        if ($status === 'maintenance') { $conditions[] = 'equipment.status = 2'; }
        if (!empty($categoryId)) { $conditions[] = 'equipment.category_id = :category_id'; $params[':category_id'] = (int) $categoryId; }
        if (!empty($lenderId)) { $conditions[] = 'EXISTS (SELECT 1 FROM loan WHERE loan.equipment_id = equipment.id AND loan.returned_at IS NULL AND loan.borrower_id = :lender_id)'; $params[':lender_id'] = (int) $lenderId; }
        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        return $this->db->createCommand('SELECT equipment.*, category.name AS category_name FROM equipment JOIN category ON category.id = equipment.category_id' . $where . ' ORDER BY category.name, equipment.name', $params)->queryAll();
    }

    public function activeLoans()
    {
        return $this->db->createCommand('SELECT loan.*, equipment.name AS equipment_name, equipment.inventory_no, borrower.full_name, borrower.email FROM loan JOIN equipment ON equipment.id = loan.equipment_id JOIN borrower ON borrower.id = loan.borrower_id WHERE loan.returned_at IS NULL ORDER BY loan.due_at')->queryAll();
    }

    public function report()
    {
            return ['total' => (int) $this->db->createCommand('SELECT COUNT(*) FROM equipment WHERE status <> 3')->queryScalar(), 'available' => (int) $this->db->createCommand('SELECT COUNT(*) FROM equipment WHERE status = 0')->queryScalar(), 'lended' => (int) $this->db->createCommand('SELECT COUNT(*) FROM loan WHERE returned_at IS NULL')->queryScalar(), 'maintenance' => (int) $this->db->createCommand('SELECT COUNT(*) FROM equipment WHERE status = 2')->queryScalar(), 'overdue' => (int) $this->db->createCommand('SELECT COUNT(*) FROM loan WHERE returned_at IS NULL AND due_at < CURDATE()')->queryScalar(), 'dueToday' => (int) $this->db->createCommand('SELECT COUNT(*) FROM loan WHERE returned_at IS NULL AND due_at = CURDATE()')->queryScalar()];
    }

    public function lenders() { return $this->db->createCommand('SELECT id, full_name FROM borrower WHERE is_active = 1 ORDER BY full_name')->queryAll(); }
    public function categories() { return $this->db->createCommand('SELECT id, name FROM category ORDER BY name')->queryAll(); }
    public function recentMovements()
    {
        return $this->db->createCommand('SELECT loan.created_at, loan.loaned_at, loan.returned_at, equipment.name AS equipment_name, borrower.full_name FROM loan JOIN equipment ON equipment.id = loan.equipment_id JOIN borrower ON borrower.id = loan.borrower_id ORDER BY loan.created_at DESC LIMIT 5')->queryAll();
    }
    public function overdueLoans($filters = [])
    {
        $conditions = ['loan.returned_at IS NULL', 'loan.due_at < CURDATE()'];
        $params = [];
        if (!empty($filters['lender_id'])) { $conditions[] = 'loan.borrower_id = :lender_id'; $params[':lender_id'] = (int) $filters['lender_id']; }
        if (!empty($filters['category_id'])) { $conditions[] = 'equipment.category_id = :category_id'; $params[':category_id'] = (int) $filters['category_id']; }
        if (!empty($filters['from'])) { $conditions[] = 'loan.due_at >= :from_date'; $params[':from_date'] = $filters['from']; }
        if (!empty($filters['to'])) { $conditions[] = 'loan.due_at <= :to_date'; $params[':to_date'] = $filters['to']; }
        return $this->db->createCommand('SELECT loan.*, equipment.name AS equipment_name, equipment.inventory_no, equipment.deposit, category.name AS category_name, borrower.full_name, borrower.email, DATEDIFF(CURDATE(), loan.due_at) AS days_late, LEAST(DATEDIFF(CURDATE(), loan.due_at) * 500, equipment.deposit) AS late_fee FROM loan JOIN equipment ON equipment.id = loan.equipment_id JOIN category ON category.id = equipment.category_id JOIN borrower ON borrower.id = loan.borrower_id WHERE ' . implode(' AND ', $conditions) . ' ORDER BY loan.due_at', $params)->queryAll();
    }
    public function overdueFee($filters = [])
    {
        $rows = $this->overdueLoans($filters);
        return array_sum(array_column($rows, 'late_fee'));
    }

    public function handleAction($post)
    {
        $action = $post['action'] ?? '';
        $equipmentId = (int) ($post['equipment_id'] ?? 0);
        $equipment = $this->db->createCommand('SELECT * FROM equipment WHERE id = :id', [':id' => $equipmentId])->queryOne();
        if (!$equipment) return ['success' => false, 'message' => 'Az eszköz nem található.'];
        if ($action === 'return') {
            $this->db->transaction(function () use ($equipmentId) {
                $this->db->createCommand('UPDATE loan SET returned_at = CURDATE() WHERE equipment_id = :id AND returned_at IS NULL', [':id' => $equipmentId])->execute();
                $this->db->createCommand()->update('equipment', ['status' => 0], 'id = :id', [':id' => $equipmentId])->execute();
            });
            return ['success' => true, 'message' => 'Az eszköz visszavétele rögzítve.'];
        }
        if ($action === 'maintenance') {
            if ($this->isOut($equipmentId)) return ['success' => false, 'message' => 'Kölcsönzés alatt álló eszköz nem küldhető szervizbe.'];
            $this->db->createCommand()->update('equipment', ['status' => 2], 'id = :id', [':id' => $equipmentId])->execute();
            return ['success' => true, 'message' => 'Az eszköz szerviz státuszba került.'];
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

    private function isOut($equipmentId)
    {
        return (bool) $this->db->createCommand('SELECT 1 FROM loan WHERE equipment_id = :id AND returned_at IS NULL', [':id' => $equipmentId])->queryScalar();
    }
}
