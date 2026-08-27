<?php

use yii\helpers\Html;

$this->title = 'Kikölcsönzött eszközök';
$this->params['breadcrumbs'][] = 'Kikölcsönzött eszközök';
?>
<div class="pending-index">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
            <tr>
                <th>Eszköz</th>
                <th>Kölcsönvevő</th>
                <th>Raktár</th>
                <th>Késésben van?</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($loans as $loan): ?>
                <tr>
                    <td><?= Html::encode($loan->equipment ? $loan->equipment->name : '') ?></td>
                    <td><?= Html::encode($loan->borrower ? $loan->borrower->full_name : '') ?></td>
                    <td><?= Html::encode($loan->storage_location) ?></td>
                    <td>
                        <?php if ($loan->isOverdue()): ?>
                            <span class="text-danger fw-semibold">Igen</span>
                        <?php else: ?>
                            <span class="text-success">Nem</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$loans): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted">Nincs kikölcsönzött eszköz.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
