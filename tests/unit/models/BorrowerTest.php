<?php

namespace tests\unit\models;

use app\models\Borrower;
use DbSeeder;

/**
 * Kölcsönvevő törzsadat: kötelező mezők, e-mail formátum és egyediség.
 */
class BorrowerTest extends \Codeception\Test\Unit
{
    /** @var \UnitTester */
    public $tester;

    public function testNevEsEmailKotelezo()
    {
        $borrower = new Borrower();

        verify($borrower->validate())->false();
        verify($borrower->getErrors())->arrayHasKey('full_name');
        verify($borrower->getErrors())->arrayHasKey('email');
    }

    public function testErvenytelenEmailtElutasit()
    {
        foreach (['nemEmail', 'hianyzo@', '@domain.hu', 'ket@@kukac.hu'] as $rossz) {
            $borrower = new Borrower(['full_name' => 'Teszt Elek', 'email' => $rossz]);

            verify($borrower->validate())->false();
            verify($borrower->getErrors())->arrayHasKey('email');
        }
    }

    public function testEmailCimEgyedi()
    {
        $letezo = DbSeeder::borrower();
        $uj = new Borrower(['full_name' => 'Másik Ember', 'email' => $letezo->email]);

        verify($uj->validate())->false();
        verify($uj->getErrors())->arrayHasKey('email');
    }

    public function testErvenyesKolcsonvevoMenthetoTelefonNelkul()
    {
        $borrower = new Borrower([
            'full_name' => 'Teszt Elek',
            'email' => 'teszt.elek.' . DbSeeder::nextId() . '@example.test',
        ]);

        verify($borrower->validate())->true();
        verify($borrower->save())->true();
    }

    public function testTulHosszuNevetElutasit()
    {
        $borrower = new Borrower([
            'full_name' => str_repeat('a', 256),
            'email' => 'hosszu.' . DbSeeder::nextId() . '@example.test',
        ]);

        verify($borrower->validate())->false();
        verify($borrower->getErrors())->arrayHasKey('full_name');
    }

    public function testKolcsonzeseiLekerdezhetok()
    {
        $borrower = DbSeeder::borrower();
        DbSeeder::loan(['borrower_id' => $borrower->id]);
        DbSeeder::loan(['borrower_id' => $borrower->id]);

        verify(count($borrower->loans))->equals(2);
    }
}
