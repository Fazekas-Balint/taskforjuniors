<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<header id="header">
    <?php
    NavBar::begin([
        'brandLabel' => Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark fixed-top']
    ]);
    // A menü csak azt kínálja fel, amit az adott felhasználó használni is tud:
    // a szerkesztő pontokra a kollega és a vendég eddig 403-at kapott.
    $belepett = !Yii::$app->user->isGuest;
    $szerkeszthet = $belepett && Yii::$app->user->identity->canEdit();


    echo Nav::widget([
        'options' => ['class' => 'navbar-nav'],
        'items' => [
            // Az "Áttekintés" menüpont helyett a bal oldali "Eszköztár" márkanév visz a főoldalra.
            // Vendégként a katalógus sem nyílik meg, ezért fel sem kínáljuk.
            ['label' => 'Katalógus', 'url' => ['/equipment/catalog'], 'visible' => $belepett],
            ['label' => 'Eszközök', 'url' => ['/equipment/index'], 'visible' => $szerkeszthet],
            ['label' => 'Kategóriák', 'url' => ['/category/index'], 'visible' => $szerkeszthet],
            ['label' => 'Kölcsönvevők', 'url' => ['/borrower/index'], 'visible' => $szerkeszthet],
            ['label' => 'Új kölcsönzés', 'url' => ['/loan/create'], 'visible' => $szerkeszthet],
            ['label' => 'Kölcsönzések', 'url' => ['/loan'], 'visible' => $szerkeszthet],
            ['label' => 'Hosszabbítás', 'url' => ['/extend'], 'visible' => $szerkeszthet],
            ['label' => 'Késés-riport', 'url' => ['/report/overdue'], 'visible' => $belepett],
            Yii::$app->user->isGuest
                ? ['label' => 'Login', 'url' => ['/site/login']]
                : '<li class="nav-item">'
                    . Html::beginForm(['/site/logout'])
                    . Html::submitButton(
                        'Kilépés (' . Yii::$app->user->identity->username . ')',
                        ['class' => 'nav-link btn btn-link logout']
                    )
                    . Html::endForm()
                    . '</li>'
        ]
    ]);
    NavBar::end();
    ?>
</header>

<main id="main" class="flex-shrink-0" role="main">
    <div class="container">
        <?php
        // A morzsamenü mindig a "Főoldal" hivatkozással kezdődik, az aloldalak ehhez
        // teszik hozzá a saját nevüket. Az üres címkéjű elemeket kiszűrjük, hogy a
        // főoldalon ne maradjon üres, "active" morzsa a sor végén.
        $breadcrumbLinks = array_merge(
            [['label' => 'Főoldal', 'url' => Yii::$app->homeUrl]],
            array_filter($this->params['breadcrumbs'] ?? [], function ($link) {
                return is_array($link) ? ($link['label'] ?? '') !== '' : (string) $link !== '';
            })
        );
        ?>
        <?= Breadcrumbs::widget(['homeLink' => false, 'links' => $breadcrumbLinks]) ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</main>

<footer id="footer" class="mt-auto py-3 bg-light">
    <div class="container">
        <div class="row text-muted">
            <div class="col-md-6 text-center text-md-start">&copy; My Company <?= date('Y') ?></div>
            <div class="col-md-6 text-center text-md-end"><?= Yii::powered() ?></div>
        </div>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
