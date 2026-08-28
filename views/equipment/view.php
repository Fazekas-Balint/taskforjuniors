<?php

use app\models\Loan;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Equipment $model */
/** @var app\models\Loan[] $loans */

$this->title = $model->inventory_no . ' – ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Eszközök', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->inventory_no;

$fmt = Yii::$app->formatter;
?>
<div class="equipment-view">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <span class="d-flex gap-2">
            <?= Html::a('Módosítás', ['update', 'id' => $model->id], ['class' => 'btn btn-outline-primary']) ?>
            <?php if ($model->isAvailable()): ?>
                <?= Html::a('Kiadás', ['/loan/create', 'equipment_id' => $model->id], ['class' => 'btn btn-success']) ?>
            <?php endif; ?>
        </span>
    </div>

    <div class="table-responsive mb-4">
        <table class="table table-sm align-middle">
            <tbody>
            <tr>
                <th>Kategória</th>
                <td><?= Html::a(
                    Html::encode($model->category ? $model->category->name : '—'),
                    ['index', 'category_id' => $model->category_id]
                ) ?></td>
            </tr>
            <tr>
                <th>Raktár</th>
                <td><strong><?= Html::encode($model->storage_location) ?></strong></td>
            </tr>
            <tr>
                <th>Státusz</th>
                <td><?= Html::encode($model->statusLabel) ?></td>
            </tr>
            <tr>
                <th>Letét</th>
                <td><?= $fmt->asInteger($model->deposit) ?> Ft</td>
            </tr>
            <tr>
                <th>Beszerzés dátuma</th>
                <td><?= $model->purchased_at ? $fmt->asDate($model->purchased_at, 'php:Y. m. d.') : '—' ?></td>
            </tr>
            <tr>
                <th>Leírás</th>
                <td><?= Html::encode($model->description ?: '—') ?></td>
            </tr>
            </tbody>
        </table>
    </div>

    <h2 class="h4">Kölcsönzési előzmény</h2>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
            <tr>
                <th>Kölcsönvevő</th>
                <th>Raktár</th>
                <th>Kiadás</th>
                <th>Határidő</th>
                <th>Visszahozás</th>
                <th>Állapot</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($loans as $loan): ?>
                <tr>
                    <td><?= Html::encode($loan->borrower ? $loan->borrower->full_name : '—') ?></td>
                    <td><?= Html::encode($loan->storage_location) ?></td>
                    <td><?= $fmt->asDate($loan->loaned_at, 'php:Y. m. d.') ?></td>
                    <td><?= $fmt->asDate($loan->due_at, 'php:Y. m. d.') ?></td>
                    <td><?= $loan->returned_at ? $fmt->asDate($loan->returned_at, 'php:Y. m. d.') : '—' ?></td>
                    <td>
                        <?php if (!$loan->isOpen()): ?>
                            <span class="text-muted">Lezárva</span>
                        <?php elseif ($loan->isOverdue()): ?>
                            <span class="text-danger fw-semibold">
                                Késésben (<?= $loan->getOverdueDays() ?> nap, <?= $fmt->asInteger($loan->getLateFee()) ?> Ft)
                            </span>
                        <?php else: ?>
                            <span class="text-success">Kint van</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$loans): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">Ezt az eszközt még nem kölcsönözték ki.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
