<?php

namespace tests\unit\fixtures;

use app\models\Category;
use yii\test\ActiveFixture;

class CategoryFixture extends ActiveFixture
{
    public $modelClass = Category::class;

    public $dataFile = '@tests/_data/category.php';
}
