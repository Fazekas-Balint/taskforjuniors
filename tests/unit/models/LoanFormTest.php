<?php

namespace tests\unit\models;

use app\models\Equipment;
use app\models\Loan;
use app\models\LoanForm;
use DbSeeder;

/**
 * Az új kölcsönzés űrlapjának szabályai (SZ-1 ... SZ-5 + raktár).
 */
class LoanFormTest extends \Codeception\Test\Unit
{
    /** @var \UnitTester */
    public $tester;

    /** Kitöltött, érvényes űrlap - a tesztek ebből indulnak ki. */
    private function ervenyesUrlap(array $attributes = []): LoanForm
    {
        return new LoanForm(array_merge([
            'equipment_id' => DbSeeder::equipment()->id,
            'borrower_id' => DbSeeder::borrower()->id,
            'storage_location' => Equipment::STORAGE_LOCATIONS[0],
            'loaned_at' => DbSeeder::day(0),
            'due_at' => DbSeeder::day(7),
        ], $attributes));
    }

    public function testErvenyesUrlapAtmegyAValidacion()
    {
        $model = $this->ervenyesUrlap();

        verify($model->validate())->true();
        verify($model->getErrors())->empty();
    }

    public function testUresUrlaponMindenKotelezoMezotJelez()
    {
        $model = new LoanForm();

        verify($model->validate())->false();
        foreach (['equipment_id', 'borrower_id', 'storage_location', 'loaned_at', 'due_at'] as $attribute) {
            verify($model->getErrors())->arrayHasKey($attribute);
        }
    }

    public function testRaktarCsakAListabolValaszthato()
    {
        $model = $this->ervenyesUrlap(['storage_location' => 'Pince']);

        verify($model->validate())->false();
        verify($model->getFirstError('storage_location'))->equals('Válassz a listából raktárat.');
    }

    public function testHataridonekAKiadasUtanKellLennie()
    {
        $model = $this->ervenyesUrlap(['due_at' => DbSeeder::day(0)]);

        verify($model->validate())->false();
        verify($model->getFirstError('due_at'))->equals('A határidőnek a kiadás dátuma után kell lennie.');
    }

    public function testAKolcsonzesLegfeljebbHarmincNaposLehet()
    {
        $model = $this->ervenyesUrlap(['due_at' => DbSeeder::day(31)]);

        verify($model->validate())->false();
        verify($model->getFirstError('due_at'))->equals('A kölcsönzés hossza legfeljebb 30 nap lehet.');
    }

    public function testKiadasDatumaNemLehetMultbeli()
    {
        $model = $this->ervenyesUrlap(['loaned_at' => DbSeeder::day(-1)]);

        verify($model->validate())->false();
        verify($model->getFirstError('loaned_at'))->equals('A kiadás dátuma nem lehet múltbeli.');
    }

    public function testNemElerhetoEszkozNemAdhatoKi()
    {
        $equipment = DbSeeder::equipment(['status' => Equipment::STATUS_MAINTENANCE]);
        $model = $this->ervenyesUrlap(['equipment_id' => $equipment->id]);

        verify($model->validate())->false();
        verify($model->getFirstError('equipment_id'))
            ->equals('Az eszköz nem elérhető (kiadva, karbantartásban vagy selejtezve).');
    }

    public function testNyitottKolcsonzesuEszkozNemAdhatoKiUjra()
    {
        $equipment = DbSeeder::equipment();
        DbSeeder::loan(['equipment_id' => $equipment->id]);
        $model = $this->ervenyesUrlap(['equipment_id' => $equipment->id]);

        verify($model->validate())->false();
        verify($model->getFirstError('equipment_id'))->equals('Az eszköznek már van nyitott kölcsönzése.');
    }

    public function testHaromNyitottKolcsonzesFelettAKolcsonvevoElutasitva()
    {
        $borrower = DbSeeder::borrower();
        for ($i = 0; $i < Loan::MAX_OPEN_LOANS_PER_BORROWER; $i++) {
            DbSeeder::loan(['borrower_id' => $borrower->id]);
        }
        $model = $this->ervenyesUrlap(['borrower_id' => $borrower->id]);

        verify($model->validate())->false();
        verify($model->getFirstError('borrower_id'))
            ->equals('A kölcsönvevőnek legfeljebb 3 nyitott kölcsönzése lehet.');
    }

    public function testInaktivKolcsonvevoNemKolcsonozhet()
    {
        $borrower = DbSeeder::borrower(['is_active' => 0]);
        $model = $this->ervenyesUrlap(['borrower_id' => $borrower->id]);

        verify($model->validate())->false();
        verify($model->getFirstError('borrower_id'))->equals('A kölcsönvevő inaktív.');
    }

    public function testErvenytelenDatumotElutasit()
    {
        $model = $this->ervenyesUrlap(['due_at' => '2026-02-31']);

        verify($model->validate())->false();
        verify($model->getErrors())->arrayHasKey('due_at');
    }
}
