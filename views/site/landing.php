<?php

/** @var yii\web\View $this */

use app\assets\GalleryAsset;
use yii\bootstrap5\BootstrapPluginAsset;
use yii\bootstrap5\Html;

$this->title = 'Eszköztár';

// A galéria képei az assets/img mappából, növekvő sorrendben.
$galeria = GalleryAsset::register($this);
BootstrapPluginAsset::register($this);
$kepek = GalleryAsset::kepek();
?>
<div class="landing">

    <?php /* Ugyanazok az osztályok, mint a műszerfal heroján: a kép így nem tud szétcsúszni. */ ?>
    <section class="inventory-hero">
        <div>
            <p class="eyebrow">BELSŐ ESZKÖZTÁR</p>
            <h1>Egyik eszköz se nem tűnik el többé!</h1>
            <p class="hero-copy">Laptopok, projektorok, kamerák és szerszámok egy helyen: ki mit
                vitt el, mikorra ígérte vissza, és mi az, ami épp szabad. Excel helyett.</p>
            <div class="landing-actions">
                <?= Html::a('Bejelentkezés', ['/site/login'], ['class' => 'action-button']) ?>
            </div>
        </div>
        <div class="hero-mark">ASSET<br><strong>ROOM</strong></div>
    </section>

    <section class="landing-cards">
        <article>
            <h3>Leltár egy helyen</h3>
            <p>Minden eszköz leltári számmal, kategóriával és raktári hellyel. Kereshető,
                szűrhető, rendezhető – nem kell körbetelefonálni.</p>
        </article>
        <article>
            <h3>Kiadás és visszavétel</h3>
            <p>A kiadás határidőt kap, a visszavétel egy kattintás. Ami már kint van vagy
                szervizben áll, azt a rendszer nem engedi újra kiadni.</p>
        </article>
        <article>
            <h3>Késés-riport</h3>
            <p>A lejárt kölcsönzések listája késési napokkal és díjjal, szűrhetően és
                CSV-be exportálva – a havi zárás perc kérdése.</p>
        </article>
    </section>

    <?php if ($kepek): ?>
        <section class="landing-gallery">
            <div class="landing-gallery-head">
                <h2>Így néz ki</h2>
                <span id="galeria-szamlalo">1 / <?= count($kepek) ?></span>
            </div>

            <div id="galeria" class="carousel slide" data-bs-ride="false" data-bs-wrap="true">
                <div class="carousel-inner">
                    <?php foreach ($kepek as $index => $kep): ?>
                        <div class="carousel-item<?= $index === 0 ? ' active' : '' ?>">
                            <img src="<?= Html::encode($galeria->baseUrl . '/' . $kep) ?>"
                                 alt="Képernyőkép az Eszköztárból (<?= $index + 1 ?>.)"
                                 <?= $index === 0 ? '' : 'loading="lazy"' ?>>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button class="carousel-control-prev" type="button" data-bs-target="#galeria" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Előző</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#galeria" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Következő</span>
                </button>
            </div>

            <p class="landing-gallery-note">A nyilakkal lapozható, az utolsó után újra az első
                következik. A képre kattintva teljes méretben is megnézhető.</p>
        </section>

        <?php /* Nagyító réteg: a kattintott kép a képernyő közepén, elmosott háttér előtt. */ ?>
        <div id="galeria-nagyito" class="gallery-lightbox" hidden aria-hidden="true"
             role="dialog" aria-modal="true" aria-label="Nagyított képernyőkép">
            <button type="button" class="gallery-lightbox-close" data-nagyito="bezar" aria-label="Bezárás">&times;</button>
            <button type="button" class="gallery-lightbox-prev" data-nagyito="elozo" aria-label="Előző kép">&#8249;</button>
            <img src="" alt="">
            <button type="button" class="gallery-lightbox-next" data-nagyito="kovetkezo" aria-label="Következő kép">&#8250;</button>
            <p class="gallery-lightbox-counter"></p>
        </div>
    <?php endif; ?>

    <section>
        <h2>Hogyan működik</h2>
        <div class="landing-steps">
            <div class="landing-step">
                <span>1. LÉPÉS</span>
                <h3>Megnézed, mi szabad</h3>
                <p>Belépés után a katalógusban csak az épp elérhető eszközök szerepelnek,
                    kategóriánként szűrve.</p>
            </div>
            <div class="landing-step">
                <span>2. LÉPÉS</span>
                <h3>Az irodavezető kiadja</h3>
                <p>Kölcsönvevő, raktár és határidő rögzítésével. Az eszköz azonnal átkerül a
                    „kiadva” állapotba, így másnak már nem ajánlja fel.</p>
            </div>
            <div class="landing-step">
                <span>3. LÉPÉS</span>
                <h3>Visszahozod</h3>
                <p>A visszavétellel a kölcsönzés lezárul, az eszköz pedig ugyanabba a raktárba
                    kerül vissza, ahonnan kiment – és újra foglalható.</p>
            </div>
        </div>
    </section>

    <p class="landing-note">Belépés után a műszerfalon látod, hány eszköz van kint, mi késik,
        és mit várunk vissza ma. <?= Html::a('Bejelentkezés', ['/site/login']) ?></p>

