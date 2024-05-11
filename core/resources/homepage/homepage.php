<?php
/*
 * Copyright (c) 2022 - 2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */
include __DIR__ . '/../../root.php';
global $bearsamppLang, $bearsamppCore, $bearsamppHomepage, $bearsamppConfig, $locale;
$resourcesPath = $bearsamppHomepage->getResourcesPath();
?>
<!DOCTYPE html>
<html lang = "<?php echo $locale ?>">

<head>
    <meta charset = 'utf-8'>
    <meta name = 'viewport' content = 'width=device-width, initial-scale=1.0'>
    <meta name = 'description' content = 'Localhost Dashboard'>
    <meta name = 'author' content = 'Bearsampp'>
    <?php
    $cssFiles = [
        '/css/app.css',
        '/libs/bootstrap/bootstrap.min.css',
        '/libs/fontawesome/css/fontawesome.min.css'
    ];
    $jsFiles  = [
        '/libs/jquery/jquery-3.7.1.min.js',
        '/libs/jquery/jquery-migrate-3.4.0.min.js',
        '/libs/bootstrap/popper.min.js',
        '/libs/bootstrap/bootstrap.min.js',
        '/libs/fontawesome/js/all.min.js',
        '/js/_commons.js',
        '/js/latestversion.js',
        '/js/summary.js',
        '/js/apache.js',
        '/js/filezilla.js',
        '/js/mailhog.js',
        '/js/mariadb.js',
        '/js/memcached.js',
        '/js/mysql.js',
        '/js/nodejs.js',
        '/js/php.js',
        '/js/postgresql.js'
    ];

    foreach ( $cssFiles as $file ) {
        echo '<link href="' . $resourcesPath . $file . '" rel="stylesheet">' . PHP_EOL;
    }

    foreach ( $jsFiles as $file ) {
        echo '<script src="' . $resourcesPath . $file . '"></script>' . PHP_EOL;
    }
    ?>
    <link href = "<?php echo Util::imgToBase64( $bearsamppCore->getResourcesPath() . '/icons/app.ico' ); ?>" rel = "icon" />
    <title><?php echo APP_TITLE . ' ' . $bearsamppCore->getAppVersion(); ?></title>
</head>

<body>
<nav class = 'navbar navbar-expand-sm navbar-light bg-dark'>
    <div class = 'container-fluid'>
        <a class = 'navbar-brand' href = '<?php echo Util::getWebsiteUrl(); ?>'>
            <img src = "<?php echo $resourcesPath . '/img/header-logo.png'; ?>" alt = "<?php echo APP_TITLE . ' ' . $bearsamppCore->getAppVersion(); ?>" aria-hidden = 'true' />
        </a>
        <button class = 'navbar-toggler' type = 'button' data-bs-toggle = 'collapse' data-bs-target = '#navbarNavAltMarkup' aria-controls = 'navbarNavAltMarkup'
                aria-expanded = 'false' aria-label = 'Toggle navigation'>
            <span class = 'navbar-toggler-icon'></span>
        </button>
        <div class = 'collapse navbar-collapse' id = 'navbarNavAltMarkup'>
            <div class = 'navbar-nav ms-auto'>
                <a class = 'nav-link social-icon' data-bs-toggle = 'tooltip' data-bs-placement = 'top' data-bs-title = "<?php echo $bearsamppLang->getValue( Lang::DISCORD ); ?>"
                   target = '_blank'
                   href = 'https://discord.gg/AgwVNAzV'><img class = 'discord' src = "<?php echo $resourcesPath . '/img/discord.png'; ?>" alt = 'Discord Icon'
                                                             aria-hidden = 'true' /></a>
                <a class = 'nav-link social-icon' data-bs-toggle = 'tooltip' data-bs-placement = 'top' data-bs-title = "<?php echo $bearsamppLang->getValue( Lang::FACEBOOK ); ?>"
                   target = '_blank'
                   href = 'https://www.facebook.com/groups/bearsampp'><i class = 'fa-brands fa-facebook' aria-hidden = 'true'></i></a>
                <a class = 'nav-link social-icon' data-bs-toggle = 'tooltip' data-bs-placement = 'top' data-bs-title = "<?php echo $bearsamppLang->getValue( Lang::GITHUB ); ?>"
                   target = '_blank'
                   href = '<?php echo Util::getGithubUrl(); ?>'><i class = 'fa-brands fa-github' aria-hidden = 'true'></i></a>
                <a class = 'nav-link social-icon' data-bs-toggle = 'tooltip' data-bs-placement = 'top' data-bs-title = "<?php echo $bearsamppLang->getValue( Lang::DONATE ); ?>"
                   target = '_blank'
                   href = "<?php echo Util::getWebsiteUrl( 'donate' ); ?>"><img class = 'donate' src = "<?php echo $resourcesPath . '/img/donate.png'; ?>"
                                                                                alt = 'Donation Icon' aria-hidden = 'true' /></a>
            </div>
        </div>
    </div>
</nav>
<?php include 'tpls/hp.latestversion.php'; ?>
<div id = "page-wrapper">
    <?php include 'tpls/hp.' . $bearsamppHomepage->getPage() . '.php'; ?>
</div>

</body>

</html>
