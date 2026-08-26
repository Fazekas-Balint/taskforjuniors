<?php

namespace app\models;

use DateTimeImmutable;
use yii\db\ActiveRecord;

class Loan extends ActiveRecord
{
    public const DAILY_LATE_FEE = 500;
    public const MAX_OPEN_LOANS_PER_BORROWER = 3;

    public static function tableName()
    {
        return '{{%loan}}';
    }

    public function rules()
    {
        return [
            [['equipment_id', 'borrower_id', 'loaned_at', 'due_at'], 'required'],
            [['equipment_id', 'borrower_id'], 'integer'],
            [['loaned_at', 'due_at', 'returned_at'], 'date', 'format' => 'php:Y-m-d'],
            [['note'], 'string'],
            ['equipment_id', 'exist', 'targetClass' => Equipment::class, 'targetAttribute' => 'id'],
            ['borrower_id', 'exist', 'targetClass' => Borrower::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'Azonosító',
            'equipment_id' => 'Eszköz',
            'borrower_id' => 'Kölcsönvevő',
            'loaned_at' => 'Kiadás dátuma',
            'due_at' => 'Határidő',
            'returned_at' => 'Visszahozás dátuma',
            'note' => 'Megjegyzés',
        ];
    }

    public function getEquipment()
    {
        return $this->hasOne(Equipment::class, ['id' => 'equipment_id']);
    }

    public function getBorrower()
    {
        return $this->hasOne(Borrower::class, ['id' => 'borrower_id']);
    }

    public function isOpen()
    {
        return $this->returned_at === null;
    }

    /**
     * Returns whether this loan is still open and its due date has passed.
     *
     * The optional reference date keeps the API deterministic for reports and tests.
     */
    public function isOverdue($asOf = null)
    {
        if (!$this->isOpen()) {
            return false;
        }

        $reference = $asOf ?: date('Y-m-d');
        return $this->due_at < date('Y-m-d', strtotime($reference));
    }

    public function getOverdueDays($asOf = null)
    {
        if (!$this->due_at) {
            return 0;
        }

        $reference = $asOf ?: ($this->returned_at ?: date('Y-m-d'));
        $due = new DateTimeImmutable($this->due_at);
        $date = new DateTimeImmutable(date('Y-m-d', strtotime($reference)));
        $days = (int) $due->diff($date)->format('%r%a');

        return max(0, $days);
    }

    /**
     * Returns the accumulated late fee in HUF.
     *
     * Closed loans are calculated up to returned_at; open loans are calculated
     * up to today. This makes the value usable both at return time and in reports.
     */
    public function getLateFee($dailyFee = self::DAILY_LATE_FEE)
    {
        $equipment = $this->getEquipment()->one();
        if (!$equipment) {
            return 0;
        }

        return min(
            $this->getOverdueDays() * (int) $dailyFee,
            (int) $equipment->deposit
        );
    }
}
