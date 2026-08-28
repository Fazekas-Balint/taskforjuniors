<?php

namespace tests\unit\services;

use app\models\Category;
use app\models\Equipment;
use app\services\EquipmentService;
use DbSeeder;

/**
 * Törlés és selejtezés (BR-8).
 *
 * Ez a projekt legkockázatosabb része: rossz ágon adatot veszítünk. A tesztek
 * azt rögzítik, hogy mikor törlődik ténylegesen egy sor, és mikor kap helyette
 * selejt státuszt.
 */
class EquipmentDeletionTest extends \Codeception\Test\Unit
{
    /** @var \UnitTester */
    public $tester;

    /** @var EquipmentService */
    private $service;

    protected function _before()
    {
        $this->service = new EquipmentService();
    }

    public function testElozmenyNelkuliEszkozTenylegesenTorlodik()
    {
        $equipment = DbSeeder::equipment();

        $eredmeny = $this->service->handleAction([
            'action' => 'delete_equipment',
            'equipment_id' => $equipment->id,
        ]);

        verify($eredmeny['success'])->true();
        verify($eredmeny['message'])->equals('Az eszköz törölve.');
        verify(Equipment::findOne($equipment->id))->null();
    }

    public function testKolcsonzottEszkozTorlesHelyettSelejtLesz()
    {
        $equipment = DbSeeder::equipment();
        // Lezárt kölcsönzés: az eszköz szabad, de van előzménye.
        DbSeeder::loan([
            'equipment_id' => $equipment->id,
            'returned_at' => DbSeeder::day(-1),
        ], -10, -2);

        $eredmeny = $this->service->handleAction([
            'action' => 'delete_equipment',
            'equipment_id' => $equipment->id,
        ]);

        verify($eredmeny['success'])->true();
        verify($eredmeny['message'])
            ->equals('Az eszközt már kölcsönözték, ezért törlés helyett selejt státuszba került.');

        $megvan = Equipment::findOne($equipment->id);
        verify($megvan)->notNull();
        verify((int) $megvan->status)->equals(Equipment::STATUS_SCRAPPED);
    }

    public function testKolcsonzesAlattAlloEszkozNemTorolheto()
    {
        $equipment = DbSeeder::equipment(['status' => Equipment::STATUS_LOANED]);
        DbSeeder::loan(['equipment_id' => $equipment->id]);

        $eredmeny = $this->service->handleAction([
            'action' => 'delete_equipment',
            'equipment_id' => $equipment->id,
        ]);

        verify($eredmeny['success'])->false();
        verify($eredmeny['message'])->equals('Kölcsönzés alatt álló eszköz nem törölhető.');
        verify(Equipment::findOne($equipment->id))->notNull();
    }

    public function testMarSelejtEszkozNemSelejtezhetoUjra()
    {
        $equipment = DbSeeder::equipment(['status' => Equipment::STATUS_SCRAPPED]);
        DbSeeder::loan([
            'equipment_id' => $equipment->id,
            'returned_at' => DbSeeder::day(-1),
        ], -10, -2);

        $eredmeny = $this->service->handleAction([
            'action' => 'delete_equipment',
            'equipment_id' => $equipment->id,
        ]);

        verify($eredmeny['success'])->false();
        verify($eredmeny['message'])->equals('Az eszköz már selejt státuszban van.');
        verify(Equipment::findOne($equipment->id))->notNull();
    }

    public function testNemLetezoEszkozTorleseUzenettelTerVissza()
    {
        $eredmeny = $this->service->handleAction([
            'action' => 'delete_equipment',
            'equipment_id' => 999999,
        ]);

        verify($eredmeny['success'])->false();
        verify($eredmeny['message'])->equals('Az eszköz nem található.');
    }

    public function testUresKategoriaTorolheto()
    {
        $category = DbSeeder::category();

        $eredmeny = $this->service->handleAction([
            'action' => 'delete_category',
            'category_id' => $category->id,
        ]);

        verify($eredmeny['success'])->true();
        verify(Category::findOne($category->id))->null();
    }

    public function testEszkozzelRendelkezoKategoriaNemTorolheto()
    {
        $category = DbSeeder::category();
        DbSeeder::equipment(['category_id' => $category->id]);

        $eredmeny = $this->service->handleAction([
            'action' => 'delete_category',
            'category_id' => $category->id,
        ]);

        verify($eredmeny['success'])->false();
        verify($eredmeny['message'])->equals('A kategória nem törölhető, amíg eszköz tartozik hozzá.');
        verify(Category::findOne($category->id))->notNull();
    }

    public function testNemLetezoKategoriaTorleseUzenettelTerVissza()
    {
        $eredmeny = $this->service->handleAction([
            'action' => 'delete_category',
            'category_id' => 999999,
        ]);

        verify($eredmeny['success'])->false();
        verify($eredmeny['message'])->equals('A kategória nem található.');
    }

    public function testAModellSzintenIsSelejtLeszTorlesHelyett()
    {
        // Ugyanez a szabály az eszköz adatlapjáról indított törlésre is áll.
        $equipment = DbSeeder::equipment();
        DbSeeder::loan([
            'equipment_id' => $equipment->id,
            'returned_at' => DbSeeder::day(-1),
        ], -10, -2);

        verify($equipment->delete())->false();

        $megvan = Equipment::findOne($equipment->id);
        verify($megvan)->notNull();
        verify((int) $megvan->status)->equals(Equipment::STATUS_SCRAPPED);
    }

    public function testElozmenyNelkuliEszkozAModellenKeresztulIsTorolheto()
    {
        $equipment = DbSeeder::equipment();

        verify($equipment->delete())->equals(1);
        verify(Equipment::findOne($equipment->id))->null();
    }
}
