<?php

namespace app\controllers;

use app\models\Borrower;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\IntegrityException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class BorrowerController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index', ['dataProvider' => new ActiveDataProvider([
            'query' => Borrower::find()->orderBy(['full_name' => SORT_ASC]),
        ])]);
    }

    public function actionCreate()
    {
        $model = new Borrower(['is_active' => true]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'A kölcsönvevő létrejött.');
            return $this->redirect(['index']);
        }
        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'A kölcsönvevő módosítva.');
            return $this->redirect(['index']);
        }
        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        if (Yii::$app->request->isPost) {
            try {
                $this->findModel($id)->delete();
            } catch (IntegrityException $e) {
                Yii::$app->session->setFlash('error', 'A kölcsönvevő nem törölhető, mert kölcsönzési előzménye van.');
            }
        }
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = Borrower::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('A kölcsönvevő nem található.');
    }
}
