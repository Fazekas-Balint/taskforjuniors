<?php

namespace tests\unit\models;

use app\models\Equipment;
use app\models\Loan;
use DateTimeImmutable;
use DbSeeder;

/**
 * A kölcsönzés dátum- és díjszámítása.
 *
 * Az első teszt az SPRT-3605 bejelentés regressziós védelme: a modell korábban
 * betöltéskor kijelzési formátumra írta át a határidőt, amitől a hosszabbítás
 * oldala 500-as hibára futott, a késésjelzés pedig csendben hibás lett.
 */
class LoanTest extends \Codeception\Test\Unit
{
    /** @var \UnitTester */
    public $tester;

    public function testHataridoBetolteskorIsSzamolhatoDatumMarad()
    {
        $loan = DbSeeder::loan([], -3, 5);

        $reloaded = Loan::findOne($loan->id);

        verify($reloaded->due_at)->equals(DbSeeder::day(5));
        verify((new DateTimeImmutable($reloaded->due_at))->modify('+7 days')->format('Y-m-d'))
            ->equals(DbSeeder::day(12));
    }

    public function testLejartKolcsonzestFelismeri()
    {
        $loan = DbSeeder::loan([], -10, -3);

        verify($loan->isOpen())->true();
        verify($loan->isOverdue())->true();
        verify($loan->getOverdueDays())->equals(3);
    }

    public function testHataridonBeluliKolcsonzesNemLejart()
    {
        $loan = DbSeeder::loan([], -1, 5);

        verify($loan->isOverdue())->false();
        verify($loan->getOverdueDays())->equals(0);
    }

    public function testLezartKolcsonzesMarNemLejart()
    {
        $loan = DbSeeder::loan(['returned_at' => DbSeeder::day(-1)], -10, -3);

        verify($loan->isOpen())->false();
        verify($loan->isOverdue())->false();
    }

    public function testKesedelmiDijLetetPluszNapiDij()
    {
        $equipment = DbSeeder::equipment(['deposit' => 2000]);
        $loan = DbSeeder::loan(['equipment_id' => $equipment->id], -10, -3);

        // 2000 Ft letét + 3 nap x 500 Ft
        verify($loan->getLateFee())->equals(3500);
    }

    public function testKesedelmiDijLetetNelkuliEszkoznel()
    {
        $equipment = DbSeeder::equipment(['deposit' => 0]);
        $loan = DbSeeder::loan(['equipment_id' => $equipment->id], -10, -4);

        verify($loan->getLateFee())->equals(4 * Loan::DAILY_LATE_FEE);
    }

    public function testHataridonBeluliKolcsonzesnelNincsDij()
    {
        $equipment = DbSeeder::equipment(['deposit' => 5000]);
        $loan = DbSeeder::loan(['equipment_id' => $equipment->id], 0, 7);

        // Késés nélkül nincs díj: a letét önmagában nem büntetés.
        verify($loan->getOverdueDays())->equals(0);
        verify($loan->getLateFee())->equals(0);
    }

    public function testRaktarKotelezoEsCsakAListabolValaszthato()
    {
        $loan = new Loan([
            'equipment_id' => DbSeeder::equipment()->id,
            'borrower_id' => DbSeeder::borrower()->id,
            'loaned_at' => DbSeeder::day(0),
            'due_at' => DbSeeder::day(7),
        ]);

        verify($loan->validate())->false();
        verify($loan->getErrors())->arrayHasKey('storage_location');

        $loan->storage_location = 'Pince';
        verify($loan->validate())->false();

        $loan->storage_location = Equipment::STORAGE_LOCATIONS[1];
        verify($loan->validate())->true();
    }

    public function testEszkozEsKolcsonvevoKapcsolat()
    {
        $equipment = DbSeeder::equipment();
        $borrower = DbSeeder::borrower();
        $loan = DbSeeder::loan(['equipment_id' => $equipment->id, 'borrower_id' => $borrower->id]);

        verify($loan->equipment->id)->equals($equipment->id);
        verify($loan->equipment->inventory_no)->equals($equipment->inventory_no);
        verify($loan->borrower->id)->equals($borrower->id);
        verify($loan->borrower->full_name)->equals($borrower->full_name);
    }
}
