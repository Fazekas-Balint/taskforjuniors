<?php

namespace tests\unit\services;

use app\models\Equipment;
use app\models\Loan;
use app\services\EquipmentService;
use DbSeeder;

/**
 * A főoldal és a riportok mögötti szolgáltatásréteg.
 *
 * A leltári számos tesztek az SPRT-3605 regressziói: a felvétel korábban a modell
 * validációját megkerülve, nyers SQL-lel mentett. A raktáras tesztek az SPRT-3606
 * elfogadási kritériumait ellenőrzik.
 */
class EquipmentServiceTest extends \Codeception\Test\Unit
{
    /** @var \UnitTester */
    public $tester;

    /** @var EquipmentService */
    private $service;

    protected function _before()
    {
        $this->service = new EquipmentService();
    }

    public function testHianyosAdatokkalNemVeszFelEszkozt()
    {
        $eredmeny = $this->service->handleAction([
            'action' => 'create_equipment',
            'inventory_no' => '',
            'equipment_name' => 'Névtelen',
            'category_id' => DbSeeder::category()->id,
        ]);

        verify($eredmeny['success'])->false();
        verify($eredmeny['message'])->equals('Add meg a leltári számot, az eszköz nevét és a kategóriát.');
    }

    public function testAFelvetelAModellValidaciojanMegyAt()
    {
        // SPRT-3605: a főoldali gyorsfelvétel korábban megkerülte a modell szabályait,
        // így be lehetett vinni olyan leltári számot, amit a szerkesztés már nem fogadott el.
        $letezo = DbSeeder::equipment();

        $eredmeny = $this->service->handleAction([
            'action' => 'create_equipment',
            'inventory_no' => $letezo->inventory_no,
            'equipment_name' => 'Ütköző leltári szám',
            'category_id' => $letezo->category_id,
        ]);

        verify($eredmeny['success'])->false();
        verify($eredmeny['message'])->equals('Ez a leltári szám már foglalt.');
    }

    public function testFelvettEszkozARaktarbaKerul()
    {
        $inventoryNo = 'SZOLG-' . DbSeeder::nextId();

        $eredmeny = $this->service->handleAction([
            'action' => 'create_equipment',
            'inventory_no' => $inventoryNo,
            'equipment_name' => 'Szolgáltatáson át felvett eszköz',
            'category_id' => DbSeeder::category()->id,
            'storage_location' => 'Raktár 2',
            'deposit' => 1500,
        ]);

        verify($eredmeny['success'])->true();

        $equipment = Equipment::findOne(['inventory_no' => $inventoryNo]);
        verify($equipment)->notNull();
        verify($equipment->storage_location)->equals('Raktár 2');
        verify((int) $equipment->deposit)->equals(1500);
        verify((int) $equipment->status)->equals(Equipment::STATUS_AVAILABLE);
    }

    public function testKiadasRaktarMegadasaNelkulElutasitva()
    {
        $equipment = DbSeeder::equipment();
        $borrower = DbSeeder::borrower();

        $hianyzo = $this->service->handleAction([
            'action' => 'lend',
            'equipment_id' => $equipment->id,
            'lender_id' => $borrower->id,
            'starts_on' => DbSeeder::day(0),
            'due_on' => DbSeeder::day(5),
        ]);
        verify($hianyzo['success'])->false();
        verify($hianyzo['message'])->equals('Válassz a listából raktárat.');

        $ervenytelen = $this->service->handleAction([
            'action' => 'lend',
            'equipment_id' => $equipment->id,
            'lender_id' => $borrower->id,
            'starts_on' => DbSeeder::day(0),
            'due_on' => DbSeeder::day(5),
            'storage_location' => 'Pince',
        ]);
        verify($ervenytelen['success'])->false();
        verify($ervenytelen['message'])->equals('Válassz a listából raktárat.');

        verify(Loan::find()->where(['equipment_id' => $equipment->id])->exists())->false();
    }

