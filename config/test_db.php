<?php

// The unit tests only need the table schema - they create and read no rows.
// Reusing the development connection keeps the setup at zero, and the Yii2
// Codeception module wraps every test in a transaction that is rolled back
// afterwards, so the development data is not touched.
//
// Once the duplicate migrations are sorted out, this should point at a
// dedicated `kolcsonpont_test` database instead.
return require __DIR__ . '/db.php';
