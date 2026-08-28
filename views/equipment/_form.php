<?php

use app\models\Category;
use app\models\Equipment;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Equipment $model */
/** @var yii\bootstrap5\ActiveForm $form */

$form = ActiveForm::begin();

echo $form->field($model, 'inventory_no')
    ->textInput(['maxlength' => true])
    ->hint('Formátum: két nagybetű, kötőjel, négy számjegy (pl. LP-0007).');

echo $form->field($model, 'name')->textInput(['maxlength' => true]);

echo $form->field($model, 'category_id')->dropDownList(
    ArrayHelper::map(Category::find()->orderBy(['name' => SORT_ASC])->all(), 'id', 'name'),
    ['prompt' => 'Válasszon kategóriát']
);

echo $form->field($model, 'status')->dropDownList(Equipment::statusLabels());

echo $form->field($model, 'description')->textarea(['rows' => 4]);

echo $form->field($model, 'purchased_at')->input('date');

echo $form->field($model, 'deposit')->input('number', ['min' => 0]);

echo Html::submitButton('Mentés', ['class' => 'btn btn-primary']);

ActiveForm::end();
