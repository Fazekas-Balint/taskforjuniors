<?php

use yii\helpers\Html;

$this->title = 'Kölcsönzési irányítópult';
?>
<div class="site-index">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Új kölcsönzés', ['/loan/create'], ['class' => 'btn btn-primary']) ?>
    </div>
    <div class="row g-3 my-3">
        <div class="col-12 col-sm-6 col-lg-4"><div class="card dashboard-card text-bg-primary h-100"><div class="card-body"><h5>Kiadva</h5><p class="display-6 mb-0"><?= $outCount ?></p></div></div></div>
        <div class="col-12 col-sm-6 col-lg-4"><div class="card dashboard-card text-bg-danger h-100"><div class="card-body"><h5>Lejárt</h5><p class="display-6 mb-0"><?= $overdueCount ?></p></div></div></div>
        <div class="col-12 col-sm-6 col-lg-4"><div class="card dashboard-card text-bg-warning h-100"><div class="card-body"><h5>Ma esedékes</h5><p class="display-6 mb-0"><?= $dueTodayCount ?></p></div></div></div>
    </div>
    <h2>Legközelebbi határidők</h2>
    <div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead><tr><th>Eszköz</th><th>Kölcsönvevő</th><th>Határidő</th></tr></thead>
        <tbody>
        <?php foreach ($recentLoans as $loan): ?>
            <tr>
                <td><?= Html::encode($loan->equipment ? $loan->equipment->name : '') ?></td>
                <td><?= Html::encode($loan->borrower ? $loan->borrower->full_name : '') ?></td>
                <td><?= Html::encode($loan->due_at) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
