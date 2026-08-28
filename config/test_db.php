<?php

$db = require __DIR__ . '/db.php';

// A tesztek külön adatbázison futnak, hogy a fejlesztői adatokat ne írják felül.
// Séma feltöltése: php yii migrate --db=dbTest
//
// A kapcsolat adatai környezeti változóval felülírhatók, hogy CI-ban (ahol a
// MySQL külön szolgáltatásként fut) ne kelljen a konfigurációt átírni.
$host = getenv('DB_HOST') ?: 'localhost';
$name = getenv('DB_NAME') ?: 'kolcsonpont_test';

$db['dsn'] = sprintf('mysql:host=%s;dbname=%s', $host, $name);

if (getenv('DB_USERNAME') !== false) {
    $db['username'] = (string) getenv('DB_USERNAME');
}
if (getenv('DB_PASSWORD') !== false) {
    $db['password'] = (string) getenv('DB_PASSWORD');
}

// A tesztek a sémát minden futáskor újraolvassák, gyorsítótár nélkül.
$db['enableSchemaCache'] = false;

return $db;
