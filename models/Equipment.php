<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

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
    public const STATUS_AVAILABLE = 0;   // available
    public const STATUS_LOANED = 1;      // loaned
    public const STATUS_MAINTENANCE = 2; // maintenance
    public const STATUS_SCRAPPED = 3;    // scrapped


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

    /**
     * {@inheritdoc}
     */
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['description', 'purchased_at', 'updated_at'], 'default', 'value' => null],
            [['deposit'], 'default', 'value' => 0],
            [['status'], 'default', 'value' => self::STATUS_AVAILABLE],

            [['category_id', 'inventory_no', 'name'], 'required'],

            [['category_id'], 'integer'],
            [['description'], 'string'],
            [['name'], 'string', 'max' => 150],
            [['inventory_no'], 'string', 'max' => 20],

            [['inventory_no'], 'match',
                'pattern' => '/^[A-Z]{2}-\d{4}$/',
                'message' => 'A leltári szám formátuma: két nagybetű, kötőjel, négy számjegy (pl. LP-0007).',
            ],
            [['inventory_no'], 'unique',
                'message' => 'Ez a leltári szám már foglalt.',
            ],

            [['status'], 'in',
                'range' => array_keys(self::statusLabels()),
                'message' => 'Érvénytelen státusz.',
            ],

            [['deposit'], 'integer',
                'min' => 0,
                'tooSmall' => 'A letét nem lehet negatív.',
            ],

            [['purchased_at'], 'date', 'format' => 'php:Y-m-d'],
            [['created_at', 'updated_at'], 'safe'],

            [['category_id'], 'exist', 'skipOnError' => true,
                'targetClass' => Category::class,
                'targetAttribute' => ['category_id' => 'id'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => 'Kategória',
            'inventory_no' => 'Leltári szám',
            'name' => 'Név',
            'description' => 'Leírás',
            'status' => 'Státusz',
            'purchased_at' => 'Beszerzés dátuma',
            'deposit' => 'Letét',
            'created_at' => 'Létrehozva',
            'updated_at' => 'Frissítve',
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

    /**
     * Human-readable status names for dropdowns and grid columns.
     *
     * @return array status code => label
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Elérhető',
            self::STATUS_LOANED => 'Kiadva',
            self::STATUS_MAINTENANCE => 'Karbantartás',
            self::STATUS_SCRAPPED => 'Selejt',
        ];
    }

    /**
     * The status label of this particular item.
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? 'Ismeretlen';
    }

    /**
     * Whether the item can be loaned out based on its status (BR-1).
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }


}
