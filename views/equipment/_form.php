<?php

use app\models\Category;
use app\models\Equipment;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Equipment $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="equipment-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'category_id')->dropDownList(
        ArrayHelper::map(Category::find()->orderBy('name')->all(), 'id', 'name'),
        ['prompt' => '— Válassz kategóriát —']
    ) ?>

    <?= $form->field($model, 'inventory_no')
        ->textInput(['maxlength' => true])
        ->hint('Formátum: két nagybetű, kötőjel, négy számjegy (pl. LP-0007).') ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 4]) ?>

    <?= $form->field($model, 'status')->dropDownList(Equipment::statusLabels()) ?>

    <?= $form->field($model, 'purchased_at')->input('date') ?>

    <?= $form->field($model, 'deposit')->textInput(['type' => 'number', 'min' => 0]) ?>

    <div class="form-group">
        <?= Html::submitButton('Mentés', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>