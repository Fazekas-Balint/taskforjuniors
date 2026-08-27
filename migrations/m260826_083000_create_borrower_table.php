<?php

use yii\db\Migration;

class m260826_083000_create_borrower_table extends Migration
{
    public function safeUp()
    {
        // Tábla-opciók a milan-deletion migrációk mintájára: a szerver alap motorja
        // MyISAM, ami nem tud idegen kulcsot és rövidebb indexet enged.
        $tableOptions = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        if ($this->db->schema->getTableSchema('{{%borrower}}', true) !== null) {
            return;
        }

        $this->createTable('{{%borrower}}', [
            'id' => $this->primaryKey(),
            'full_name' => $this->string(255)->notNull(),
            'email' => $this->string(255)->notNull(),
            'phone' => $this->string(50),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
        ], $tableOptions);

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