    public function testKiadasRogzitiARaktartEsKiadottaTesziAzEszkozt()
    {
        $equipment = DbSeeder::equipment(['storage_location' => 'Központi']);
        $borrower = DbSeeder::borrower();

        $eredmeny = $this->service->handleAction([
            'action' => 'lend',
            'equipment_id' => $equipment->id,
            'lender_id' => $borrower->id,
            'starts_on' => DbSeeder::day(0),
            'due_on' => DbSeeder::day(5),
            'storage_location' => 'Raktár 1',
        ]);

        verify($eredmeny['success'])->true();

        $loan = Loan::find()->where(['equipment_id' => $equipment->id, 'returned_at' => null])->one();
        verify($loan)->notNull();
        verify($loan->storage_location)->equals('Raktár 1');

        $frissitett = Equipment::findOne($equipment->id);
        verify((int) $frissitett->status)->equals(Equipment::STATUS_LOANED);
        verify($frissitett->storage_location)->equals('Raktár 1');
    }

    public function testVisszavetelAKiadoRaktarbaTesziVisszaAzEszkozt()
    {
        $equipment = DbSeeder::equipment(['status' => Equipment::STATUS_LOANED]);
        DbSeeder::loan([
            'equipment_id' => $equipment->id,
            'storage_location' => 'Raktár 2',
        ], -3, 4);
        // Közben az eszközt papíron máshova írták át.
        Equipment::updateAll(['storage_location' => 'Központi'], ['id' => $equipment->id]);

        $eredmeny = $this->service->handleAction([
            'action' => 'return',
            'equipment_id' => $equipment->id,
        ]);

        verify($eredmeny['success'])->true();

        $frissitett = Equipment::findOne($equipment->id);
        verify((int) $frissitett->status)->equals(Equipment::STATUS_AVAILABLE);
        verify($frissitett->storage_location)->equals('Raktár 2');
        verify(Loan::find()->where(['equipment_id' => $equipment->id, 'returned_at' => null])->exists())->false();
    }

    public function testLejartRiportKesesiNapokEsDij()
    {
        $equipment = DbSeeder::equipment(['deposit' => 2000, 'status' => Equipment::STATUS_LOANED]);
        $loan = DbSeeder::loan(['equipment_id' => $equipment->id], -10, -3);

        $sajat = null;
        foreach ($this->service->overdueLoans() as $sor) {
            if ((int) $sor['id'] === (int) $loan->id) {
                $sajat = $sor;
            }
        }

        verify($sajat)->notNull();
        verify((int) $sajat['days_late'])->equals(3);
        // letét + 3 nap x 500 Ft
        verify((int) $sajat['late_fee'])->equals(3500);
        verify($sajat['storage_location'])->equals($loan->storage_location);
        verify($sajat['inventory_no'])->equals($equipment->inventory_no);
    }

    public function testHataridonBeluliKolcsonzesNincsALejartRiportban()
    {
        $loan = DbSeeder::loan([], -1, 5);

        $azonositok = array_map('intval', array_column($this->service->overdueLoans(), 'id'));

        verify(in_array((int) $loan->id, $azonositok, true))->false();
    }

    public function testOsszesitettKesedelmiDijNovekszikAzUjLejartTetellel()
    {
        $elotte = $this->service->overdueFee();

        $equipment = DbSeeder::equipment(['deposit' => 1000, 'status' => Equipment::STATUS_LOANED]);
        DbSeeder::loan(['equipment_id' => $equipment->id], -10, -2);

        // 1000 Ft letét + 2 nap x 500 Ft
        verify($this->service->overdueFee())->equals($elotte + 2000);
    }

    public function testEszkozListaTartalmazzaARaktartEsSzurhetoKategoriara()
    {
        $category = DbSeeder::category();
        $equipment = DbSeeder::equipment([
            'category_id' => $category->id,
            'storage_location' => 'Raktár 2',
        ]);

        $sorok = $this->service->equipment('', '', $category->id);

        verify(count($sorok))->equals(1);
        verify($sorok[0]['inventory_no'])->equals($equipment->inventory_no);
        verify($sorok[0]['storage_location'])->equals('Raktár 2');
        verify($sorok[0]['category_name'])->equals($category->name);
    }

    public function testKolcsonzesAlattAlloEszkozNemKuldhetoSzervizbe()
    {
        $equipment = DbSeeder::equipment(['status' => Equipment::STATUS_LOANED]);
        DbSeeder::loan(['equipment_id' => $equipment->id]);

        $eredmeny = $this->service->handleAction([
            'action' => 'maintenance',
            'equipment_id' => $equipment->id,
        ]);

        verify($eredmeny['success'])->false();
        verify((int) Equipment::findOne($equipment->id)->status)->equals(Equipment::STATUS_LOANED);
    }
}
