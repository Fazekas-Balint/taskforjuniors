<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * EquipmentSearch represents the model behind the search form of `app\models\Equipment`.
 */
class EquipmentSearch extends Equipment
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'category_id', 'status', 'deposit'], 'integer'],
            [['inventory_no', 'name', 'purchased_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied.
     *
     * @param array $params
     * @param string|null $formName Form name to be used into `->load()` method.
     *
     * @return ActiveDataProvider
     */
    public function search($params, $formName = null)
    {
        // joinWith() eager-loads the category (so no N+1 in the grid) and
        // also exposes category.name to the ORDER BY clause.
        $query = Equipment::find()->joinWith('category');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => [
                'defaultOrder' => ['inventory_no' => SORT_ASC],
                'attributes' => [
                    'inventory_no',
                    'name',
                    'status',
                    'deposit',
                    'purchased_at',
                    'category_id' => [
                        'asc' => ['category.name' => SORT_ASC],
                        'desc' => ['category.name' => SORT_DESC],
                    ],
                ],
            ],
        ]);

        $this->load($params, $formName);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Column names are qualified: the JOIN brings in a second table
        // that also has `id` and `name` columns.
        $query->andFilterWhere([
            'equipment.id' => $this->id,
            'equipment.category_id' => $this->category_id,
            'equipment.status' => $this->status,
            'equipment.purchased_at' => $this->purchased_at,
            'equipment.deposit' => $this->deposit,
        ]);

        $query->andFilterWhere(['like', 'equipment.inventory_no', $this->inventory_no])
            ->andFilterWhere(['like', 'equipment.name', $this->name]);

        return $dataProvider;
    }
}