<?php

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$form = ActiveForm::begin();
echo $form->field($model, 'name')->textInput(['maxlength' => true]);
echo $form->field($model, 'slug')->textInput(['maxlength' => true]);
echo Html::submitButton('Mentés', ['class' => 'btn btn-primary']);
ActiveForm::end();
