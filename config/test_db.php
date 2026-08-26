<?php

$db = require __DIR__ . '/db.php';
// Keep the test connection aligned with the database configured for this demo.
$db['dsn'] = 'mysql:host=localhost;dbname=keddidb';

return $db;
