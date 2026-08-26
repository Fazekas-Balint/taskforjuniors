<?php

use yii\helpers\Html;

$this->title = 'Eszköz módosítása';
$this->params['breadcrumbs'][] = ['label' => 'Eszközök', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= $this->render('_form', ['model' => $model]) ?>
