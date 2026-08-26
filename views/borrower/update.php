<?php

use yii\helpers\Html;

$this->title = 'Kölcsönvevő módosítása';
$this->params['breadcrumbs'][] = ['label' => 'Kölcsönvevők', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= $this->render('_form', ['model' => $model]) ?>
