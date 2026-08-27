<?php

namespace app\controllers;

use app\models\Category;
use app\models\Equipment;
use Yii;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class EquipmentController extends Controller
{
    public function behaviors()
    {
        return ['access' => ['class' => AccessControl::class, 'rules' => [['allow' => true, 'roles' => ['@'], 'matchCallback' => function () { return Yii::$app->user->identity->canEdit(); }]]]];
    }

    public function actionIndex()
    {
        $request = Yii::$app->request;
        $query = Equipment::find()->with('category');
        if ($request->get('status', '') !== '') {
            $query->andWhere(['status' => (int) $request->get('status')]);
        }
        if ($request->get('category_id', '') !== '') {
            $query->andWhere(['category_id' => (int) $request->get('category_id')]);
        }
        if ($request->get('q', '') !== '') {
            $query->andWhere(['or',
                ['like', 'inventory_no', $request->get('q')],
                ['like', 'name', $request->get('q')],
            ]);
        }
        return $this->render('index', ['dataProvider' => new ActiveDataProvider([
            'query' => $query->orderBy(['name' => SORT_ASC]),
        ]), 'categories' => Category::find()->orderBy(['name' => SORT_ASC])->all()]);
    }

    public function actionCreate()
    {
        $model = new Equipment(['status' => Equipment::STATUS_AVAILABLE]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Az eszköz létrejött.');
            return $this->redirect(['index']);
        }
        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Az eszköz módosítva.');
            return $this->redirect(['index']);
        }
        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        if (Yii::$app->request->isPost) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // A sort a tranzakció végéig zároljuk, hogy közben ne induljon rá kölcsönzés.
                $model = Equipment::findBySql('SELECT * FROM {{%equipment}} WHERE id = :id FOR UPDATE', [':id' => $id])->one();
                if (!$model) {
                    throw new NotFoundHttpException('Az eszköz nem található.');
                }

                if ($model->getLoans()->exists()) {
                    $model->status = Equipment::STATUS_SCRAPPED;
                    if (!$model->save(false, ['status'])) {
                        throw new \DomainException('Az eszköz selejtezése sikertelen.');
                    }
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'Az eszköz kölcsönzési előzménye miatt selejt státuszba került.');
                } elseif ($model->delete() === false) {
                    throw new \DomainException('Az eszköz törlése sikertelen.');
                } else {
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'Az eszköz törölve.');
                }
            } catch (\Throwable $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = Equipment::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('Az eszköz nem található.');
    }
}
