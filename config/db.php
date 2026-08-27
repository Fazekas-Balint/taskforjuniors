<?php

// Forrás: milan-deletion branch - config/db.php.
// A kapcsolat innentől MySQL (kolcsonpont adatbázis) a korábbi runtime/equipment.sqlite helyett.
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=kolcsonpont',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
