<?php

namespace tests\unit\models;

use app\models\Category;
use DbSeeder;

/**
 * Kategória törzsadat: a név és az URL-azonosító egyedisége, a slug formátuma.
 */
class CategoryTest extends \Codeception\Test\Unit
{
    /** @var \UnitTester */
    public $tester;

    public function testNevEsUrlAzonositoKotelezo()
    {
        $category = new Category();

        verify($category->validate())->false();
        verify($category->getFirstError('name'))->equals('Ez a mező nem lehet üres.');
        verify($category->getFirstError('slug'))->equals('Ez a mező nem lehet üres.');
    }

    public function testSlugCsakKisbetutSzamotEsKotojeletTartalmazhat()
    {
        foreach (['Nagybetűs', 'ekezetes-szo-á', 'ket szo', 'kotojel--dupla', '-kezdo'] as $rossz) {
            $category = new Category(['name' => 'Teszt ' . DbSeeder::nextId(), 'slug' => $rossz]);

            verify($category->validate())->false();
            verify($category->getFirstError('slug'))
                ->equals('Az URL-azonosító csak kisbetűt, számot és kötőjelet tartalmazhat.');
        }
    }

    public function testErvenyesSlugotElfogad()
    {
        $category = new Category([
            'name' => 'Teszt kategória ' . DbSeeder::nextId(),
            'slug' => 'halozati-eszkozok-2026',
        ]);

        verify($category->validate())->true();
    }

    public function testAzonosNevuKategoriaNemVeheteFel()
    {
        $letezo = DbSeeder::category();
        $uj = new Category(['name' => $letezo->name, 'slug' => 'mas-slug-' . DbSeeder::nextId()]);

        verify($uj->validate())->false();
        verify($uj->getFirstError('name'))->equals('Ilyen nevű kategória már létezik.');
    }

    public function testAzonosSlugNemVeheteFel()
    {
        $letezo = DbSeeder::category();
        $uj = new Category(['name' => 'Más név ' . DbSeeder::nextId(), 'slug' => $letezo->slug]);

        verify($uj->validate())->false();
        verify($uj->getFirstError('slug'))->equals('Ez az URL-azonosító már foglalt.');
    }

    public function testLetrehozasIdopontjaAutomatikusanKitoltodik()
    {
        $category = DbSeeder::category();

        verify($category->created_at)->notEmpty();
        verify(Category::findOne($category->id)->created_at)->notEmpty();
    }

    public function testKategoriaEszkozeiLekerdezhetok()
    {
        $category = DbSeeder::category();
        DbSeeder::equipment(['category_id' => $category->id]);
        DbSeeder::equipment(['category_id' => $category->id]);

        verify(count($category->equipments))->equals(2);
    }
}
