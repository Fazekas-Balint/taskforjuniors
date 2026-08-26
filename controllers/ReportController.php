<?php

namespace app\controllers;

use Yii;
use app\services\EquipmentService;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class ReportController extends Controller
{
    public function behaviors()
    {
        return ['access' => ['class' => AccessControl::class, 'rules' => [['allow' => true, 'roles' => ['@']]]]];
    }

    public function actionOverdue()
    {
        $this->view->params['breadcrumbs'] = [
            ['label' => 'Áttekintés', 'url' => ['/site/index']],
            ['label' => 'Késés-riport'],
        ];
        $service = new EquipmentService();
        $service->initialize();
        $filters = Yii::$app->request->queryParams;
        return $this->render('overdue', ['rows' => $service->overdueLoans($filters), 'lenders' => $service->lenders(), 'categories' => $service->categories(), 'filters' => $filters, 'totalFee' => $service->overdueFee($filters)]);
    }

    public function actionExport()
    {
        $service = new EquipmentService();
        $service->initialize();
        $rows = $service->overdueLoans(Yii::$app->request->queryParams);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, ['Leltári szám', 'Eszköz', 'Kategória', 'Kölcsönző', 'Email', 'Határidő', 'Késés napjai', 'Késedelmi díj'], ';');
        foreach ($rows as $row) fputcsv($stream, [$row['inventory_no'], $row['equipment_name'], $row['category_name'], $row['full_name'], $row['email'], $row['due_at'], $row['days_late'], $row['late_fee']], ';');
        rewind($stream);
        return Yii::$app->response->sendContentAsFile(stream_get_contents($stream), 'kesesi-riport-' . date('Y-m-d') . '.csv', ['mimeType' => 'text/csv; charset=UTF-8']);
    }
}