<?php
use yii\db\Migration;
class m250101_000001_create_category_table extends Migration
{
    public function safeUp() { $this->createTable('category', ['id' => $this->primaryKey(), 'name' => $this->string(80)->notNull(), 'slug' => $this->string(80)->notNull(), 'created_at' => $this->dateTime()->notNull()]); $this->createIndex('uq_category_slug', 'category', 'slug', true); }
    public function safeDown() { $this->dropTable('category'); }
    public function up() { return $this->safeUp(); }
}