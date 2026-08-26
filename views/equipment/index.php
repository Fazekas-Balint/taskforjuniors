<?php

use app\models\Category;
use app\models\Equipment;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\EquipmentSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Eszközök';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="equipment-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Új eszköz', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'emptyText' => 'Nincs a szűrésnek megfelelő eszköz.',
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'inventory_no',
            'name',
            [
                'attribute' => 'category_id',
                'value' => 'category.name',
                'filter' => ArrayHelper::map(
                    Category::find()->orderBy('name')->all(),
                    'id',
                    'name'
                ),
            ],
            [
                'attribute' => 'status',
                'value' => 'statusLabel',
                'filter' => Equipment::statusLabels(),
            ],
            [
                'attribute' => 'deposit',
                'format' => ['decimal', 0],
            ],
            [
                'class' => ActionColumn::className(),
                'urlCreator' => function ($action, Equipment $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                },
            ],
        ],
    ]); ?>

</div>