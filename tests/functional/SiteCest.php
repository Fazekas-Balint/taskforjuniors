<?php

/**
 * A belépési pont: kijelentkezve bemutatkozó oldal, belépés után műszerfal.
 *
 * A szétválasztás nem csak megjelenés kérdése: a műszerfal kölcsönvevő-neveket
 * és e-mail-címeket is mutat, azokat vendég nem láthatja.
 */
class SiteCest
{
    public function vendegkentABemutatkozoOldalFogad(\FunctionalTester $I)
    {
        $I->amOnRoute('site/index');

        $I->see('Egyik eszköz se nem tűnik el többé!', 'h1');
        $I->see('Hogyan működik');
        $I->seeLink('Bejelentkezés');
    }

    public function aBemutatkozoOldalonOttAGaleria(\FunctionalTester $I)
    {
        $kepek = \app\assets\GalleryAsset::kepek();

        $I->amOnRoute('site/index');

        $I->see('Így néz ki');
        $I->seeElement('#galeria');
        $I->seeNumberOfElements('#galeria .carousel-item', count($kepek));
        $I->seeElement('[data-bs-slide=prev]');
        $I->seeElement('[data-bs-slide=next]');
    }

    public function aGaleriaKepeiNovekvoSorrendbenVannak(\FunctionalTester $I)
    {
        $I->amOnRoute('site/index');

        preg_match_all('~/assets/[a-z0-9]+/([^"]+\.(?:jpg|jpeg|png|webp))~i', $I->grabPageSource(), $talalatok);

        // A 2.jpg a 10.jpg elé kerül: természetes, nem szótári sorrend.
        $I->assertSame(\app\assets\GalleryAsset::kepek(), $talalatok[1]);
    }

    public function vendegNemLatjaAMuszerfalAdatait(\FunctionalTester $I)
    {
        $borrower = DbSeeder::borrower();
        DbSeeder::loan(['borrower_id' => $borrower->id]);

        $I->amOnRoute('site/index');

        $I->dontSee('Kinél van?');
        $I->dontSee('Teljes leltár');
        $I->dontSee($borrower->full_name);
        $I->dontSee($borrower->email);
    }

    public function aBemutatkozoOldalFejleceCsakABelepestKinalja(\FunctionalTester $I)
    {
        $I->amOnRoute('site/index');

        $I->dontSeeLink('Katalógus');
        $I->seeLink('Login');
    }

    public function aKatalogusCsakBelepesUtanErhetoEl(\FunctionalTester $I)
    {
        $I->amOnRoute('equipment/catalog');
        $I->see('Login', 'h1');

        $I->amLoggedInAs(\app\models\User::findByUsername('kollega'));
        $I->amOnRoute('equipment/catalog');
        $I->see('Elérhető eszközök', 'h1');
    }

    public function vendegkentMindenBelsoOldalABelepesreIranyit(\FunctionalTester $I)
    {
        $utvonalak = [
            'equipment/index',
            'equipment/catalog',
            'category/index',
            'borrower/index',
            'loan/index',
            'extend/index',
            'report/overdue',
            'site/about',
        ];

        foreach ($utvonalak as $utvonal) {
            $I->amOnRoute($utvonal);
            $I->see('Login', 'h1');
        }
    }

    public function aBelepesiOldalVendegkentIsNyitva(\FunctionalTester $I)
    {
        $I->amOnRoute('site/login');

        $I->see('Login', 'h1');
        $I->seeElement('#login-form');
        $I->dontSeeLink('Katalógus');
    }

    public function aGaleriaKepeiNagyithatoak(\FunctionalTester $I)
    {
        $I->amOnRoute('site/index');

        $I->seeElement('#galeria-nagyito');
        // Alapból rejtve van, csak kattintásra nyílik ki.
        $I->seeElement('#galeria-nagyito[hidden]');
        $I->seeElement('[data-nagyito=bezar]');
        $I->seeElement('[data-nagyito=elozo]');
        $I->seeElement('[data-nagyito=kovetkezo]');
    }

    public function belepesUtanAMuszerfalJelenikMeg(\FunctionalTester $I)
    {
        $I->amLoggedInAs(\app\models\User::findByUsername('admin'));

        $I->amOnRoute('site/index');

        $I->see('Kinél van?');
        $I->see('Teljes leltár');
        $I->dontSee('Hogyan működik');
    }

    public function aKollegaIsAMuszerfaltLatjaSzerkesztesNelkul(\FunctionalTester $I)
    {
        $I->amLoggedInAs(\app\models\User::findByUsername('kollega'));

        $I->amOnRoute('site/index');

        $I->see('Teljes leltár');
        $I->see('Csak megtekintés');
        $I->dontSee('Új eszköz hozzáadása');
    }
}
