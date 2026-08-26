<?php

namespace app\models;

use yii\db\ActiveRecord;

class Category extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%category}}';
    }

    public function rules()
    {
        return [
            [['name', 'slug'], 'required'],
            [['name', 'slug'], 'string', 'max' => 255],
            ['slug', 'match', 'pattern' => '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'message' => 'A slug csak kisbetűket, számokat és kötőjeleket tartalmazhat.'],
            ['slug', 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'Azonosító',
            'name' => 'Kategória neve',
            'slug' => 'Slug',
            'created_at' => 'Létrehozva',
        ];
    }

    public function getEquipments()
    {
        return $this->hasMany(Equipment::class, ['category_id' => 'id']);
    }
}
