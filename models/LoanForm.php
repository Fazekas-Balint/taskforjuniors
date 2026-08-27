<?php

namespace app\models;

use DateTimeImmutable;
use yii\base\Model;

class LoanForm extends Model
{
    public $equipment_id;
    public $borrower_id;
    public $loaned_at;
    public $due_at;
    public $note;

    public function rules()
    {
        return [
            [['equipment_id', 'borrower_id', 'loaned_at', 'due_at'], 'validateSz1'],
            ['due_at', 'validateSz2'],
            ['loaned_at', 'validateSz5'],
            [['loaned_at', 'due_at'], 'validateRealDate'],
            ['equipment_id', 'validateSz3'],
            ['borrower_id', 'validateSz4'],
            [['equipment_id', 'borrower_id'], 'integer'],
            [['loaned_at', 'due_at'], 'date', 'format' => 'php:Y-m-d'],
            ['note', 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'equipment_id' => 'Eszköz',
            'borrower_id' => 'Kölcsönvevő',
            'loaned_at' => 'Kiadás dátuma',
            'due_at' => 'Határidő',
            'note' => 'Megjegyzés',
        ];
    }

    public function validateSz1($attribute, $params)
    {
        if ($this->$attribute === null || $this->$attribute === '') {
            $this->addError($attribute, 'A mező kitöltése kötelező.');
        }
    }

    public function validateRealDate($attribute, $params)
    {
        if ($this->$attribute === null || $this->$attribute === '') {
            return;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $this->$attribute);
        $errors = DateTimeImmutable::getLastErrors();
        if (!$date || ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) || $date->format('Y-m-d') !== $this->$attribute) {
            $this->addError($attribute, 'Adj meg érvényes dátumot.');
        }
    }

    public function validateSz2($attribute, $params)
    {
        if (!$this->loaned_at || !$this->due_at) {
            return;
        }

        $loanedAt = DateTimeImmutable::createFromFormat('!Y-m-d', $this->loaned_at);
        $dueAt = DateTimeImmutable::createFromFormat('!Y-m-d', $this->due_at);
        if (!$loanedAt || !$dueAt) {
            return;
        }

        if ($dueAt <= $loanedAt) {
            $this->addError($attribute, 'A határidőnek a kiadás dátuma után kell lennie.');
        } elseif ($loanedAt->diff($dueAt)->days > 30) {
            $this->addError($attribute, 'A kölcsönzés hossza legfeljebb 30 nap lehet.');
        }
    }

    public function validateSz5($attribute, $params)
    {
        if (!$this->loaned_at) {
            return;
        }

        $loanedAt = DateTimeImmutable::createFromFormat('!Y-m-d', $this->loaned_at);
        if ($loanedAt && $loanedAt < new DateTimeImmutable('today')) {
            $this->addError($attribute, 'A kiadás dátuma nem lehet múltbeli.');
        }
    }

    public function validateSz3($attribute, $params)
    {
        if (!$this->equipment_id) {
            return;
        }
        $equipment = Equipment::findOne($this->equipment_id);
        if (!$equipment) {
            $this->addError($attribute, 'A kiválasztott eszköz nem található.');
        } elseif ($equipment->status !== Equipment::STATUS_AVAILABLE) {
            $this->addError($attribute, 'Az eszköz nem elérhető (kiadva, karbantartásban vagy selejtezve).');
        } elseif (Loan::find()->where(['equipment_id' => $equipment->id, 'returned_at' => null])->exists()) {
            $this->addError($attribute, 'Az eszköznek már van nyitott kölcsönzése.');
        }
    }

    public function validateSz4($attribute, $params)
    {
        if (!$this->borrower_id) {
            return;
        }
        $borrower = Borrower::findOne($this->borrower_id);
        if (!$borrower) {
            $this->addError($attribute, 'A kölcsönvevő nem található.');
        } elseif (!$borrower->is_active) {
            $this->addError($attribute, 'A kölcsönvevő inaktív.');
        } elseif (Loan::find()
            ->where(['borrower_id' => $borrower->id, 'returned_at' => null])
            ->count() >= Loan::MAX_OPEN_LOANS_PER_BORROWER) {
            $this->addError($attribute, 'A kölcsönvevőnek legfeljebb 3 nyitott kölcsönzése lehet.');
        }
    }
}
