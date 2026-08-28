<?php

use app\models\Equipment;
use app\models\Loan;

/**
 * Az új kölcsönzés rögzítése végponttól végpontig.
 *
 * A raktáras tesztek az SPRT-3606 elfogadási kritériumait ellenőrzik: a kérelmen
 * meg kell adni a raktárt, alapértéke az eszköz aktuális raktára.
 */
class LoanCest
{
    public function _before(\FunctionalTester $I)
    {
        $I->amLoggedInAs(\app\models\User::findByUsername('admin'));
    }

    public function aNyitottKolcsonzesekListajaMegnyilik(\FunctionalTester $I)
    {
        // A lista korábban elérhetetlen volt: a /loan az űrlapot rendelte meg.
        $equipment = DbSeeder::equipment(['status' => Equipment::STATUS_LOANED]);
        DbSeeder::loan([
            'equipment_id' => $equipment->id,
            'storage_location' => 'Raktár 2',
        ], -2, 5);

        $I->amOnRoute('loan/index');

        $I->see('Nyitott kölcsönzések', 'h1');
        $I->see($equipment->inventory_no);
        $I->see('Raktár 2');
        $I->see('Visszavétel');
        $I->see('Hosszabbítás');
    }

    public function aListarolElerhetoAzUjKolcsonzesUrlapja(\FunctionalTester $I)
    {
        $I->amOnRoute('loan/index');
        $I->click('Új kölcsönzés');

        $I->see('Új kölcsönzés', 'h1');
        $I->seeElement('#loan-form');
    }

    public function aVisszavetelLezarjaAKolcsonzestEsVisszateszARaktarba(\FunctionalTester $I)
    {
        $equipment = DbSeeder::equipment(['status' => Equipment::STATUS_LOANED, 'storage_location' => 'Központi']);
        $loan = DbSeeder::loan([
            'equipment_id' => $equipment->id,
            'storage_location' => 'Raktár 2',
        ], -3, 4);

        $I->sendAjaxPostRequest(\Yii::$app->urlManager->createUrl(['loan/return', 'id' => $loan->id]));

        $lezart = Loan::findOne($loan->id);
        $I->assertNotNull($lezart->returned_at);

        $frissitett = Equipment::findOne($equipment->id);
        $I->assertEquals(Equipment::STATUS_AVAILABLE, (int) $frissitett->status);
        $I->assertEquals('Raktár 2', $frissitett->storage_location);
    }

    public function azUrlapKeriARaktart(\FunctionalTester $I)
    {
        $I->amOnRoute('loan/create');

        $I->see('Új kölcsönzés', 'h1');
        $I->seeElement('#loanform-storage_location');
        $I->see('Raktár 1', '#loanform-storage_location');
    }

    public function azUrlapAzEszkozSajatRaktaratKinaljaFel(\FunctionalTester $I)
    {
        $equipment = DbSeeder::equipment(['storage_location' => 'Raktár 2']);

        $I->amOnRoute('loan/create', ['equipment_id' => $equipment->id]);

        $I->seeOptionIsSelected('#loanform-storage_location', 'Raktár 2');
    }

    public function azUresUrlapKotelezoMezoketJelez(\FunctionalTester $I)
    {
        // A javítás előtt az üres űrlap átment a validáción, és csak később,
        // félrevezető üzenettel hasalt el.
        $I->amOnRoute('loan/create');
        $I->submitForm('#loan-form', []);

        $I->see('A mező kitöltése kötelező.');
        $I->seeElement('#loan-form');
    }

    public function sikeresKolcsonzesRogzitiARaktart(\FunctionalTester $I)
    {
        $equipment = DbSeeder::equipment(['storage_location' => 'Központi']);
        $borrower = DbSeeder::borrower();

        $I->amOnRoute('loan/create');
        $I->submitForm('#loan-form', [
            'LoanForm[equipment_id]' => $equipment->id,
            'LoanForm[borrower_id]' => $borrower->id,
            'LoanForm[storage_location]' => 'Raktár 1',
            'LoanForm[loaned_at]' => DbSeeder::day(0),
            'LoanForm[due_at]' => DbSeeder::day(7),
            'LoanForm[note]' => 'Funkcionális teszt',
        ]);

        $I->see('A kölcsönzés létrejött.');

        $loan = Loan::find()->where(['equipment_id' => $equipment->id, 'returned_at' => null])->one();
        $I->assertNotNull($loan);
        $I->assertEquals('Raktár 1', $loan->storage_location);
        $I->assertEquals(Equipment::STATUS_LOANED, (int) Equipment::findOne($equipment->id)->status);
        $I->assertEquals('Raktár 1', Equipment::findOne($equipment->id)->storage_location);
    }

    public function marKiadottEszkozNemValaszthatoAzUrlapon(\FunctionalTester $I)
    {
        $equipment = DbSeeder::equipment(['status' => Equipment::STATUS_LOANED]);

        $I->amOnRoute('loan/create');

        $I->dontSeeOptionIsSelected('#loanform-equipment_id', $equipment->inventory_no . ' - ' . $equipment->name);
        $I->dontSee($equipment->inventory_no, '#loanform-equipment_id');
    }

    public function raktaronKivuliErteketNemFogadEl(\FunctionalTester $I)
    {
        $equipment = DbSeeder::equipment();
        $borrower = DbSeeder::borrower();

        $I->amOnRoute('loan/create');
        $I->submitForm('#loan-form', [
            'LoanForm[equipment_id]' => $equipment->id,
            'LoanForm[borrower_id]' => $borrower->id,
            'LoanForm[storage_location]' => 'Pince',
            'LoanForm[loaned_at]' => DbSeeder::day(0),
            'LoanForm[due_at]' => DbSeeder::day(7),
        ]);

        $I->see('Válassz a listából raktárat.');
        $I->assertFalse(Loan::find()->where(['equipment_id' => $equipment->id])->exists());
    }
}
