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
        return ['access' => ['class' => AccessControl::class, 'rules' => [['allow' => true, 'roles' => ['@'], 'matchCallback' => function () { return Yii::$app->user->identity->canEdit(); }]]]];
    }

    public function actionIndex()
    {
        return $this->render('index', ['dataProvider' => new \yii\data\ActiveDataProvider([
            'query' => Category::find()->orderBy(['name' => SORT_ASC]),
        ])]);
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
            $model = $this->findModel($id);

            // BR-8 lives in Category::beforeDelete(), which sets the error flash.
            if ($model->delete()) {
                Yii::$app->session->setFlash('success', 'A kategória törölve.');
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
