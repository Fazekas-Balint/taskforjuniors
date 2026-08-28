<?php

namespace tests\unit\models;

use app\models\Equipment;
use Codeception\Test\Unit;
use tests\unit\fixtures\BorrowerFixture;
use tests\unit\fixtures\CategoryFixture;
use tests\unit\fixtures\EquipmentFixture;
use tests\unit\fixtures\LoanFixture;
use Yii;

/**
 * BR-8, the equipment half: an item that has ever been lent out is scrapped
 * instead of being deleted.
 *
 * The rule is about loan HISTORY, not about the item being out right now -
 * the fixture's only loan is already returned, and the item still may not be
 * deleted.
 */
class EquipmentDeletionTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'categories' => CategoryFixture::class,
            'borrowers' => BorrowerFixture::class,
            'equipment' => EquipmentFixture::class,
            'loans' => LoanFixture::class,
        ];
    }

    public function testEquipmentWithoutLoanHistoryIsDeletedForGood(): void
    {
        $equipment = Equipment::findOne(1);

        $this->assertNotNull($equipment, 'A fixture nem töltődött be.');
        $this->assertSame(1, $equipment->delete());
        $this->assertNull(Equipment::findOne(1));
    }

    public function testEquipmentWithLoanHistoryIsNotDeleted(): void
    {
        $equipment = Equipment::findOne(2);

        $this->assertFalse($equipment->delete());
        $this->assertNotNull(
            Equipment::findOne(2),
            'A sornak meg kell maradnia, csak a státusza változhat.'
        );
    }

    public function testEquipmentWithLoanHistoryBecomesScrapped(): void
    {
        Equipment::findOne(2)->delete();

        $this->assertSame(
            Equipment::STATUS_SCRAPPED,
            Equipment::findOne(2)->status
        );
    }

    /**
     * A second attempt must behave the same way - no exception, no deletion.
     */
    public function testScrappingAnAlreadyScrappedItemIsStillRefused(): void
    {
        Equipment::findOne(2)->delete();

        $this->assertFalse(Equipment::findOne(2)->delete());
        $this->assertNotNull(Equipment::findOne(2));
    }

    /**
     * The item disappears from the public catalogue, because that only lists
     * items with the available status.
     */
    public function testAScrappedItemIsNoLongerAvailable(): void
    {
        Equipment::findOne(2)->delete();

        $this->assertFalse(Equipment::findOne(2)->isAvailable());
    }

    /**
     * The user has to be told why the item did not disappear.
     */
    public function testTheUserIsToldWhyTheItemWasKept(): void
    {
        Yii::$app->session->removeAllFlashes();

        Equipment::findOne(2)->delete();

        $this->assertTrue(Yii::$app->session->hasFlash('warning'));
    }
}
