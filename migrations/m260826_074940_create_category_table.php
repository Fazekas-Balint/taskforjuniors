<?php

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
        ]);

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
