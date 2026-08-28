<?php

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Category $model */
/** @var yii\bootstrap5\ActiveForm $form */

$form = ActiveForm::begin();

echo $form->field($model, 'name')->textInput(['maxlength' => true]);

// Client-side validation is off for this one field on purpose: the slug is
// generated from the name in Category::beforeValidate(), which only runs once
// the form reaches the server. With the browser enforcing "required" here, an
// empty slug would never get that far. The server-side rule still applies.
echo $form->field($model, 'slug', ['enableClientValidation' => false])
    ->textInput(['maxlength' => true])
    ->hint('Üresen hagyva a névből képződik.');

echo Html::submitButton('Mentés', ['class' => 'btn btn-primary']);

ActiveForm::end();
