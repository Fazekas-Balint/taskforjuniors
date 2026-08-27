<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Equipment $model */
/** @var int $key */
/** @var int $index */
?>
<div class="card h-100">
    <div class="card-body">
        <h5 class="card-title"><?= Html::encode($model->name) ?></h5>

        <h6 class="card-subtitle mb-2 text-muted">
            <?= Html::encode($model->category->name) ?>
        </h6>

        <p class="card-text">
            <?= Html::encode($model->description ?: 'Nincs leírás.') ?>
        </p>

        <ul class="list-unstyled small mb-0">
            <li><strong>Leltári szám:</strong> <?= Html::encode($model->inventory_no) ?></li>
            <li><strong>Raktár:</strong> <?= Html::encode($model->storage_location) ?></li>
            <li><strong>Letét:</strong> <?= Yii::$app->formatter->asInteger($model->deposit) ?> Ft</li>
        </ul>
    </div>

    <div class="card-footer bg-transparent">
        <span class="badge bg-success"><?= Html::encode($model->statusLabel) ?></span>
    </div>
</div>