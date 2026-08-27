<?php

/**
 * Forrás: milan-deletion branch - migrations/m260826_074940_create_category_table.php
 * Változatlanul átvéve: ez adja a MySQL/InnoDB sémát a törölt sqlite-os
 * m250101_* create-table migrációk helyett.
 */

use yii\db\Migration;

/**
 * Handles the creation of table `{{%category}}`.
 */
class m260826_074940_create_category_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%category}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull(),
            'slug' => $this->string(120)->notNull(),
            'created_at' => $this->dateTime()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex(
            'idx-category-slug',
            '{{%category}}',
            'slug',
            true            // UNIQUE
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-category-slug', '{{%category}}');
        $this->dropTable('{{%category}}');
    }
}
