<?php
use yii\db\Migration;
class m250101_000003_create_borrower_table extends Migration
{
    public function safeUp() { $this->createTable('borrower', ['id' => $this->primaryKey(), 'full_name' => $this->string(120)->notNull(), 'email' => $this->string(180)->notNull(), 'phone' => $this->string(40), 'is_active' => $this->boolean()->notNull()->defaultValue(true)]); $this->createIndex('uq_borrower_email', 'borrower', 'email', true); }
    public function safeDown() { $this->dropTable('borrower'); }
    public function up() { return $this->safeUp(); }
}