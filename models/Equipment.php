<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
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
 * @property Loan[] $loans
 */
class Equipment extends ActiveRecord
{
    public const STATUS_AVAILABLE = 0;   // available
    public const STATUS_LOANED = 1;      // out on loan
    public const STATUS_MAINTENANCE = 2; // under maintenance
    public const STATUS_SCRAPPED = 3;    // scrapped

    public static function tableName()
    {
        return '{{%equipment}}';
    }

    /**
     * created_at is NOT NULL without a default in the migration, so it has to
     * be filled here. The Expression is required because the columns are
     * DATETIME, not unix timestamps.
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['description', 'purchased_at'], 'default', 'value' => null],
            ['deposit', 'default', 'value' => 0],
            ['status', 'default', 'value' => self::STATUS_AVAILABLE],

            [['category_id', 'inventory_no', 'name'], 'required'],

            ['category_id', 'integer'],
            ['description', 'string'],

            // Lengths follow the frozen schema: varchar(20) and varchar(150).
            ['inventory_no', 'string', 'max' => 20],
            ['name', 'string', 'max' => 150],

            ['inventory_no', 'match',
                'pattern' => '/^[A-Z]{2}-\d{4}$/',
                'message' => 'A leltári szám formátuma: két nagybetű, kötőjel, négy számjegy (pl. LP-0007).',
            ],
            ['inventory_no', 'unique',
                'message' => 'Ez a leltári szám már foglalt.',
            ],

            ['status', 'in',
                'range' => array_keys(self::statusLabels()),
                'message' => 'Érvénytelen státusz.',
            ],

            ['deposit', 'integer',
                'min' => 0,
                'tooSmall' => 'A letét nem lehet negatív.',
            ],

            ['purchased_at', 'date', 'format' => 'php:Y-m-d'],

            ['category_id', 'exist',
                'targetClass' => Category::class,
                'targetAttribute' => 'id',
                'message' => 'A megadott kategória nem létezik.',
            ],
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
            'status' => 'Státusz',
            'purchased_at' => 'Beszerzés dátuma',
            'deposit' => 'Letét',
            'created_at' => 'Létrehozva',
            'updated_at' => 'Módosítva',
        ];
    }

    /**
     * Human-readable status names for dropdowns and grid columns.
     *
     * @return array status code => label
     */
    public static function statusLabels()
    {
        return [
            self::STATUS_AVAILABLE => 'Elérhető',
            self::STATUS_LOANED => 'Kiadva',
            self::STATUS_MAINTENANCE => 'Karbantartás',
            self::STATUS_SCRAPPED => 'Selejt',
        ];
    }

    public function getStatusLabel()
    {
        return self::statusLabels()[$this->status] ?? 'Ismeretlen';
    }

    /**
     * Whether the item can be loaned out based on its status (BR-1).
     * The open-loan check itself belongs to the loan engine (BR-2).
     */
    public function isAvailable()
    {
        return (int) $this->status === self::STATUS_AVAILABLE;
    }

    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    public function getLoans()
    {
        return $this->hasMany(Loan::class, ['equipment_id' => 'id']);
    }

    /**
     * Equipment that has ever been loaned is scrapped instead of deleted (BR-8).
     *
     * {@inheritdoc}
     */
    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        if ($this->getLoans()->exists()) {
            $this->status = self::STATUS_SCRAPPED;
            $this->save(false, ['status', 'updated_at']);

            Yii::$app->session->setFlash(
                'warning',
                'Az eszközt korábban már kölcsönözték, ezért nem törölhető — selejt státuszba került.'
            );

            return false;
        }

        return true;
    }
}