<?php

namespace app\controllers;

use app\models\Loan;
use yii\web\Controller;

class PendingController extends Controller
{
    public function actionIndex()
    {
        $loans = Loan::find()
            ->with(['equipment', 'borrower'])
            ->where(['returned_at' => null])
            ->orderBy(['due_at' => SORT_ASC])
            ->all();

        return $this->render('index', ['loans' => $loans]);
    }
}
