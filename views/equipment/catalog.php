<?php

use yii\helpers\Html;
use yii\widgets\ListView;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var app\models\Category[] $categories */
/** @var string|null $selectedCategory */

$this->title = 'Elérhető eszközök';
// A morzsamenüben a menüpont neve szerepel, hogy a navigációval egyezzen.
$this->params['breadcrumbs'][] = 'Katalógus';
?>
<div class="equipment-catalog">

    <h1><?= Html::encode($this->title) ?></h1>

    <p class="text-muted">
        Ezek az eszközök most kölcsönözhetők. Ami éppen kint van vagy
        karbantartás alatt áll, nem jelenik meg a listában.
    </p>

    <div class="mb-4">
        <?= Html::a('Összes', ['catalog'], [
            'class' => $selectedCategory
                ? 'btn btn-outline-secondary me-1 mb-1'
                : 'btn btn-secondary me-1 mb-1',
        ]) ?>

        <?php foreach ($categories as $category): ?>
            <?= Html::a(Html::encode($category->name), ['catalog', 'category' => $category->id], [
                'class' => (string) $selectedCategory === (string) $category->id
                    ? 'btn btn-secondary me-1 mb-1'
                    : 'btn btn-outline-secondary me-1 mb-1',
            ]) ?>
        <?php endforeach; ?>
    </div>

    <?= ListView::widget([
        'dataProvider' => $dataProvider,
        'itemView' => '_card',
        'layout' => "{items}\n{pager}",
        'options' => ['class' => 'row'],
        'itemOptions' => ['class' => 'col-12 col-md-6 col-lg-4 mb-3'],
        'emptyText' => 'Ebben a kategóriában most nincs elérhető eszköz.',
        'emptyTextOptions' => ['class' => 'alert alert-info'],
    ]) ?>

</div>