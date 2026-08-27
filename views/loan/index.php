<?php

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Nyitott kölcsönzések';
?>
<div class="loan-index">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Új kölcsönzés', ['create'], ['class' => 'btn btn-success']) ?>
    </div>
    <div class="table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['label' => 'Eszköz', 'value' => function ($model) { return $model->equipment ? $model->equipment->inventory_no . ' - ' . $model->equipment->name : ''; }],
            ['label' => 'Kölcsönvevő', 'value' => function ($model) { return $model->borrower ? $model->borrower->full_name : ''; }],
            'storage_location',
            'loaned_at',
            // A dátum formázása a nézet dolga; a modellben hagyva elrontotta a dátumszámítást (HJ-2).
            ['attribute' => 'due_at', 'format' => ['date', 'php:Y. m. d.']],
            ['label' => 'Lejárt', 'value' => function ($model) { return $model->isOverdue() ? 'Igen (' . $model->getOverdueDays() . ' nap)' : 'Nem'; }],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{return} {extend}',
                'buttons' => [
                    'return' => function ($url, $model) {
                        return Html::a('Visszavétel', $url, ['class' => 'btn btn-sm btn-outline-success', 'data-method' => 'post', 'data-confirm' => 'Biztosan visszavette az eszközt?']);
                    },
                    'extend' => function ($url, $model) {
                        return Html::a('Hosszabbítás', ['/extend', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-primary']);
                    },
                ],
            ],
        ],
    ]) ?>
    </div>
</div>
