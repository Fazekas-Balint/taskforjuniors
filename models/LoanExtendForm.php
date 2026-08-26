<?php

namespace app\models;

use DateTimeImmutable;
use yii\base\Model;

class LoanExtendForm extends Model
{
    public $due_at;
    public $current_due_at;
    public $loaned_at;

    public function rules()
    {
        return [
            ['due_at', 'required'],
            ['due_at', 'date', 'format' => 'php:Y-m-d'],
            ['due_at', 'validateExtension'],
        ];
    }

    public function attributeLabels()
    {
        return ['due_at' => 'Új határidő'];
    }

    public function validateExtension($attribute, $params)
    {
        if (!$this->due_at || !$this->current_due_at || !$this->loaned_at) {
            return;
        }

        $currentDueAt = DateTimeImmutable::createFromFormat('!Y-m-d', $this->current_due_at);
        $loanedAt = DateTimeImmutable::createFromFormat('!Y-m-d', $this->loaned_at);
        $dueAt = DateTimeImmutable::createFromFormat('!Y-m-d', $this->due_at);
        if (!$currentDueAt || !$loanedAt || !$dueAt) {
            return;
        }

        if ($dueAt != $currentDueAt->modify('+7 days')) {
            $this->addError($attribute, 'A határidő pontosan 7 nappal hosszabbítható meg.');
        } elseif ($dueAt > $loanedAt->modify('+30 days')) {
            $this->addError($attribute, 'A kölcsönzés teljes hossza legfeljebb 30 nap lehet.');
        }
    }
}
