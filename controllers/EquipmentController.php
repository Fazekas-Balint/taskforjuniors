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
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    // The public catalogue is open to everyone, guests included.
                    [
                        'allow' => true,
                        'actions' => ['catalog'],
                        'roles' => ['?', '@'],
                    ],
                    // Everything else requires a logged-in user who may edit.
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
        $request = Yii::$app->request;

        // joinWith() eager-loads the category (no N+1) and also exposes
        // category.name to the ORDER BY clause.
        $query = Equipment::find()->joinWith('category');

        // Column names are qualified: the JOIN brings in a second table
        // that also has `id` and `name` columns.
        if ($request->get('status', '') !== '') {
            $query->andWhere(['equipment.status' => (int) $request->get('status')]);
        }
        if ($request->get('category_id', '') !== '') {
            $query->andWhere(['equipment.category_id' => (int) $request->get('category_id')]);
        }
        if ($request->get('q', '') !== '') {
            $query->andWhere(['or',
                ['like', 'equipment.inventory_no', $request->get('q')],
                ['like', 'equipment.name', $request->get('q')],
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => [
                'defaultOrder' => ['inventory_no' => SORT_ASC],
                'attributes' => [
                    'inventory_no',
                    'status',
                    'deposit',
                    'purchased_at',
                    'name' => [
                        'asc' => ['equipment.name' => SORT_ASC],
                        'desc' => ['equipment.name' => SORT_DESC],
                    ],
                    'category_id' => [
                        'asc' => ['category.name' => SORT_ASC],
                        'desc' => ['category.name' => SORT_DESC],
                    ],
                ],
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'categories' => Category::find()->orderBy(['name' => SORT_ASC])->all(),
        ]);
    }
    /**
     * Public catalogue of the items that can be borrowed right now.
     *
     * @return string
     */
    public function actionCatalog()
    {
        $categoryId = Yii::$app->request->get('category');

        $query = Equipment::find()
            ->with('category')
            ->where(['status' => Equipment::STATUS_AVAILABLE])
            ->andFilterWhere(['category_id' => $categoryId])
            ->orderBy(['name' => SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 12],
        ]);

        return $this->render('catalog', [
            'dataProvider' => $dataProvider,
            'categories' => Category::find()->orderBy(['name' => SORT_ASC])->all(),
            'selectedCategory' => $categoryId,
        ]);
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
            $model = $this->findModel($id);

            // BR-8 lives in Equipment::beforeDelete(): an item with loan
            // history is scrapped instead of deleted and delete() returns false.
            if ($model->delete()) {
                Yii::$app->session->setFlash('success', 'Az eszköz törölve.');
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
