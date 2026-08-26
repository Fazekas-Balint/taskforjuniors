<?php

use yii\helpers\Html;

$this->title = 'Kategória létrehozása';
$this->params['breadcrumbs'][] = ['label' => 'Kategóriák', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= $this->render('_form', ['model' => $model]) ?>
