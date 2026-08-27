<?php

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Kölcsönzés hosszabbítása';
?>
<?php if (!isset($loan)): ?>
    <div class="loan-index">
        <div class="page-header">
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
        <?php if (!$loans): ?>
            <div class="alert alert-info">Nincs hosszabbítható nyitott kölcsönzés.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Eszköz</th>
                        <th>Kölcsönvevő</th>
                        <th>Jelenlegi határidő</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($loans as $openLoan): ?>
                        <tr>
                            <td>
                                <?= Html::encode(
                                    $openLoan->equipment->inventory_no . ' - ' . $openLoan->equipment->name
                                ) ?>
                            </td>
                            <td><?= Html::encode($openLoan->borrower->full_name) ?></td>
                            <td><?= Html::encode($openLoan->due_at) ?></td>
                            <td>
                                <?= Html::a(
                                    'Hosszabbítás',
                                    ['/extend', 'id' => $openLoan->id],
                                    ['class' => 'btn btn-sm btn-outline-primary']
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>

                <div class="alert alert-light border mb-4">
                    <div class="fw-semibold"><?= Html::encode($loan->equipment->name) ?></div>
                    <div class="text-muted"><?= Html::encode($loan->borrower->full_name) ?></div>
                    <div class="mt-2">Jelenlegi határidő:
                        <strong><?= Html::encode($loan->due_at) ?></strong>
                    </div>
                </div>

                <?php $form = ActiveForm::begin([
                    'action' => ['/extend', 'id' => $loan->id],
                    'options' => ['class' => 'vstack gap-2'],
                ]); ?>
                <?= $form->field($model, 'due_at')->input('date') ?>
                <div class="d-grid d-sm-flex gap-2 mt-2">
                    <?= Html::submitButton('Határidő mentése', ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('Mégse', ['/loan'], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
