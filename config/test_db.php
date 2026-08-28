<?php

// Dedicated test database. It has to stay separate from the development one:
// the fixtures delete every row of the tables they touch before loading their
// own, so pointing this at the working database would wipe it.
$db = require __DIR__ . '/db.php';

$db['dsn'] = 'mysql:host=localhost;dbname=kolcsonpont_test';

return $db;
