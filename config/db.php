<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'sqlite:' . dirname(__DIR__) . '/runtime/equipment.sqlite',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
