<?php

use app\models\Equipment;

/**
 * Az eszközlista és az eszköz adatlap végponttól végpontig.
 *
 * A raktáras tesztek az SPRT-3606 elfogadási kritériumait ellenőrzik (látni és
 * kezelni, melyik eszköz melyik raktárban van), a leltári számos tesztek az
 * SPRT-3605 második bejelentését.
 */
class EquipmentCest
{
    public function _before(\FunctionalTester $I)
    {
        // Az eszköz törzsadat szerkesztő jogot igényel.
        $I->amLoggedInAs(\app\models\User::findByUsername('admin'));
    }

    public function aRaktarOszloponkentLatszikAListaban(\FunctionalTester $I)
    {
        $equipment = DbSeeder::equipment(['storage_location' => 'Raktár 2']);

        $I->amOnRoute('equipment/index', ['q' => $equipment->inventory_no]);

        $I->seeElement('a', ['data-sort' => 'storage_location']);
        $I->see($equipment->inventory_no);
        $I->see('Raktár 2');
    }

    public function aListaSzurhetoRaktarra(\FunctionalTester $I)
    {
        $keresett = DbSeeder::equipment(['storage_location' => 'Raktár 2']);
        $masik = DbSeeder::equipment(['storage_location' => 'Központi']);

        $I->amOnRoute('equipment/index', ['storage_location' => 'Raktár 2']);

        $I->see($keresett->inventory_no);
        $I->dontSee($masik->inventory_no);
    }

    public function aListaRendezhetoRaktarSzerint(\FunctionalTester $I)
    {
        DbSeeder::equipment(['storage_location' => 'Raktár 1']);

        $I->amOnRoute('equipment/index');
        // A fejléc rendező hivatkozása létezik...
        $I->seeElement('a', ['data-sort' => 'storage_location']);

        // ...és a rendezett lista is hiba nélkül betölt.
        $I->amOnRoute('equipment/index', ['sort' => '-storage_location']);
        $I->see('Eszközök', 'h1');
    }

    public function aKategoriaNevereKattintvaAListaArraSzur(\FunctionalTester $I)
    {
        $kategoria = DbSeeder::category();
        $sajat = DbSeeder::equipment(['category_id' => $kategoria->id]);
        $masik = DbSeeder::equipment();

        $I->amOnRoute('equipment/index', ['q' => $sajat->inventory_no]);
        $I->click($kategoria->name);

        $I->see($sajat->inventory_no);
        $I->dontSee($masik->inventory_no);
    }

    public function aKategoriakOldalrolElerhetoekAKategoriaEszkozei(\FunctionalTester $I)
    {
        $kategoria = DbSeeder::category();
        $equipment = DbSeeder::equipment(['category_id' => $kategoria->id]);

        $I->amOnRoute('category/index');
        $I->click($kategoria->name);

        $I->see('Eszközök', 'h1');
        $I->see($equipment->inventory_no);
    }

    public function azAthelyezesAtirjaARaktart(\FunctionalTester $I)
    {
        $equipment = DbSeeder::equipment(['storage_location' => 'Központi']);

        // A keresésre szűkítve pontosan egy sor - és egy áthelyező űrlap - marad.
        $I->amOnRoute('equipment/index', ['q' => $equipment->inventory_no]);
        $I->submitForm('form.d-flex', ['storage_location' => 'Raktár 2']);

        $I->see('átkerült ide: Raktár 2');
        $I->assertEquals('Raktár 2', Equipment::findOne($equipment->id)->storage_location);
    }

    public function azAthelyezesMindenSorbolElerheto(\FunctionalTester $I)
    {
        $equipment = DbSeeder::equipment(['storage_location' => 'Központi']);

        $I->amOnRoute('equipment/index', ['q' => $equipment->inventory_no]);

        $I->seeElement('form.d-flex select[name=storage_location]');
        $I->see('Áthelyez');
    }

    public function azAdatlapMutatjaARaktartEsAKolcsonzesiElozmenyt(\FunctionalTester $I)
    {
        $equipment = DbSeeder::equipment(['storage_location' => 'Raktár 1']);
        $borrower = DbSeeder::borrower();
        DbSeeder::loan([
            'equipment_id' => $equipment->id,
            'borrower_id' => $borrower->id,
            'storage_location' => 'Raktár 1',
        ], -3, 4);

        $I->amOnRoute('equipment/view', ['id' => $equipment->id]);

        $I->see($equipment->inventory_no, 'h1');
        $I->see('Raktár 1');
        $I->see('Kölcsönzési előzmény');
        $I->see($borrower->full_name);
        $I->see('Kint van');
    }

    public function azElozmenyNelkuliEszkozAdatlapjaIsMegnyilik(\FunctionalTester $I)
    {
        $equipment = DbSeeder::equipment();

        $I->amOnRoute('equipment/view', ['id' => $equipment->id]);

        $I->see('Ezt az eszközt még nem kölcsönözték ki.');
    }

    public function aLeltariSzamTetszolegesFormatumraAtirhato(\FunctionalTester $I)
    {
        // SPRT-3605: az ügyfél a "123"-at akarta "1234"-re átírni.
        $equipment = DbSeeder::equipment(['inventory_no' => '123-' . DbSeeder::nextId()]);
        $ujSzam = '1234-' . DbSeeder::nextId();

        $I->amOnRoute('equipment/update', ['id' => $equipment->id]);
        $I->submitForm('#equipment-form', $this->urlapMezok($equipment, ['inventory_no' => $ujSzam]));

        $I->see('Az eszköz módosítva.');
        $I->assertEquals($ujSzam, Equipment::findOne($equipment->id)->inventory_no);
    }

    public function azUresLeltariSzamotElutasitja(\FunctionalTester $I)
    {
        $equipment = DbSeeder::equipment();

        $I->amOnRoute('equipment/update', ['id' => $equipment->id]);
        $I->submitForm('#equipment-form', $this->urlapMezok($equipment, ['inventory_no' => '']));

        $I->see('A leltári szám megadása kötelező.');
        $I->assertEquals($equipment->inventory_no, Equipment::findOne($equipment->id)->inventory_no);
    }

    /**
     * Az eszköz űrlap mezői a rekord jelenlegi értékeivel; a $felulir tömbben
     * csak azt kell megadni, amit a teszt éppen változtat.
     *
     * @return array<string, mixed>
     */
    private function urlapMezok(Equipment $equipment, array $felulir = []): array
    {
        $mezok = [
            'inventory_no' => $equipment->inventory_no,
            'name' => $equipment->name,
            'category_id' => $equipment->category_id,
            'storage_location' => $equipment->storage_location,
            'status' => $equipment->status,
            'deposit' => $equipment->deposit,
        ];

        $urlap = [];
        foreach (array_merge($mezok, $felulir) as $mezo => $ertek) {
            $urlap["Equipment[$mezo]"] = $ertek;
        }

        return $urlap;
    }
}