</div>

<?php
// A lapozó számláló frissítése; a Bootstrap carousel magától körbeér.
$this->registerJs(<<<'JS'
var galeria = document.getElementById('galeria');
var szamlalo = document.getElementById('galeria-szamlalo');
if (galeria && szamlalo) {
    var osszes = galeria.querySelectorAll('.carousel-item').length;
    galeria.addEventListener('slid.bs.carousel', function (esemeny) {
        szamlalo.textContent = (esemeny.to + 1) + ' / ' + osszes;
    });
}
JS);

// Nagyító: a képre kattintva középre, elmosott háttér elé. Nyilakkal és
// billentyűzettel is lapozható, az utolsó után újra az első jön.
$this->registerJs(<<<'JS'
(function () {
    var galeria = document.getElementById('galeria');
    var nagyito = document.getElementById('galeria-nagyito');
    if (!galeria || !nagyito) {
        return;
    }

    var elemek = Array.prototype.slice.call(galeria.querySelectorAll('.carousel-item img'));
    var nagyKep = nagyito.querySelector('img');
    var szamlalo = nagyito.querySelector('.gallery-lightbox-counter');
    var aktualis = 0;

    function mutat(index) {
        aktualis = (index + elemek.length) % elemek.length;
        nagyKep.setAttribute('src', elemek[aktualis].getAttribute('src'));
        nagyKep.setAttribute('alt', elemek[aktualis].getAttribute('alt'));
        szamlalo.textContent = (aktualis + 1) + ' / ' + elemek.length;
    }

    function nyit(index) {
        mutat(index);
        nagyito.hidden = false;
        nagyito.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        nagyito.querySelector('[data-nagyito="bezar"]').focus();
    }

    function zar() {
        nagyito.hidden = true;
        nagyito.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    elemek.forEach(function (kep, index) {
        kep.addEventListener('click', function () {
            nyit(index);
        });
    });

    nagyito.addEventListener('click', function (esemeny) {
        var muvelet = esemeny.target.getAttribute ? esemeny.target.getAttribute('data-nagyito') : null;
        if (muvelet === 'elozo') {
            mutat(aktualis - 1);
        } else if (muvelet === 'kovetkezo') {
            mutat(aktualis + 1);
        } else if (muvelet === 'bezar' || esemeny.target === nagyito) {
            zar();
        }
    });

    document.addEventListener('keydown', function (esemeny) {
        if (nagyito.hidden) {
            return;
        }
        if (esemeny.key === 'Escape') {
            zar();
        } else if (esemeny.key === 'ArrowLeft') {
            mutat(aktualis - 1);
        } else if (esemeny.key === 'ArrowRight') {
            mutat(aktualis + 1);
        }
    });
})();
JS);
