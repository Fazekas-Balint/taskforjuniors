<?php

namespace app\controllers;

use DateTimeImmutable;
use app\models\Loan;
use app\models\LoanExtendForm;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class ExtendController extends Controller
{
    public function actionIndex($id = null)
    {
        if ($id === null) {
            return $this->render('index', [
                'loans' => Loan::find()
                    ->with(['equipment', 'borrower'])
                    ->where(['returned_at' => null])
                    ->orderBy(['due_at' => SORT_ASC])
                    ->all(),
            ]);
        }

        $loan = Loan::find()
            ->with(['equipment', 'borrower'])
            ->where(['id' => $id])
            ->one();
        if (!$loan) {
            throw new NotFoundHttpException('A kölcsönzés nem található.');
        }
        if (!$loan->isOpen() || $loan->isOverdue()) {
            throw new NotFoundHttpException('A lezárt vagy késésben lévő kölcsönzés nem hosszabbítható.');
        }

        $model = new LoanExtendForm([
            'current_due_at' => $loan->due_at,
            'loaned_at' => $loan->loaned_at,
            'due_at' => (new DateTimeImmutable($loan->due_at))->modify('+7 days')->format('Y-m-d'),
        ]);
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $lockedLoan = Loan::find()
                    ->where(['id' => $id])
                    ->forUpdate()
                    ->one();
                if (!$lockedLoan || !$lockedLoan->isOpen() || $lockedLoan->isOverdue()) {
                    throw new \DomainException('A kölcsönzés időközben lezárult vagy késésbe került.');
                }

                $expectedDueAt = (new DateTimeImmutable($lockedLoan->due_at))
                    ->modify('+7 days')
                    ->format('Y-m-d');
                $maximumDueAt = (new DateTimeImmutable($lockedLoan->loaned_at))
                    ->modify('+30 days')
                    ->format('Y-m-d');
                if ($model->due_at !== $expectedDueAt || $expectedDueAt > $maximumDueAt) {
                    throw new \DomainException('A kölcsönzés legfeljebb 7 nappal hosszabbítható, összesen 30 napig.');
                }
                $lockedLoan->due_at = $model->due_at;
                if (!$lockedLoan->save(false, ['due_at'])) {
                    throw new \DomainException('A határidő mentése sikertelen.');
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'A határidő módosítva.');
                return $this->redirect(['/loan']);
            } catch (\Throwable $e) {
                $transaction->rollBack();
                $model->addError('due_at', $e->getMessage());
            }
        }

        return $this->render('index', ['model' => $model, 'loan' => $loan]);
    }
}
