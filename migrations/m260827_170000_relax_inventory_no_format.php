<?php

use yii\db\Migration;

/**
 * Az ügyfél kérésére a leltári szám bármilyen formátumú és hosszú lehet, csak
 * kitöltve kell lennie (a korábbi LP-0007 formátumkényszer megszűnt), ezért a
 * 20 karakteres oszlop is szűk lett.
 */
class m260827_170000_relax_inventory_no_format extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('{{%equipment}}', 'inventory_no', $this->string(255)->notNull());
    }

    public function safeDown()
    {
        // A 20 karakternél hosszabb leltári számok levágása helyett inkább szólunk.
        $tooLong = (int) $this->db
            ->createCommand('SELECT COUNT(*) FROM {{%equipment}} WHERE CHAR_LENGTH(inventory_no) > 20')
            ->queryScalar();

        if ($tooLong > 0) {
            echo "    > $tooLong eszköz leltári száma hosszabb 20 karakternél, a visszaállítás adatvesztéssel járna.\n";

            return false;
        }

        $this->alterColumn('{{%equipment}}', 'inventory_no', $this->string(20)->notNull());
    }
}
