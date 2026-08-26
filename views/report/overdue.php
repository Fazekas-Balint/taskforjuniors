<?php

use yii\helpers\Html;

$this->title = 'Lejárt kölcsönzések';
?>
<div class="report-overdue">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('CSV export', ['overdue-csv'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>
    <div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead><tr><th>Leltári szám</th><th>Eszköz</th><th>Kölcsönvevő</th><th>Határidő</th><th>Késés (nap)</th><th>Késedelmi díj</th></tr></thead>
        <tbody>
        <?php foreach ($loans as $loan): ?>
            <tr>
                <td><?= Html::encode($loan->equipment ? $loan->equipment->inventory_no : '') ?></td>
                <td><?= Html::encode($loan->equipment ? $loan->equipment->name : '') ?></td>
                <td><?= Html::encode($loan->borrower ? $loan->borrower->full_name : '') ?></td>
                <td><?= Html::encode($loan->due_at) ?></td>
                <td><?= $loan->getOverdueDays() ?></td>
                <td><?= Yii::$app->formatter->asInteger($loan->getLateFee()) ?> Ft</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
