<?php

use yii\db\Migration;

class m260826_084000_create_lending_tables extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%category}}', true) === null) {
            $this->createTable('{{%category}}', [
                'id' => $this->primaryKey(),
                'name' => $this->string(255)->notNull(),
                'slug' => $this->string(255)->notNull(),
                'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            ]);
            $this->createIndex('uq-category-slug', '{{%category}}', 'slug', true);
        }

        if ($this->db->schema->getTableSchema('{{%equipment}}', true) === null) {
            $this->createTable('{{%equipment}}', [
                'id' => $this->primaryKey(),
                'category_id' => $this->integer()->notNull(),
                'inventory_no' => $this->string(50)->notNull(),
                'name' => $this->string(255)->notNull(),
                'description' => $this->text(),
                'status' => $this->smallInteger()->notNull()->defaultValue(0),
                'purchased_at' => $this->date(),
                'deposit' => $this->integer()->notNull()->defaultValue(0),
                'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
                'updated_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            ]);
            $this->createIndex('uq-equipment-inventory-no', '{{%equipment}}', 'inventory_no', true);
            $this->createIndex('idx-equipment-category-status', '{{%equipment}}', ['category_id', 'status']);
            $this->addForeignKey('fk-equipment-category', '{{%equipment}}', 'category_id', '{{%category}}', 'id', 'RESTRICT', 'CASCADE');
        }

        if ($this->db->schema->getTableSchema('{{%loan}}', true) === null) {
            $this->createTable('{{%loan}}', [
                'id' => $this->primaryKey(),
                'equipment_id' => $this->integer()->notNull(),
                'borrower_id' => $this->integer()->notNull(),
                'loaned_at' => $this->date()->notNull(),
                'due_at' => $this->date()->notNull(),
                'returned_at' => $this->date(),
                'note' => $this->text(),
                'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            ]);
            $this->createIndex('idx-loan-equipment-returned', '{{%loan}}', ['equipment_id', 'returned_at']);
            $this->addForeignKey('fk-loan-equipment', '{{%loan}}', 'equipment_id', '{{%equipment}}', 'id', 'RESTRICT', 'CASCADE');
            $this->addForeignKey('fk-loan-borrower', '{{%loan}}', 'borrower_id', '{{%borrower}}', 'id', 'RESTRICT', 'CASCADE');
        }
    }

    public function safeDown()
    {
        $this->dropTable('{{%loan}}');
        $this->dropTable('{{%equipment}}');
        $this->dropTable('{{%category}}');
    }
}
