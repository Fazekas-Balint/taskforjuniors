<?php

use app\models\Loan;

/**
 * A hosszabbítás oldal végponttól végpontig (SPRT-3605).
 *
 * A bejelentés szerint az oldal "Hiba" oldalt írt ki. Ezek a tesztek azt őrzik,
 * hogy a hosszabbítás valóban elvégezhető, a nem hosszabbítható eseteknél pedig
 * érthető üzenet jön, nem hibaoldal.
 */
class ExtendCest
{
    public function _before(\FunctionalTester $I)
    {
        // A hosszabbítás szerkesztő jogot igényel.
        $I->amLoggedInAs(\app\models\User::findByUsername('admin'));
    }

    public function hosszabbitasiListaMegnyilik(\FunctionalTester $I)
    {
        $I->amOnRoute('extend');

        $I->see('Kölcsönzés hosszabbítása');
        $I->dontSee('Hiba', 'h1');
    }

    public function hataridonBeluliKolcsonzesUrlapjaMegnyilik(\FunctionalTester $I)
    {
        $loan = DbSeeder::loan([], -2, 5);

        $I->amOnRoute('extend', ['id' => $loan->id]);

        $I->dontSee('Hiba', 'h1');
        $I->see('Jelenlegi határidő');
        $I->seeElement('form.vstack');
    }

    public function lejartKolcsonzesUzenettelTerVissza(\FunctionalTester $I)
    {
        $loan = DbSeeder::loan([], -10, -3);

        $I->amOnRoute('extend', ['id' => $loan->id]);

        $I->dontSee('Hiba', 'h1');
        $I->see('már késésben van');
        $I->see('Kölcsönzés hosszabbítása');
    }

    public function lezartKolcsonzesNemHosszabbithato(\FunctionalTester $I)
    {
        $loan = DbSeeder::loan(['returned_at' => DbSeeder::day(-1)], -10, 5);

        $I->amOnRoute('extend', ['id' => $loan->id]);

        $I->dontSee('Hiba', 'h1');
        $I->see('már le van zárva');
    }

    public function sikeresHosszabbitasHetNappal(\FunctionalTester $I)
    {
        $loan = DbSeeder::loan([], -2, 5);
        $ujHatarido = DbSeeder::day(12);

        $I->amOnRoute('extend', ['id' => $loan->id]);
        $I->submitForm('form.vstack', ['LoanExtendForm[due_at]' => $ujHatarido]);

        $I->see('A határidő módosítva.');
        $I->assertEquals($ujHatarido, Loan::findOne($loan->id)->due_at);
    }

    public function hetNaptolElteroHosszabbitastElutasit(\FunctionalTester $I)
    {
        $loan = DbSeeder::loan([], -2, 5);
        $eredetiHatarido = $loan->due_at;

        $I->amOnRoute('extend', ['id' => $loan->id]);
        $I->submitForm('form.vstack', ['LoanExtendForm[due_at]' => DbSeeder::day(30)]);

        $I->see('A határidő pontosan 7 nappal hosszabbítható meg.');
        $I->assertEquals($eredetiHatarido, Loan::findOne($loan->id)->due_at);
    }

    public function aRaktarLatszikAHosszabbitasOldalon(\FunctionalTester $I)
    {
        // SPRT-3606: a raktárnak minden kölcsönzési nézetben látszania kell.
        $loan = DbSeeder::loan(['storage_location' => 'Raktár 2'], -2, 5);

        $I->amOnRoute('extend', ['id' => $loan->id]);

        $I->see('Raktár 2');
    }
}
