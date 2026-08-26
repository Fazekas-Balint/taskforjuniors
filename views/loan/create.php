<?php

use app\models\Borrower;
use app\models\Equipment;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$this->title = 'Új kölcsönzés';
$form = ActiveForm::begin();
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= $form->field($model, 'equipment_id')->dropDownList(ArrayHelper::map($equipmentOptions, 'id', function (Equipment $item) { return $item->inventory_no . ' - ' . $item->name; }), ['prompt' => 'Válasszon eszközt']) ?>
<?= $form->field($model, 'borrower_id')->dropDownList(ArrayHelper::map($borrowerOptions, 'id', 'full_name'), ['prompt' => 'Válasszon kölcsönvevőt']) ?>
<?= $form->field($model, 'loaned_at')->input('date') ?>
<?= $form->field($model, 'due_at')->input('date') ?>
<?= $form->field($model, 'note')->textarea(['rows' => 4]) ?>
<?= Html::submitButton('Kölcsönzés mentése', ['class' => 'btn btn-primary']) ?>
<?php ActiveForm::end(); ?>
