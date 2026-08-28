<?php

namespace tests\unit\models;

use app\models\Category;
use Codeception\Test\Unit;
use tests\unit\fixtures\CategoryFixture;
use tests\unit\fixtures\EquipmentFixture;
use Yii;

/**
 * BR-8, the category half: a category may not be deleted while equipment is
 * still assigned to it.
 */
class CategoryDeletionTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'categories' => CategoryFixture::class,
            'equipment' => EquipmentFixture::class,
        ];
    }

    public function testAnEmptyCategoryIsDeleted(): void
    {
        $category = Category::findOne(3);

        $this->assertNotNull($category, 'A fixture nem töltődött be.');
        $this->assertSame(0, (int) $category->getEquipments()->count());
        $this->assertSame(1, $category->delete());
        $this->assertNull(Category::findOne(3));
    }

    public function testACategoryWithEquipmentIsNotDeleted(): void
    {
        $category = Category::findOne(1);

        $this->assertFalse($category->delete());
        $this->assertNotNull(Category::findOne(1));
    }

    public function testTheEquipmentSurvivesTheRefusedDeletion(): void
    {
        Category::findOne(1)->delete();

        $this->assertSame(
            2,
            (int) Category::findOne(1)->getEquipments()->count(),
            'A kategória eszközeihez nem szabad hozzányúlni.'
        );
    }

    public function testTheUserIsToldWhyTheCategoryWasKept(): void
    {
        Yii::$app->session->removeAllFlashes();

        Category::findOne(1)->delete();

        $this->assertTrue(Yii::$app->session->hasFlash('error'));
    }
}
