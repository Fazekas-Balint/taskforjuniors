<?php

namespace app\controllers;

use app\models\Loan;
use Yii;

class ReportController extends \yii\web\Controller
{
    public function actionOverdue()
    {
        $loans = Loan::find()
            ->where(['returned_at' => null])
            ->andWhere(['<', 'due_at', date('Y-m-d')])
            ->with(['equipment', 'borrower'])
            ->orderBy(['due_at' => SORT_ASC])
            ->all();
        return $this->render('overdue', ['loans' => $loans]);
    }

    public function actionOverdueCsv()
    {
        $loans = Loan::find()
            ->where(['returned_at' => null])
            ->andWhere(['<', 'due_at', date('Y-m-d')])
            ->with(['equipment', 'borrower'])
            ->orderBy(['due_at' => SORT_ASC])
            ->all();
        $rows = [["Leltári szám", "Eszköz", "Kölcsönvevő", "Határidő", "Késés (nap)", "Késedelmi díj (Ft)"]];
        foreach ($loans as $loan) {
            $rows[] = [
                $loan->equipment ? $loan->equipment->inventory_no : '',
                $loan->equipment ? $loan->equipment->name : '',
                $loan->borrower ? $loan->borrower->full_name : '',
                $loan->due_at,
                $loan->getOverdueDays(),
                $loan->getLateFee(),
            ];
        }
        $content = "\xEF\xBB\xBF";
        foreach ($rows as $row) {
            $content .= implode(';', array_map(function ($value) {
                return '"' . str_replace('"', '""', (string) $value) . '"';
            }, $row)) . "\r\n";
        }
        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="kesedelmek.csv"');
        return $content;
    }
}
