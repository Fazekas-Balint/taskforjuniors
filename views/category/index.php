<?php

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Kategóriák';
?>
<div class="category-index">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Új kategória', ['create'], ['class' => 'btn btn-success']) ?>
    </div>
    <div class="table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'name',
            'slug',
            'created_at',
            ['class' => 'yii\grid\ActionColumn', 'template' => '{update} {delete}'],
        ],
    ]) ?>
    </div>
</div>
