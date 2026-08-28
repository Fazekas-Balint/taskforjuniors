<?php

namespace tests\unit\fixtures;

use app\models\Equipment;
use yii\test\ActiveFixture;

class EquipmentFixture extends ActiveFixture
{
    public $modelClass = Equipment::class;

    public $dataFile = '@tests/_data/equipment.php';

    /**
     * Categories have to exist before the equipment that points at them.
     */
    public $depends = [CategoryFixture::class];
}
