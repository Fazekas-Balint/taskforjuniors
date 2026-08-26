<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\helpers\Inflector;

/**
 * This is the model class for table "category".
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $created_at
 *
 * @property Equipment[] $equipments
 */
class Category extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'category';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'slug'], 'required'],
            [['name'], 'string', 'max' => 100],
            [['slug'], 'string', 'max' => 120],
            [['slug'], 'match',
                'pattern' => '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'message' => 'Az URL-azonosító csak kisbetűt, számot és kötőjelet tartalmazhat.',
            ],
            [['slug'], 'unique',
                'message' => 'Ez az URL-azonosító már foglalt.',
            ],
            [['created_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Név',
            'slug' => 'URL-azonosító',
            'created_at' => 'Létrehozva',
        ];
    }

    /**
     * Gets query for [[Equipments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEquipments()
    {
        return $this->hasMany(Equipment::class, ['category_id' => 'id']);
    }

    /**
     * Derives the slug from the name when left empty.
     *
     * {@inheritdoc}
     */
    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        if ($this->slug === null || $this->slug === '') {
            $this->slug = Inflector::slug(Inflector::transliterate((string) $this->name));
        }

        return true;
    }

    /**
     * Prevents deletion while equipment is still assigned to this category (BR-8).
     *
     * {@inheritdoc}
     */
    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        if ($this->getEquipments()->exists()) {
            Yii::$app->session->setFlash(
                'error',
                'A kategória nem törölhető, mert tartozik hozzá eszköz.'
            );
            return false;
        }

        return true;
    }
}
