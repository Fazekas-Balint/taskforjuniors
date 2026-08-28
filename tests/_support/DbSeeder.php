<?php

use app\models\Borrower;
use app\models\Category;
use app\models\Equipment;
use app\models\Loan;

/**
 * Tesztadatok előállítása.
 *
 * A Codeception Yii2 modulja minden tesztet tranzakcióba csomagol és a végén
 * visszagörgeti, ezért az itt beszúrt sorok nem maradnak az adatbázisban.
 * Az egyedi mezők egy futáson belül növekvő sorszámot kapnak, hogy több
 * rekord se ütközzön egymással.
 */
class DbSeeder
{
    /** @var int egyediséget biztosító sorszám a leltári számokhoz, slugokhoz, e-mailekhez */
    private static $counter = 0;

    public static function nextId(): int
    {
        return ++self::$counter;
    }

    public static function category(array $attributes = []): Category
    {
        $n = self::nextId();
        $category = new Category(array_merge([
            'name' => 'Teszt kategória ' . $n,
            'slug' => 'teszt-kategoria-' . $n,
        ], $attributes));

        self::persist($category);

        return $category;
    }

    public static function equipment(array $attributes = []): Equipment
    {
        $n = self::nextId();
        if (!isset($attributes['category_id'])) {
            $attributes['category_id'] = self::category()->id;
        }

        $equipment = new Equipment(array_merge([
            'inventory_no' => 'TESZT-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'name' => 'Teszt eszköz ' . $n,
            'storage_location' => Equipment::STORAGE_LOCATIONS[0],
            'status' => Equipment::STATUS_AVAILABLE,
            'deposit' => 0,
        ], $attributes));

        self::persist($equipment);

        return $equipment;
    }

    public static function borrower(array $attributes = []): Borrower
    {
        $n = self::nextId();
        $borrower = new Borrower(array_merge([
            'full_name' => 'Teszt Kölcsönvevő ' . $n,
            'email' => 'teszt' . $n . '@example.test',
            'is_active' => 1,
        ], $attributes));

        self::persist($borrower);

        return $borrower;
    }

    /**
     * Kölcsönzés. A dátumok napokban, a mai naphoz képest értendők:
     * loanedDays = -3 a három nappal ezelőtti kiadás, dueDays = -1 a tegnapi határidő.
     */
    public static function loan(array $attributes = [], int $loanedDays = 0, int $dueDays = 7): Loan
    {
        if (!isset($attributes['equipment_id'])) {
            $attributes['equipment_id'] = self::equipment()->id;
        }
        if (!isset($attributes['borrower_id'])) {
            $attributes['borrower_id'] = self::borrower()->id;
        }

        $loan = new Loan(array_merge([
            'storage_location' => Equipment::STORAGE_LOCATIONS[0],
            'loaned_at' => self::day($loanedDays),
            'due_at' => self::day($dueDays),
            'created_at' => date('Y-m-d H:i:s'),
        ], $attributes));

        // save(false): a múltbeli dátumú tesztadatokat nem az űrlapszabályok szerint visszük fel.
        if (!$loan->save(false)) {
            throw new RuntimeException('A teszt kölcsönzés mentése sikertelen.');
        }

        return $loan;
    }

    /** A mai naphoz képest eltolt dátum Y-m-d formában. */
    public static function day(int $offsetDays): string
    {
        return date('Y-m-d', strtotime($offsetDays . ' days'));
    }

    private static function persist(\yii\db\ActiveRecord $model): void
    {
        if (!$model->save()) {
            throw new RuntimeException(sprintf(
                'A teszt %s mentése sikertelen: %s',
                get_class($model),
                json_encode($model->getErrors(), JSON_UNESCAPED_UNICODE)
            ));
        }
    }
}
