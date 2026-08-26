<?php

namespace app\controllers;

use app\models\Borrower;
use app\models\Equipment;
use app\models\Loan;
use app\models\LoanForm;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class LoanController extends Controller
{
    public function actionIndex()
    {
        return $this->actionCreate();
    }

    public function actionCreate()
    {
        $model = new LoanForm(['loaned_at' => date('Y-m-d'), 'due_at' => date('Y-m-d', strtotime('+7 days'))]);
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $equipment = Equipment::find()
                    ->where(['id' => $model->equipment_id])
                    ->forUpdate()
                    ->one();
                $borrower = Borrower::find()
                    ->where(['id' => $model->borrower_id])
                    ->forUpdate()
                    ->one();
                if (!$equipment || $equipment->status !== Equipment::STATUS_AVAILABLE ||
                    !$borrower || !$borrower->is_active ||
                    Loan::find()
                        ->where(['borrower_id' => $model->borrower_id, 'returned_at' => null])
                        ->count() >= Loan::MAX_OPEN_LOANS_PER_BORROWER ||
                    Loan::find()
                        ->where(['equipment_id' => $model->equipment_id, 'returned_at' => null])
                        ->forUpdate()
                        ->exists()) {
                    throw new \DomainException('Az eszköz vagy a kölcsönvevő állapota közben megváltozott.');
                }

                $loan = new Loan();
                $loan->setAttributes($model->attributes);
                if (!$loan->save()) {
                    throw new \DomainException(implode(' ', $loan->getFirstErrors()));
                }
                $equipment->status = Equipment::STATUS_LOANED;
                if (!$equipment->save(false, ['status'])) {
                    throw new \DomainException('Az eszköz állapotának mentése sikertelen.');
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'A kölcsönzés létrejött.');
                return $this->redirect(['index']);
            } catch (\Throwable $e) {
                $transaction->rollBack();
                $model->addError('equipment_id', $e->getMessage());
            }
        }

        return $this->render('create', [
            'model' => $model,
            'equipmentOptions' => Equipment::find()->where(['status' => Equipment::STATUS_AVAILABLE])->orderBy(['name' => SORT_ASC])->all(),
            'borrowerOptions' => Borrower::find()->where(['is_active' => true])->orderBy(['full_name' => SORT_ASC])->all(),
        ]);
    }

    public function actionReturn($id)
    {
        if (!Yii::$app->request->isPost) {
            throw new NotFoundHttpException();
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $loan = Loan::find()
                ->where(['id' => $id])
                ->forUpdate()
                ->one();
            if (!$loan || !$loan->isOpen()) {
                throw new \DomainException('A kölcsönzés már le van zárva vagy nem található.');
            }

            $equipment = Equipment::find()
                ->where(['id' => $loan->equipment_id])
                ->forUpdate()
                ->one();
            if (!$equipment) {
                throw new \DomainException('A kölcsönzéshez tartozó eszköz nem található.');
            }

            $loan->returned_at = date('Y-m-d');
            if (!$loan->save(false, ['returned_at'])) {
                throw new \DomainException('A visszavétel mentése sikertelen.');
            }
            $equipment->status = Equipment::STATUS_AVAILABLE;
            if (!$equipment->save(false, ['status'])) {
                throw new \DomainException('Az eszköz elérhető állapotának mentése sikertelen.');
            }
            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Az eszköz visszavétele megtörtént.');
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', $e->getMessage());
        }
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = Loan::find()->with(['equipment', 'borrower'])->where(['id' => $id])->one()) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('A kölcsönzés nem található.');
    }
}
