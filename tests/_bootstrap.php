<?php

define('YII_ENV', 'test');
defined('YII_DEBUG') or define('YII_DEBUG', true);

require_once __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
require __DIR__ .'/../vendor/autoload.php';

// Codeception loads test files by path, but fixture classes are referenced by
// class name - this alias lets Yii's autoloader find them.
Yii::setAlias('@tests', __DIR__);
