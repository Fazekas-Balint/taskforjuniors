<?php

use yii\db\Migration;

/**
 * A raktári helyszín innentől fix lista (lásd app\models\Loan::STORAGE_LOCATIONS),
 * ezért a korábbi szabad szöveges értékeket a központi raktárra igazítjuk.
 */
class m260827_140000_normalize_storage_locations extends Migration
{
    private $locations = ['Központi', 'Raktár 1', 'Raktár 2'];

    public function safeUp()
    {
        $table = $this->db->schema->getTableSchema('{{%loan}}', true);
        if ($table === null || !isset($table->columns['storage_location'])) {
            return;
        }

        $this->update(
            '{{%loan}}',
            ['storage_location' => 'Központi'],
            ['not in', 'storage_location', $this->locations]
        );
    }

    public function safeDown()
    {
        echo "A korábbi szabad szöveges raktári helyszínek nem állíthatók vissza.\n";

        return true;
    }
}
