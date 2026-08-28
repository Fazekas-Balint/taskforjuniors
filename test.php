<?php

/**
 * Tesztek futtatása egyetlen paranccsal.
 *
 *     php test.php              # minden teszt
 *     php test.php unit         # csak a unit tesztek
 *     php test.php functional   # csak a funkcionális tesztek
 *
 * A futás végén elkészül a géppel és kézzel is olvasható összefoglaló:
 * tests/_output/test-results.json
 *
 * Előfeltétel: a teszt-adatbázis séma naprakész (php yii migrate --db=dbTest).
 */

declare(strict_types=1);

$root = __DIR__;
$suite = $argv[1] ?? '';

$codecept = $root . '/vendor/bin/codecept';
if (!is_file($codecept)) {
    fwrite(STDERR, "Nincs telepítve a Codeception. Futtasd előbb: composer install\n");
    exit(1);
}

$futtat = static function (string $script, array $args = []) : int {
    $parancs = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script);
    foreach ($args as $arg) {
        $parancs .= ' ' . escapeshellarg($arg);
    }

    passthru($parancs, $kod);

    return $kod;
};

echo '» Tesztek futtatása' . ($suite !== '' ? " – $suite" : ' – minden suite') . "\n\n";

$argumentumok = ['run'];
if ($suite !== '') {
    $argumentumok[] = $suite;
}
$argumentumok[] = '--xml';

$tesztKod = $futtat($codecept, $argumentumok);

echo "\n» Összefoglaló\n";
$riportKod = $futtat($root . '/tests/results-to-json.php');

if ($tesztKod !== 0) {
    echo "\nA tesztek elhasaltak. A részletek a fenti kimenetben és a JSON fájlban is megvannak.\n";
}

exit($tesztKod !== 0 ? $tesztKod : $riportKod);
