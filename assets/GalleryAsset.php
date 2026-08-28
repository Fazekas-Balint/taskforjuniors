<?php

namespace app\assets;

use yii\web\AssetBundle;

/**
 * A bemutatkozó oldal képgalériája.
 *
 * A képek az assets/img mappában vannak, ami nincs a webgyökérben, ezért a Yii
 * publikálja őket a web/assets alá. A megjelenítéshez a bundle baseUrl-je adja
 * az elérési utat, így új kép felvételéhez elég a mappába bemásolni.
 */
class GalleryAsset extends AssetBundle
{
    public $sourcePath = '@app/assets/img';

    /** A galériában megjelenített képek kiterjesztései. */
    public const KEP_KITERJESZTESEK = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * A mappa képei növekvő sorrendben, csak a fájlnevekkel.
     *
     * A természetes rendezés miatt a 2.jpg a 10.jpg elé kerül.
     *
     * @return string[]
     */
    public static function kepek(): array
    {
        $minta = \Yii::getAlias('@app/assets/img')
            . '/*.{' . implode(',', self::KEP_KITERJESZTESEK) . '}';

        $fajlok = array_map('basename', glob($minta, GLOB_BRACE) ?: []);
        natsort($fajlok);

        return array_values($fajlok);
    }
}
