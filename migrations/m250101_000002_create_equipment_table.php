<?php
use yii\db\Migration;
class m250101_000002_create_equipment_table extends Migration
{
    public function safeUp() { $this->createTable('equipment', ['id' => $this->primaryKey(), 'category_id' => $this->integer()->notNull(), 'inventory_no' => $this->string(30)->notNull(), 'name' => $this->string(120)->notNull(), 'description' => $this->text(), 'status' => $this->smallInteger()->notNull()->defaultValue(0), 'purchased_at' => $this->date(), 'deposit' => $this->integer()->notNull()->defaultValue(0), 'created_at' => $this->dateTime()->notNull(), 'updated_at' => $this->dateTime()->notNull()]); $this->createIndex('uq_equipment_inventory_no', 'equipment', 'inventory_no', true); $this->createIndex('idx_equipment_category_status', 'equipment', ['category_id', 'status']); }
    public function safeDown() { $this->dropTable('equipment'); }
    public function up() { return $this->safeUp(); }
}