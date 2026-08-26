<?php

namespace app\models;

use yii\db\ActiveRecord;

class Equipment extends ActiveRecord
{
    const STATUS_AVAILABLE = 0;
    const STATUS_LOANED = 1;
    const STATUS_MAINTENANCE = 2;
    const STATUS_SCRAPPED = 3;

    public static function tableName()
    {
        return '{{%equipment}}';
    }

    public function rules()
    {
        return [
            [['category_id', 'inventory_no', 'name'], 'required'],
            ['category_id', 'integer'],
            [['description'], 'string'],
            ['inventory_no', 'string', 'max' => 50],
            ['name', 'string', 'max' => 255],
            ['status', 'in', 'range' => array_keys(self::statusLabels())],
            ['purchased_at', 'date', 'format' => 'php:Y-m-d'],
            ['deposit', 'integer', 'min' => 0],
            ['inventory_no', 'unique'],
            ['category_id', 'exist', 'targetClass' => Category::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'Azonosító',
            'category_id' => 'Kategória',
            'inventory_no' => 'Leltári szám',
            'name' => 'Megnevezés',
            'description' => 'Leírás',
            'status' => 'Állapot',
            'purchased_at' => 'Vásárlás dátuma',
            'deposit' => 'Kaució (Ft)',
        ];
    }

    public static function statusLabels()
    {
        return [
            self::STATUS_AVAILABLE => 'Elérhető',
            self::STATUS_LOANED => 'Kiadva',
            self::STATUS_MAINTENANCE => 'Karbantartás',
            self::STATUS_SCRAPPED => 'Selejtezett',
        ];
    }

    public function getStatusLabel()
    {
        $labels = self::statusLabels();
        return isset($labels[$this->status]) ? $labels[$this->status] : 'Ismeretlen';
    }

    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    public function getLoans()
    {
        return $this->hasMany(Loan::class, ['equipment_id' => 'id']);
    }
}
