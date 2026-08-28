<?php

use yii\db\Migration;

/**
 * A raktári helyszín a kölcsönzéshez tartozik: az új kérelem űrlapján kell megadni,
 * így a riportokban és a listákban végig követhető, honnan/hová került az eszköz.
 */
class m260827_120000_add_storage_location_to_loan extends Migration
{
    public function safeUp()
    {
        $table = $this->db->schema->getTableSchema('{{%loan}}', true);
        if ($table === null || isset($table->columns['storage_location'])) {
            return;
        }

        $this->addColumn(
            '{{%loan}}',
            'storage_location',
            $this->string(100)->notNull()->defaultValue('')->after('borrower_id')
        );

        // A korábbi kölcsönzéseknél nincs rögzített helyszín; a riportban ne maradjon üres cella.
        $this->update('{{%loan}}', ['storage_location' => 'Központi'], ['storage_location' => '']);
    }

    public function safeDown()
    {
        $table = $this->db->schema->getTableSchema('{{%loan}}', true);
        if ($table !== null && isset($table->columns['storage_location'])) {
            $this->dropColumn('{{%loan}}', 'storage_location');
        }
    }
}
