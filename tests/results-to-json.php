<?php

/**
 * A Codeception JUnit XML riportjából emberi szemmel is átnézhető JSON-t készít:
 * suite-onként, azon belül tesztosztályonként csoportosítva.
 *
 * Használat a projekt gyökeréből:
 *
 *     php vendor/bin/codecept run --xml
 *     php tests/results-to-json.php
 *
 * Eredmény: tests/_output/test-results.json
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$outputDir = __DIR__ . '/_output';
$xmlPath = $outputDir . '/report.xml';
$jsonPath = $outputDir . '/test-results.json';

if (!is_file($xmlPath)) {
    fwrite(STDERR, "Nincs riport: $xmlPath\nFuttasd előbb: php vendor/bin/codecept run --xml\n");
    exit(1);
}

$xml = simplexml_load_file($xmlPath);
if ($xml === false) {
    fwrite(STDERR, "A riport nem olvasható: $xmlPath\n");
    exit(1);
}

/** A projekt gyökeréhez képesti, egységes elválasztójelű útvonal. */
$relativUt = static function (string $ut) use ($projectRoot): string {
    $ut = str_replace('\\', '/', $ut);
    $gyoker = str_replace('\\', '/', $projectRoot) . '/';

    return str_starts_with($ut, $gyoker) ? substr($ut, strlen($gyoker)) : $ut;
};

/** Egy testcase csomópont kiolvasása. */
$olvasEset = static function (SimpleXMLElement $case): array {
    $statusz = 'sikeres';
    $uzenet = null;

    foreach (['failure' => 'hibas', 'error' => 'hibara_futott', 'skipped' => 'kihagyott'] as $tag => $statuszNev) {
        if (isset($case->{$tag})) {
            $statusz = $statuszNev;
            $uzenet = trim((string) $case->{$tag}) ?: null;
            break;
        }
    }

    $eset = [
        'nev' => (string) $case['name'],
        'leiras' => (string) $case['feature'],
        'statusz' => $statusz,
        'allitasok' => isset($case['assertions']) ? (int) $case['assertions'] : 0,
        'ido_mp' => round((float) $case['time'], 3),
    ];

    if ($uzenet !== null) {
        $eset['uzenet'] = $uzenet;
    }

    return $eset;
};

$suitek = [];
$osszes = ['tesztek' => 0, 'allitasok' => 0, 'hibas' => 0, 'hibara_futott' => 0, 'kihagyott' => 0, 'ido_mp' => 0.0];

foreach ($xml->testsuite as $suite) {
    // A Codeception egy suite-on belül nem bontja osztályokra a riportot,
    // ezért a testcase class attribútuma alapján csoportosítunk.
    $osztalyok = [];

    foreach ($suite->testcase as $case) {
        $osztaly = (string) $case['class'];

        if (!isset($osztalyok[$osztaly])) {
            $osztalyok[$osztaly] = [
                'osztaly' => $osztaly,
                'fajl' => $relativUt((string) $case['file']),
                'tesztek' => 0,
                'allitasok' => 0,
                'hibas' => 0,
                'hibara_futott' => 0,
                'kihagyott' => 0,
                'ido_mp' => 0.0,
                'esetek' => [],
            ];
        }

        $eset = $olvasEset($case);
        $osztalyok[$osztaly]['tesztek']++;
        $osztalyok[$osztaly]['allitasok'] += $eset['allitasok'];
        $osztalyok[$osztaly]['ido_mp'] += $eset['ido_mp'];
        $osztalyok[$osztaly]['esetek'][] = $eset;

        // A státusz neve egyben a számláló kulcsa is: hibas / hibara_futott / kihagyott.
        if ($eset['statusz'] !== 'sikeres') {
            $osztalyok[$osztaly][$eset['statusz']]++;
        }
    }

    foreach ($osztalyok as $nev => $osztaly) {
        $osztalyok[$nev]['ido_mp'] = round($osztaly['ido_mp'], 3);
    }

    ksort($osztalyok);

    $suitek[] = [
        'suite' => (string) $suite['name'],
        'tesztek' => (int) $suite['tests'],
        'allitasok' => (int) $suite['assertions'],
        'hibas' => (int) $suite['failures'],
        'hibara_futott' => (int) $suite['errors'],
        'kihagyott' => (int) $suite['skipped'],
        'ido_mp' => round((float) $suite['time'], 3),
        'osztalyok' => array_values($osztalyok),
    ];

    $osszes['tesztek'] += (int) $suite['tests'];
    $osszes['allitasok'] += (int) $suite['assertions'];
    $osszes['hibas'] += (int) $suite['failures'];
    $osszes['hibara_futott'] += (int) $suite['errors'];
    $osszes['kihagyott'] += (int) $suite['skipped'];
    $osszes['ido_mp'] += (float) $suite['time'];
}

$osszes['ido_mp'] = round($osszes['ido_mp'], 3);
$osszes['eredmeny'] = ($osszes['hibas'] === 0 && $osszes['hibara_futott'] === 0) ? 'SIKERES' : 'SIKERTELEN';

$riport = [
    'projekt' => 'Eszköztár (taskforjuniors)',
    'keszult' => date('c'),
    'parancs' => 'php vendor/bin/codecept run --xml && php tests/results-to-json.php',
    'osszesites' => $osszes,
    'suitek' => $suitek,
];

file_put_contents(
    $jsonPath,
    json_encode($riport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
);

printf(
    "%s\n  %d teszt, %d állítás, %d hibás, %d hibára futott, %d kihagyott -> %s\n",
    $relativUt($jsonPath),
    $osszes['tesztek'],
    $osszes['allitasok'],
    $osszes['hibas'],
    $osszes['hibara_futott'],
    $osszes['kihagyott'],
    $osszes['eredmeny']
);
