<?php
/*
 * Copyright (c) 2022 - 2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

global $bearsamppHomepage, $bearsamppLang;
require_once __DIR__ . '/../../root.php';

$appTitle      = APP_TITLE;
$appVersion    = APP_VERSION;
$resourcesPath = RESOURCES_PATH;
?>
<!DOCTYPE html>
<html lang = "en-US">
<head>
    <meta charset = "utf-8">
    <meta name = "viewport" content = "width=device-width, initial-scale=1.0">
    <meta name = "description" content = "">
    <meta name = "author" content = "">

    <?php
    $styles = [
        'libs/bootstrap/bootstrap.min.css',
        'libs/fontawesome/css/all.css',
        'libs/fontawesome/css/v4-shims.css',
        'css/app.css'
    ];
    foreach ( $styles as $style )
    {
        echo '<link href="' . $resourcesPath . '/' . $style . '" rel="stylesheet">' . PHP_EOL;
    }

    $scripts = [
        'libs/jquery/jquery-3.7.1.min.js',
        'libs/jquery/jquery-migrate-3.4.0.min.js',
        'libs/bootstrap/popper.min.js',
        'libs/bootstrap/bootstrap.min.js',
        'libs/fontawesome/js/all.js',
        'libs/fontawesome/js/v4-shims.js',
        'js/_commons.js',
        'js/latestversion.js',
        'js/summary.js',
        'js/apache.js',
        'js/filezilla.js',
        'js/mailhog.js',
        'js/mariadb.js',
        'js/memcached.js',
        'js/mysql.js',
        'js/nodejs.js',
        'js/php.js',
        'js/postgresql.js'

    ];
    foreach ( $scripts as $script )
    {
        echo '<script src="' . RESOURCES_PATH. '/' . $script . '"></script>' . PHP_EOL;
    }
    ?>

    <!-- Create favicon directly into the html -->
    <link href = "<?php echo $resourcesPath . '/bearsampp.ico'; ?>" rel = "icon" />

    <title><?php echo $appTitle . ' ' . $appVersion; ?></title>
</head>

<body>
<nav class = "navbar navbar-expand-md navbar-light bg-dark fixed-top" role = "navigation">
    <div class = "container-fluid">
        <div class = "d-inline-block">
            <a class = "navbar-brand" href = "<?php echo htmlspecialchars( Util::getWebsiteUrl() ); ?>">
                <img class = "p-1" alt = "<?php echo $appTitle . ' ' . $appVersion; ?>"
                     src = "<?php echo RESOURCES_PATH. '/img/header-logo.png'; ?>" /></a>
            <button class = "navbar-toggler" type = "button" data-bs-toggle = "collapse" data-bs-target = "#navbarSupportedContent" aria-controls = "navbarSupportedContent"
                    aria-expanded = "false" aria-label = "Toggle navigation">
                <span class = "navbar-toggler-icon"></span>
            </button>
        </div>
    </div>
    <div class = "collapse navbar-collapse" id = "navbarSupportedContent">
        <ul class = "d-flex flex-row justify-content-end flex-fill mb-0">
            <li>
                <a data-bs-toggle = "tooltip" data-bs-placement = "top" data-bs-title = "<?php echo $bearsamppLang->getValue( Lang::GITHUB ); ?>" target = "_blank"
                   href = "<?php echo Util::getGithubUrl(); ?>"><img src = "<?php echo RESOURCES_PATH. '/img/github.png'; ?>" alt = "Github URL" /></a>
            </li>
            <li>
                <a data-bs-toggle = "tooltip" data-bs-placement = "top" data-bs-title = "<?php echo $bearsamppLang->getValue( Lang::DONATE ); ?>" target = "_blank"
                   href = "<?php echo Util::getWebsiteUrl( 'donate' ); ?>"><img src = "<?php echo RESOURCES_PATH. '/img/heart.png'; ?>" alt = "Website URL" /></a>
            </li>
        </ul>
    </div>
</nav>

<div id = "page-wrapper">
    <!-- TODO need to work on the latestversion.php to use github api -->
    <?php include_once 'tpls/hp.latestversion.php'; ?>
    <?php include_once 'tpls/hp.' . $bearsamppHomepage->getPage() . '.php'; ?>
</div>

</body>
</html>
