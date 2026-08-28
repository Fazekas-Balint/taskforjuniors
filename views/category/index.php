<?php

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Kategóriák';
$this->params['breadcrumbs'][] = 'Kategóriák';
?>
<div class="category-index">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Új kategória', ['create'], ['class' => 'btn btn-success']) ?>
    </div>
    <div class="table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            // A kategória nevére kattintva megnyílik az oda tartozó eszközök listája.
            [
                'attribute' => 'name',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::a(Html::encode($model->name), ['/equipment/index', 'category_id' => $model->id]);
                },
            ],
            'slug',
            [
                'label' => 'Eszközök',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::a(
                        $model->getEquipments()->count() . ' db',
                        ['/equipment/index', 'category_id' => $model->id]
                    );
                },
            ],
            'created_at',
            ['class' => 'yii\grid\ActionColumn', 'template' => '{update} {delete}'],
        ],
    ]) ?>
    </div>
</div>
