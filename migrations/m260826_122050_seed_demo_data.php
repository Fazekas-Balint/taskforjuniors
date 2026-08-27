<?php

use yii\db\Migration;

class m260826_122050_seed_demo_data extends Migration
{
public function safeUp() {
    // A demó adatokat az m260826_090000_seed_demo_data is felviszi. Ha már bent vannak,
    // a duplikált beszúrás az egyedi indexeken (slug, inventory_no, email) elhasalna.
    if ((int) $this->getDb()->createCommand('SELECT COUNT(*) FROM {{%category}}')->queryScalar() > 0) {
        echo "    > a demó adatok már bent vannak, kihagyva\n";
        return;
    }
    $now = date('Y-m-d H:i:s'); foreach ([['Laptopok', 'laptopok'], ['Projektorok', 'projektorok'], ['Kamerák', 'kamerak'], ['Szerszámok', 'szerszamok']] as $row) $this->insert('category', ['name' => $row[0], 'slug' => $row[1], 'created_at' => $now]); $categories = $this->getDb()->createCommand('SELECT id, slug FROM category')->queryAll(); $cat = []; foreach ($categories as $row) $cat[$row['slug']] = $row['id']; foreach ([['LP-0001', 'ThinkPad T14 Gen 3', 'laptopok'], ['LP-0002', 'MacBook Pro 14', 'laptopok'], ['LP-0003', 'Dell Latitude 5440', 'laptopok'], ['LP-0004', 'ThinkPad X1 Carbon', 'laptopok'], ['PR-0001', '3-as projektor', 'projektorok'], ['PR-0002', 'Epson EB-FH52', 'projektorok'], ['KA-0001', 'Sony A7 IV kamera', 'kamerak'], ['KA-0002', 'Canon EOS R6', 'kamerak'], ['SZ-0001', 'Milwaukee M18 fúró', 'szerszamok'], ['SZ-0002', 'Bosch GKS 18V fűrész', 'szerszamok'], ['SZ-0003', 'Makita csiszoló', 'szerszamok'], ['SZ-0004', 'Fluke multiméter', 'szerszamok']] as $item) $this->insert('equipment', ['category_id' => $cat[$item[2]], 'inventory_no' => $item[0], 'name' => $item[1], 'status' => 0, 'deposit' => 0, 'created_at' => $now, 'updated_at' => $now]); foreach ([['Nagy Anna', 'anna@example.test'], ['Kiss Béla', 'bela@example.test'], ['Tóth Csilla', 'csilla@example.test'], ['Varga Dávid', 'david@example.test'], ['Szabó Éva', 'eva@example.test']] as $row) $this->insert('borrower', ['full_name' => $row[0], 'email' => $row[1]]); $borrowers = $this->getDb()->createCommand('SELECT id, full_name FROM borrower')->queryAll(); $people = []; foreach ($borrowers as $row) $people[$row['full_name']] = $row['id']; $equipment = $this->getDb()->createCommand('SELECT id, inventory_no FROM equipment')->queryAll(); $items = []; foreach ($equipment as $row) $items[$row['inventory_no']] = $row['id']; foreach ([['PR-0001', 'Nagy Anna', -12, -5], ['LP-0001', 'Kiss Béla', -8, -2], ['KA-0001', 'Tóth Csilla', -2, 5], ['SZ-0001', 'Varga Dávid', -1, 2], ['LP-0002', 'Szabó Éva', 1, 8], ['PR-0002', 'Nagy Anna', 2, 4]] as $loan) { $this->insert('loan', ['equipment_id' => $items[$loan[0]], 'borrower_id' => $people[$loan[1]], 'loaned_at' => date('Y-m-d', strtotime($loan[2] . ' days')), 'due_at' => date('Y-m-d', strtotime($loan[3] . ' days')), 'created_at' => $now]); $this->update('equipment', ['status' => 1, 'updated_at' => $now], ['id' => $items[$loan[0]]]); } }
    public function safeDown() {
    $this->delete('loan'); $this->delete('borrower'); $this->delete('equipment'); $this->delete('category'); }
    public function up() {
    return $this->safeUp();
}
}
