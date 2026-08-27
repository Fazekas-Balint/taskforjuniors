<?php

use app\models\Equipment;
use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$this->title = 'Eszközök';
$this->params['breadcrumbs'][] = 'Eszközök';
?>
<div class="equipment-index">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Új eszköz', ['create'], ['class' => 'btn btn-success']) ?>
    </div>
    <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['index'], 'options' => ['class' => 'row g-2 mb-3 filter-form']]); ?>
    <div class="col-md-3"><?= Html::textInput('q', Yii::$app->request->get('q'), ['class' => 'form-control', 'placeholder' => 'Keresés leltári szám vagy név alapján']) ?></div>
    <div class="col-md-2"><?= Html::dropDownList('category_id', Yii::$app->request->get('category_id'), ArrayHelper::map($categories, 'id', 'name'), ['class' => 'form-select', 'prompt' => 'Minden kategória']) ?></div>
    <div class="col-md-2"><?= Html::dropDownList('storage_location', Yii::$app->request->get('storage_location'), Equipment::storageLocationOptions(), ['class' => 'form-select', 'prompt' => 'Minden raktár']) ?></div>
    <div class="col-md-2"><?= Html::dropDownList('status', Yii::$app->request->get('status'), Equipment::statusLabels(), ['class' => 'form-select', 'prompt' => 'Minden státusz']) ?></div>
    <div class="col-md-3"><?= Html::submitButton('Szűrés', ['class' => 'btn btn-outline-primary']) ?> <?= Html::a('Törlés', ['index'], ['class' => 'btn btn-outline-secondary']) ?></div>
    <?php ActiveForm::end(); ?>
    <div class="table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'inventory_no',
            'name',
            ['attribute' => 'category_id', 'value' => function ($model) { return $model->category ? $model->category->name : ''; }],
            // A fejlécre kattintva raktár szerint rendez (a sort attribútum a controllerben van bekötve).
            'storage_location',
            ['attribute' => 'status', 'value' => function ($model) { return $model->statusLabel; }],
            'deposit',
            [
                'label' => 'Áthelyezés',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::beginForm(['move', 'id' => $model->id], 'post', ['class' => 'd-flex gap-1'])
                        . Html::dropDownList('storage_location', $model->storage_location, Equipment::storageLocationOptions(), ['class' => 'form-select form-select-sm'])
                        . Html::submitButton('Áthelyez', ['class' => 'btn btn-sm btn-outline-secondary text-nowrap'])
                        . Html::endForm();
                },
            ],
            ['class' => 'yii\grid\ActionColumn', 'template' => '{update} {delete}'],
        ],
    ]) ?>
    </div>
</div>
