<?php

namespace app\controllers;

use app\models\Category;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class CategoryController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Yii::$app->user->identity->canEdit();
                        },
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index', [
            'dataProvider' => new \yii\data\ActiveDataProvider([
                'query' => Category::find()->orderBy(['name' => SORT_ASC]),
            ]),
        ]);
    }

    public function actionCreate()
    {
        $model = new Category();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'A kategória létrejött.');
            return $this->redirect(['index']);
        }
        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'A kategória módosítva.');
            return $this->redirect(['index']);
        }
        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        if (Yii::$app->request->isPost) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // A sort a tranzakció végéig zároljuk, hogy közben ne kerüljön alá eszköz.
                $model = Category::findBySql(
                    'SELECT * FROM {{%category}} WHERE id = :id FOR UPDATE',
                    [':id' => $id]
                )->one();
                if (!$model) {
                    throw new NotFoundHttpException('A kategória nem található.');
                }
                if ($model->getEquipments()->exists()) {
                    throw new \DomainException('A kategória nem törölhető, amíg eszköz tartozik hozzá.');
                }
                if ($model->delete() === false) {
                    throw new \DomainException('A kategória törlése sikertelen.');
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'A kategória törölve.');
            } catch (\Throwable $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = Category::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('A kategória nem található.');
    }
}
