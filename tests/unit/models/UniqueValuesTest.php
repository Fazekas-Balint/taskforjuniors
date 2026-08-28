<?php

namespace tests\unit\models;

use app\models\Category;
use app\models\Equipment;
use Codeception\Test\Unit;
use tests\unit\fixtures\CategoryFixture;
use tests\unit\fixtures\EquipmentFixture;

/**
 * The uniqueness rules that need existing rows to check against.
 *
 * These cover the positive side of the inventory number rule as well, which
 * the database-free tests could not reach: a well-formed number lets the
 * unique validator run, and that one queries.
 */
class UniqueValuesTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'categories' => CategoryFixture::class,
            'equipment' => EquipmentFixture::class,
        ];
    }

    public function testATakenInventoryNumberIsRejectedInHungarian(): void
    {
        $equipment = new Equipment([
            'category_id' => 1,
            'inventory_no' => 'LP-0001',
            'name' => 'Másik laptop',
        ]);

        $this->assertFalse($equipment->validate());
        $this->assertSame(
            'Ez a leltári szám már foglalt.',
            $equipment->getFirstError('inventory_no')
        );
    }

    public function testAFreeInventoryNumberIsAccepted(): void
    {
        $equipment = new Equipment([
            'category_id' => 1,
            'inventory_no' => 'LP-9999',
            'name' => 'Új laptop',
        ]);

        $this->assertTrue(
            $equipment->validate(),
            implode(' ', $equipment->getFirstErrors())
        );
    }

    public function testAnUnknownCategoryIsRejected(): void
    {
        $equipment = new Equipment([
            'category_id' => 999,
            'inventory_no' => 'LP-9998',
            'name' => 'Árva eszköz',
        ]);

        $this->assertFalse($equipment->validate());
        $this->assertTrue($equipment->hasErrors('category_id'));
    }

    public function testATakenCategoryNameIsRejected(): void
    {
        $category = new Category([
            'name' => 'Laptopok',
            'slug' => 'valami-egyeb',
        ]);

        $this->assertFalse($category->validate());
        $this->assertSame(
            'Ilyen nevű kategória már létezik.',
            $category->getFirstError('name')
        );
    }

    public function testATakenSlugIsRejected(): void
    {
        $category = new Category([
            'name' => 'Hordozható gépek',
            'slug' => 'laptopok',
        ]);

        $this->assertFalse($category->validate());
        $this->assertSame(
            'Ez az URL-azonosító már foglalt.',
            $category->getFirstError('slug')
        );
    }

    /**
     * The interesting case: the name is new, but the slug generated from it
     * collides with an existing one. "Laptopok!" loses its punctuation and
     * becomes "laptopok", which category 1 already uses.
     *
     * This is why the slug is generated in beforeValidate() and not in
     * beforeSave() - the generated value has to go through the unique rule
     * like a typed one.
     */
    public function testAGeneratedSlugThatCollidesIsRejected(): void
    {
        $category = new Category([
            'name' => 'Laptopok!',
            'slug' => '',
        ]);

        $this->assertFalse($category->validate());
        $this->assertSame('laptopok', $category->slug);
        $this->assertSame(
            'Ez az URL-azonosító már foglalt.',
            $category->getFirstError('slug')
        );
    }
}
