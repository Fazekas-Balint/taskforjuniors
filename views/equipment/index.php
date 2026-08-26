<?php

use app\models\Equipment;
use yii\bootstrap5\ActiveForm;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$this->title = 'Eszközök';
?>
<div class="equipment-index">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Új eszköz', ['create'], ['class' => 'btn btn-success']) ?>
    </div>
    <?php $form = ActiveForm::begin(['method' => 'get', 'action' => ['index'], 'options' => ['class' => 'row g-2 mb-3 filter-form']]); ?>
    <div class="col-md-4"><?= Html::textInput('q', Yii::$app->request->get('q'), ['class' => 'form-control', 'placeholder' => 'Keresés leltári szám vagy név alapján']) ?></div>
    <div class="col-md-3"><?= Html::dropDownList('category_id', Yii::$app->request->get('category_id'), ArrayHelper::map($categories, 'id', 'name'), ['class' => 'form-select', 'prompt' => 'Minden kategória']) ?></div>
    <div class="col-md-3"><?= Html::dropDownList('status', Yii::$app->request->get('status'), Equipment::statusLabels(), ['class' => 'form-select', 'prompt' => 'Minden státusz']) ?></div>
    <div class="col-md-2"><?= Html::submitButton('Szűrés', ['class' => 'btn btn-outline-primary']) ?> <?= Html::a('Törlés', ['index'], ['class' => 'btn btn-outline-secondary']) ?></div>
    <?php ActiveForm::end(); ?>
    <div class="table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'inventory_no',
            'name',
            ['attribute' => 'category_id', 'value' => function ($model) { return $model->category ? $model->category->name : ''; }],
            ['attribute' => 'status', 'value' => function ($model) { return $model->statusLabel; }],
            'deposit',
            ['class' => 'yii\grid\ActionColumn', 'template' => '{update} {delete}'],
        ],
    ]) ?>
    </div>
</div>
