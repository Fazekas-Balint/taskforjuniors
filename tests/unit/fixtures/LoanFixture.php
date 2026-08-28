<?php

namespace tests\unit\fixtures;

use app\models\Loan;
use yii\test\ActiveFixture;

class LoanFixture extends ActiveFixture
{
    public $modelClass = Loan::class;

    public $dataFile = '@tests/_data/loan.php';

    /**
     * A loan points at both an item and a borrower.
     */
    public $depends = [
        EquipmentFixture::class,
        BorrowerFixture::class,
    ];
}
