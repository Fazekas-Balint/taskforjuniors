<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\helpers\Inflector;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $created_at
 *
 * @property Equipment[] $equipments
 */
class Category extends ActiveRecord
{
    /**
     * Hungarian accented characters mapped to ASCII, so slug generation does
     * not depend on the optional intl extension being installed.
     */
    private const TRANSLITERATION = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ö' => 'o',
        'ő' => 'o', 'ú' => 'u', 'ü' => 'u', 'ű' => 'u',
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ö' => 'O',
        'Ő' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ű' => 'U',
    ];

    public static function tableName()
    {
        return '{{%category}}';
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,   // no updated_at column here
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['name', 'slug'], 'required', 'message' => 'Ez a mező nem lehet üres.'],

            // Lengths follow the frozen schema: varchar(100) and varchar(120).
            ['name', 'string', 'max' => 100],
            ['name', 'unique',
                'message' => 'Ilyen nevű kategória már létezik.',
            ],
            ['slug', 'string', 'max' => 120],

            ['slug', 'match',
                'pattern' => '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'message' => 'Az URL-azonosító csak kisbetűt, számot és kötőjelet tartalmazhat.',
            ],
            ['slug', 'unique',
                'message' => 'Ez az URL-azonosító már foglalt.',
            ],
        ];
    }

    /**
     * Derives the slug from the name when left empty.
     *
     * This runs before validation, not before save: the generated value has to
     * pass the required, match and unique rules like a typed one.
     *
     * {@inheritdoc}
     */
    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        if ($this->slug === null || $this->slug === '') {
            $this->slug = Inflector::slug(
                strtr((string) $this->name, self::TRANSLITERATION)
            );
        }

        return true;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'Azonosító',
            'name' => 'Kategória neve',
            'slug' => 'URL-azonosító',
            'created_at' => 'Létrehozva',
        ];
    }

    public function getEquipments()
    {
        return $this->hasMany(Equipment::class, ['category_id' => 'id']);
    }

    /**
     * A category with equipment attached cannot be deleted (BR-8).
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