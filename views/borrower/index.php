<?php

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Kölcsönvevők';
?>
<div class="borrower-index">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Új kölcsönvevő', ['create'], ['class' => 'btn btn-success']) ?>
    </div>
    <div class="table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'full_name',
            'email:email',
            'phone',
            [
                'attribute' => 'is_active',
                'value' => function ($model) { return $model->is_active ? 'Igen' : 'Nem'; },
            ],
            ['class' => 'yii\grid\ActionColumn', 'template' => '{update} {delete}'],
        ],
    ]) ?>
    </div>
</div>
