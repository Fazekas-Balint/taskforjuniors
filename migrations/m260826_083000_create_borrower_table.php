<?php

use yii\db\Migration;

class m260826_083000_create_borrower_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%borrower}}', true) !== null) {
            return;
        }

        $this->createTable('{{%borrower}}', [
            'id' => $this->primaryKey(),
            'full_name' => $this->string(255)->notNull(),
            'email' => $this->string(255)->notNull(),
            'phone' => $this->string(50),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
        ]);

        $this->createIndex(
            'uq-borrower-email',
            '{{%borrower}}',
            'email',
            true
        );
    }

    public function safeDown()
    {
        $this->dropIndex('uq-borrower-email', '{{%borrower}}');
        $this->dropTable('{{%borrower}}');
    }
}
