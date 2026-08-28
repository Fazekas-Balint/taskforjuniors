<?php

namespace tests\unit\fixtures;

use app\models\Borrower;
use yii\test\ActiveFixture;

class BorrowerFixture extends ActiveFixture
{
    public $modelClass = Borrower::class;

    public $dataFile = '@tests/_data/borrower.php';
}
