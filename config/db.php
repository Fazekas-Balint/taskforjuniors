<?php

// Forrás: milan-deletion branch - config/db.php.
// A kapcsolat innentől MySQL (kolcsonpont adatbázis) a korábbi runtime/equipment.sqlite helyett.
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=kolcsonpont',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',

    // Séma-gyorsítótár: enélkül minden kérés újra lekérdezi a táblák leírását
    // (SHOW CREATE TABLE / SHOW FULL COLUMNS), ami feleslegesen viszi a lekérdezésszámot.
    // Migráció után: php yii cache/flush-schema
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
];
