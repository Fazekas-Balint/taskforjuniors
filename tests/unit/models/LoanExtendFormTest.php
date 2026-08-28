<?php

namespace tests\unit\models;

use app\models\LoanExtendForm;
use DbSeeder;

/**
 * A hosszabbítás űrlapja: pontosan 7 nap, összesen legfeljebb 30 nap (SZ-7).
 */
class LoanExtendFormTest extends \Codeception\Test\Unit
{
    /** @var \UnitTester */
    public $tester;

    /** Hosszabbítható kölcsönzés: 2 napja adták ki, a határidő 5 nap múlva jár le. */
    private function urlap(array $attributes = []): LoanExtendForm
    {
        return new LoanExtendForm(array_merge([
            'loaned_at' => DbSeeder::day(-2),
            'current_due_at' => DbSeeder::day(5),
            'due_at' => DbSeeder::day(12),
        ], $attributes));
    }

    public function testUjHataridoKotelezo()
    {
        $model = $this->urlap(['due_at' => null]);

        verify($model->validate())->false();
        verify($model->getErrors())->arrayHasKey('due_at');
    }

    public function testPontosanHetNapposHosszabbitasElfogadhato()
    {
        $model = $this->urlap();

        verify($model->validate())->true();
        verify($model->getErrors())->empty();
    }

    public function testHetNaptolElteroHosszabbitastElutasit()
    {
        $model = $this->urlap(['due_at' => DbSeeder::day(8)]);

        verify($model->validate())->false();
        verify($model->getFirstError('due_at'))->equals('A határidő pontosan 7 nappal hosszabbítható meg.');
    }

    public function testHarmincNaposTeljesHosszonTulNemHosszabbithato()
    {
        // 27 napja adták ki, a határidő holnap jár le: +7 nap már túllépné a 30 napot.
        $model = $this->urlap([
            'loaned_at' => DbSeeder::day(-27),
            'current_due_at' => DbSeeder::day(1),
            'due_at' => DbSeeder::day(8),
        ]);

        verify($model->validate())->false();
        verify($model->getFirstError('due_at'))->equals('A kölcsönzés teljes hossza legfeljebb 30 nap lehet.');
    }

    public function testErvenytelenDatumformatumotElutasit()
    {
        $model = $this->urlap(['due_at' => '2026.09.14.']);

        verify($model->validate())->false();
        verify($model->getErrors())->arrayHasKey('due_at');
    }

    public function testHianyzoKiindulasiAdatoknalNemDobKivetelt()
    {
        // A controller mindig kitölti ezeket, de az űrlap önmagában sem hasalhat el.
        $model = new LoanExtendForm(['due_at' => DbSeeder::day(7)]);

        verify($model->validate())->true();
    }
}
