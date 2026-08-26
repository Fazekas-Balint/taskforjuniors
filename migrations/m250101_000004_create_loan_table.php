<?php
use yii\db\Migration;
class m250101_000004_create_loan_table extends Migration
{
    public function safeUp() { $this->createTable('loan', ['id' => $this->primaryKey(), 'equipment_id' => $this->integer()->notNull(), 'borrower_id' => $this->integer()->notNull(), 'loaned_at' => $this->date()->notNull(), 'due_at' => $this->date()->notNull(), 'returned_at' => $this->date(), 'note' => $this->text(), 'created_at' => $this->dateTime()->notNull()]); $this->createIndex('idx_loan_equipment_returned', 'loan', ['equipment_id', 'returned_at']); }
    public function safeDown() { $this->dropTable('loan'); }
    public function up() { return $this->safeUp(); }
}