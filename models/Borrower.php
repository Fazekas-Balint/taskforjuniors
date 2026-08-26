<?php

namespace app\models;

use yii\db\ActiveRecord;

class Borrower extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%borrower}}';
    }

    public function rules()
    {
        return [
            [['full_name', 'email'], 'required'],
            ['full_name', 'string', 'max' => 255],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique'],
            ['phone', 'string', 'max' => 50],
            ['is_active', 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'Azonosító',
            'full_name' => 'Teljes név',
            'email' => 'E-mail',
            'phone' => 'Telefon',
            'is_active' => 'Aktív',
        ];
    }

    public function getLoans()
    {
        return $this->hasMany(Loan::class, ['borrower_id' => 'id']);
    }
}
