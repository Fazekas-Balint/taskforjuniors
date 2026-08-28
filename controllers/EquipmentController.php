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
                    // A katalógust minden belépett felhasználó megnézheti, a
                    // szerkesztő jog nélküli kollega is - vendég viszont nem.
                    [
                        'allow' => true,
                        'actions' => ['catalog'],
                        'roles' => ['@'],
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
        if ($request->get('storage_location', '') !== '') {
            $query->andWhere(['equipment.storage_location' => $request->get('storage_location')]);
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
                    'storage_location' => [
                        'asc' => ['equipment.storage_location' => SORT_ASC],
                        'desc' => ['equipment.storage_location' => SORT_DESC],
                    ],
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

    /**
     * Egy eszköz adatai és a teljes kölcsönzési előzménye - eddig sehol nem
     * lehetett megnézni, hogy egy eszköz mikor kinél volt és melyik raktárból ment ki.
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
            'loans' => $model->getLoans()
                ->with('borrower')
                ->orderBy(['loaned_at' => SORT_DESC, 'id' => SORT_DESC])
                ->all(),
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

    /**
     * Eszköz átmozgatása másik raktárba (HJ-1).
     *
     * Csak a raktár mezőt validálja és menti, hogy a régi, még nem szabványos
     * leltári számú eszközök is mozgathatók legyenek.
     */
    public function actionMove($id)
    {
        if (!Yii::$app->request->isPost) {
            throw new NotFoundHttpException();
        }

        $model = $this->findModel($id);
        $from = $model->storage_location;
        $model->storage_location = Yii::$app->request->post('storage_location');

        if ($model->save(true, ['storage_location', 'updated_at'])) {
            Yii::$app->session->setFlash('success', sprintf(
                '%s – %s átkerült ide: %s (innen: %s).',
                $model->inventory_no,
                $model->name,
                $model->storage_location,
                $from
            ));
        } else {
            Yii::$app->session->setFlash('error', implode(' ', $model->getFirstErrors()));
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
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
