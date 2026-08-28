<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Az eszköz raktári helye (HJ-1): az ügyfél a helyhiány miatt raktárak között
 * mozgatja az eszközöket, ezért az aktuális raktárnak magán az eszközön a helye,
 * nem csak a kölcsönzésen.
 */
class m260827_160000_add_storage_location_to_equipment extends Migration
{
    public function safeUp()
    {
        $table = $this->db->schema->getTableSchema('{{%equipment}}', true);
        if ($table === null || isset($table->columns['storage_location'])) {
            return;
        }

        $this->addColumn(
            '{{%equipment}}',
            'storage_location',
            $this->string(100)->notNull()->defaultValue('Központi')->after('name')
        );

        // Amit épp kölcsönöztek, az a kölcsönzésnél rögzített raktárból ment ki:
        // az eszköz raktára induláskor az legyen.
        $openLoans = (new Query())
            ->select(['equipment_id', 'storage_location'])
            ->from('{{%loan}}')
            ->where(['returned_at' => null])
            ->all($this->db);

        foreach ($openLoans as $loan) {
            if ($loan['storage_location'] !== '' && $loan['storage_location'] !== null) {
                $this->update(
                    '{{%equipment}}',
                    ['storage_location' => $loan['storage_location']],
                    ['id' => $loan['equipment_id']]
                );
            }
        }

        $this->createIndex('idx-equipment-storage_location', '{{%equipment}}', 'storage_location');
    }

    public function safeDown()
    {
        $table = $this->db->schema->getTableSchema('{{%equipment}}', true);
        if ($table !== null && isset($table->columns['storage_location'])) {
            $this->dropIndex('idx-equipment-storage_location', '{{%equipment}}');
            $this->dropColumn('{{%equipment}}', 'storage_location');
        }
    }
}
