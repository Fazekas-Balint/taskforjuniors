<?php

namespace tests\unit\models;

use app\models\Equipment;
use DbSeeder;

/**
 * Az eszköz törzsadat szabályai. A leltári számra vonatkozó tesztek az SPRT-3605
 * bejelentéshez tartoznak: a formátumkényszer megszűnt, de a kitöltöttségnek és
 * az egyediségnek maradnia kell.
 */
class EquipmentTest extends \Codeception\Test\Unit
{
    /** @var \UnitTester */
    public $tester;

    public function testLeltariSzamKitolteseKotelezo()
    {
        $equipment = DbSeeder::equipment();
        $equipment->inventory_no = '';

        verify($equipment->validate())->false();
        verify($equipment->getFirstError('inventory_no'))->equals('A leltári szám megadása kötelező.');
    }

    public function testLeltariSzamBarmilyenFormatumuLehet()
    {
        // SPRT-3605: az ügyfél "123"-at rögzített, és "1234"-re akarta átírni.
        foreach (['123', '1234', 'raktár/2026-01', str_repeat('x', 200)] as $inventoryNo) {
            $equipment = DbSeeder::equipment();
            $equipment->inventory_no = $inventoryNo;

            verify($equipment->validate())->true();
            verify($equipment->save())->true();
        }
    }

    public function testLeltariSzamEgyediMarad()
    {
        $letezo = DbSeeder::equipment();
        $uj = DbSeeder::equipment();
        $uj->inventory_no = $letezo->inventory_no;

        verify($uj->validate())->false();
        verify($uj->getFirstError('inventory_no'))->equals('Ez a leltári szám már foglalt.');
    }

    public function testRaktarCsakAListabolValaszthato()
    {
        $equipment = DbSeeder::equipment();
        $equipment->storage_location = 'Pince';

        verify($equipment->validate())->false();
        verify($equipment->getFirstError('storage_location'))->equals('Válassz a listából raktárat.');

        $equipment->storage_location = 'Raktár 2';
        verify($equipment->validate())->true();
    }

    public function testUjEszkozAlapertelmezettRaktaratKap()
    {
        $equipment = new Equipment([
            'category_id' => DbSeeder::category()->id,
            'inventory_no' => 'TESZT-ALAP-' . DbSeeder::nextId(),
            'name' => 'Raktár nélkül felvitt eszköz',
        ]);

        verify($equipment->validate())->true();
        verify($equipment->storage_location)->equals(Equipment::STORAGE_LOCATIONS[0]);
    }

    public function testStatuszCimkek()
    {
        $equipment = DbSeeder::equipment(['status' => Equipment::STATUS_LOANED]);
        verify($equipment->statusLabel)->equals('Kiadva');

        $equipment->status = Equipment::STATUS_MAINTENANCE;
        verify($equipment->statusLabel)->equals('Karbantartás');

        // Ismeretlen státusznál sem szabad hibára futni.
        $equipment->status = 99;
        verify($equipment->statusLabel)->equals('Ismeretlen');
    }

    public function testCsakAzElerhetoEszkozKolcsonozheto()
    {
        verify(DbSeeder::equipment(['status' => Equipment::STATUS_AVAILABLE])->isAvailable())->true();
        verify(DbSeeder::equipment(['status' => Equipment::STATUS_LOANED])->isAvailable())->false();
        verify(DbSeeder::equipment(['status' => Equipment::STATUS_MAINTENANCE])->isAvailable())->false();
        verify(DbSeeder::equipment(['status' => Equipment::STATUS_SCRAPPED])->isAvailable())->false();
    }

    public function testRaktarLegorduloErtekkeszlete()
    {
        $options = Equipment::storageLocationOptions();

        verify(array_keys($options))->equals(array_values($options));
        verify($options)->arrayHasKey('Központi');
        verify($options)->arrayHasKey('Raktár 1');
        verify($options)->arrayHasKey('Raktár 2');
        verify(count($options))->equals(count(Equipment::STORAGE_LOCATIONS));
    }
}
