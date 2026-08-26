<?php

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$form = ActiveForm::begin();
echo $form->field($model, 'full_name')->textInput(['maxlength' => true]);
echo $form->field($model, 'email')->input('email', ['maxlength' => true]);
echo $form->field($model, 'phone')->textInput(['maxlength' => true]);
echo $form->field($model, 'is_active')->checkbox();
echo Html::submitButton('Mentés', ['class' => 'btn btn-primary']);
ActiveForm::end();
