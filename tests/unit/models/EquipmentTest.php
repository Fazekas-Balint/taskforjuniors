<?php

namespace tests\unit\models;

use app\models\Equipment;
use Codeception\Test\Unit;

/**
 * Unit tests for the equipment model's own rules (lane A).
 *
 * No rows are created or read: these cover pure logic and the validators that
 * can reject a value without asking the database.
 */
class EquipmentTest extends Unit
{
    /**
     * Every status constant must have a label - the `in` validator builds its
     * allowed range from the keys of this array, so a missing entry would
     * silently make a valid status unusable.
     */
    public function testStatusLabelsCoverEveryStatus(): void
    {
        $labels = Equipment::statusLabels();

        $this->assertSame(
            [
                Equipment::STATUS_AVAILABLE,
                Equipment::STATUS_LOANED,
                Equipment::STATUS_MAINTENANCE,
                Equipment::STATUS_SCRAPPED,
            ],
            array_keys($labels)
        );

        $this->assertSame('Elérhető', $labels[Equipment::STATUS_AVAILABLE]);
    }

    /**
     * BR-1: only an available item may be lent out.
     */
    public function testOnlyAvailableItemsAreReportedAsAvailable(): void
    {
        $expectations = [
            Equipment::STATUS_AVAILABLE => true,
            Equipment::STATUS_LOANED => false,
            Equipment::STATUS_MAINTENANCE => false,
            Equipment::STATUS_SCRAPPED => false,
        ];

        foreach ($expectations as $status => $expected) {
            $equipment = new Equipment(['status' => $status]);

            $this->assertSame(
                $expected,
                $equipment->isAvailable(),
                sprintf('A(z) %d státusz rossz elérhetőséget adott.', $status)
            );
        }
    }

    public function testStatusLabelFallsBackForAnUnknownValue(): void
    {
        $equipment = new Equipment(['status' => 99]);

        $this->assertSame('Ismeretlen', $equipment->getStatusLabel());
    }

    /**
     * @dataProvider malformedInventoryNumbers
     */
    public function testMalformedInventoryNumberIsRejected(string $inventoryNo): void
    {
        $equipment = new Equipment(['inventory_no' => $inventoryNo]);

        $equipment->validate(['inventory_no']);

        $this->assertTrue($equipment->hasErrors('inventory_no'));
    }

    public function malformedInventoryNumbers(): array
    {
        return [
            'kisbetűs előtag' => ['lp-0007'],
            'három betű' => ['LPX-0007'],
            'egy betű' => ['L-0007'],
            'három számjegy' => ['LP-007'],
            'öt számjegy' => ['LP-00077'],
            'hiányzó kötőjel' => ['LP0007'],
            'körülötte szöveg' => ['XXLP-0007YY'],
        ];
    }

    /**
     * @dataProvider statusesOutsideTheRange
     */
    public function testStatusOutsideTheKnownRangeIsRejected(int $status): void
    {
        $equipment = new Equipment(['status' => $status]);

        $equipment->validate(['status']);

        $this->assertTrue($equipment->hasErrors('status'));
        $this->assertSame('Érvénytelen státusz.', $equipment->getFirstError('status'));
    }

    public function statusesOutsideTheRange(): array
    {
        return [
            'negatív' => [-1],
            'közvetlenül a tartomány felett' => [4],
            'jóval a tartomány felett' => [99],
        ];
    }

    public function testNegativeDepositIsRejected(): void
    {
        $equipment = new Equipment(['deposit' => -100]);

        $equipment->validate(['deposit']);

        $this->assertTrue($equipment->hasErrors('deposit'));
        $this->assertSame(
            'A letét nem lehet negatív.',
            $equipment->getFirstError('deposit')
        );
    }

    public function testZeroDepositIsAccepted(): void
    {
        $equipment = new Equipment(['deposit' => 0]);

        $equipment->validate(['deposit']);

        $this->assertFalse($equipment->hasErrors('deposit'));
    }
}
