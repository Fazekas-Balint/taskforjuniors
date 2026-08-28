<?php

/**
 * Késés-riport és CSV export.
 *
 * A díjszámítás a megrendelő kérése szerint: letét + 500 Ft minden késési napra.
 * A raktár oszlop az SPRT-3606 elfogadási kritériuma.
 */
class ReportCest
{
    public function _before(\FunctionalTester $I)
    {
        $I->amLoggedInAs(\app\models\User::findByUsername('admin'));
    }

    public function aRiportMegnyilikEsMutatjaARaktart(\FunctionalTester $I)
    {
        $equipment = DbSeeder::equipment(['deposit' => 0]);
        DbSeeder::loan([
            'equipment_id' => $equipment->id,
            'storage_location' => 'Raktár 2',
        ], -10, -3);

        $I->amOnRoute('report/overdue');

        $I->see('Késés-riport', 'h1');
        $I->see('Raktár');
        $I->see($equipment->inventory_no);
        $I->see('Raktár 2');
    }

    public function aKesedelmiDijLetetPluszNapiDij(\FunctionalTester $I)
    {
        $equipment = DbSeeder::equipment(['deposit' => 2000]);
        DbSeeder::loan(['equipment_id' => $equipment->id], -10, -3);

        $I->amOnRoute('report/overdue');

        // 2000 Ft letét + 3 nap x 500 Ft
        $I->see('3 nap');
        $I->see(number_format(3500, 0, ',', ' ') . ' Ft');
    }

    public function aSzamitasSzabalyaKiVanIrva(\FunctionalTester $I)
    {
        $I->amOnRoute('report/overdue');

        $I->see('Számítás: letét + 500 Ft / késési nap');
    }

    public function hataridonBeluliTetelNemJelenikMeg(\FunctionalTester $I)
    {
        $equipment = DbSeeder::equipment();
        DbSeeder::loan(['equipment_id' => $equipment->id], -1, 5);

        $I->amOnRoute('report/overdue');

        $I->dontSee($equipment->inventory_no);
    }

    public function aRiportSzurhetoKolcsonvevore(\FunctionalTester $I)
    {
        $sajatEszkoz = DbSeeder::equipment();
        $sajatKolcsonvevo = DbSeeder::borrower();
        DbSeeder::loan([
            'equipment_id' => $sajatEszkoz->id,
            'borrower_id' => $sajatKolcsonvevo->id,
        ], -10, -3);

        $masEszkoz = DbSeeder::equipment();
        DbSeeder::loan(['equipment_id' => $masEszkoz->id], -10, -4);

        $I->amOnRoute('report/overdue', ['lender_id' => $sajatKolcsonvevo->id]);

        $I->see($sajatEszkoz->inventory_no);
        $I->dontSee($masEszkoz->inventory_no);
    }

    public function aCsvExportTartalmazzaARaktarOszlopot(\FunctionalTester $I)
    {
        $equipment = DbSeeder::equipment(['deposit' => 1000]);
        DbSeeder::loan([
            'equipment_id' => $equipment->id,
            'storage_location' => 'Raktár 1',
        ], -10, -2);

        $I->amOnRoute('report/export');

        $csv = $I->grabPageSource();

        $I->assertStringContainsString('Raktár', $csv);
        $I->assertStringContainsString($equipment->inventory_no, $csv);
        $I->assertStringContainsString('Raktár 1', $csv);
        // 1000 Ft letét + 2 nap x 500 Ft, elválasztó nélkül a nyers CSV-ben
        $I->assertStringContainsString(';2;1000;2000', $csv);
    }
}
