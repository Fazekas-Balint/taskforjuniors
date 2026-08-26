<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%equipment}}`.
 */
class m260826_075513_create_equipment_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%equipment}}', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer()->notNull(),
            'inventory_no' => $this->string(20)->notNull(),
            'name' => $this->string(150)->notNull(),
            'description' => $this->text()->null(),
            'status' => $this->smallInteger()->notNull()->defaultValue(0),
            'purchased_at' => $this->date()->null(),
            'deposit' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->null(),
        ]);

        // egyedi leltári szám
        $this->createIndex(
            'idx-equipment-inventory_no',
            '{{%equipment}}',
            'inventory_no',
            true            // UNIQUE
        );

        // összetett index a katalógus-szűréshez (kategória + státusz)
        $this->createIndex(
            'idx-equipment-category_id-status',
            '{{%equipment}}',
            ['category_id', 'status']
        );

        // idegen kulcs a kategóriára, RESTRICT
        $this->addForeignKey(
            'fk-equipment-category_id',
            '{{%equipment}}',
            'category_id',
            '{{%category}}',
            'id',
            'RESTRICT',     // ON DELETE
            'CASCADE'       // ON UPDATE
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-equipment-category_id', '{{%equipment}}');
        $this->dropIndex('idx-equipment-category_id-status', '{{%equipment}}');
        $this->dropIndex('idx-equipment-inventory_no', '{{%equipment}}');
        $this->dropTable('{{%equipment}}');
    }
}
