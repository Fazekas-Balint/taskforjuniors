<?php

namespace tests\unit\models;

use app\models\Category;
use Codeception\Test\Unit;

/**
 * Unit tests for the category model's slug generation (lane A).
 *
 * beforeValidate() is called directly instead of validate(), so the unique
 * check never runs and no query is sent.
 */
class CategoryTest extends Unit
{
    /**
     * @dataProvider namesAndExpectedSlugs
     */
    public function testSlugIsDerivedFromTheNameWhenLeftEmpty(string $name, string $expected): void
    {
        $category = new Category(['name' => $name, 'slug' => '']);

        $category->beforeValidate();

        $this->assertSame($expected, $category->slug);
    }

    public function namesAndExpectedSlugs(): array
    {
        return [
            'ékezet nélkül' => ['Projektorok', 'projektorok'],
            'hosszú ő és é' => ['Fényképezőgépek', 'fenykepezogepek'],
            'szóköz kötőjellé' => ['Kézi szerszámok', 'kezi-szerszamok'],
            'nagy kezdőbetűk' => ['Álló Íróasztal', 'allo-iroasztal'],
            'többszörös szóköz' => ['Laptop   táska', 'laptop-taska'],
            'ű és ü' => ['Fűrészek és Üllők', 'fureszek-es-ullok'],
        ];
    }

    /**
     * A slug typed by hand must win over the generated one.
     */
    public function testExistingSlugIsNotOverwritten(): void
    {
        $category = new Category([
            'name' => 'Fényképezőgépek',
            'slug' => 'kameras-cuccok',
        ]);

        $category->beforeValidate();

        $this->assertSame('kameras-cuccok', $category->slug);
    }

    public function testSlugIsAlsoGeneratedWhenItIsNull(): void
    {
        $category = new Category(['name' => 'Szerszámok']);

        $category->beforeValidate();

        $this->assertSame('szerszamok', $category->slug);
    }

    /**
     * The generated slug has to satisfy the model's own format rule, otherwise
     * validation would reject a value the model produced itself.
     *
     * @dataProvider namesAndExpectedSlugs
     */
    public function testGeneratedSlugMatchesTheFormatRule(string $name): void
    {
        $category = new Category(['name' => $name, 'slug' => '']);

        $category->beforeValidate();

        $this->assertMatchesRegularExpression(
            '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            $category->slug
        );
    }
}
