<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "equipment".
 *
 * @property int $id
 * @property int $category_id
 * @property string $inventory_no
 * @property string $name
 * @property string|null $description
 * @property int $status
 * @property string|null $purchased_at
 * @property int $deposit
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property Category $category
 */
class Equipment extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'equipment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['description', 'purchased_at', 'updated_at'], 'default', 'value' => null],
            [['deposit'], 'default', 'value' => 0],
            [['category_id', 'inventory_no', 'name', 'created_at'], 'required'],
            [['category_id', 'status', 'deposit'], 'integer'],
            [['description'], 'string'],
            [['purchased_at', 'created_at', 'updated_at'], 'safe'],
            [['inventory_no'], 'string', 'max' => 20],
            [['name'], 'string', 'max' => 150],
            [['inventory_no'], 'unique'],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['category_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => 'Category ID',
            'inventory_no' => 'Inventory No',
            'name' => 'Name',
            'description' => 'Description',
            'status' => 'Status',
            'purchased_at' => 'Purchased At',
            'deposit' => 'Deposit',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Category]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

}
