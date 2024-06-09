<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Retrieves information about Apache server status, versions, modules, aliases, vhosts, directories, and URLs.
 * Returns a JSON-encoded array with the collected data.
 */

// Declare global variables
global $bearsamppRoot, $bearsamppBins, $bearsamppLang;

// Initialize result array
$result = array(
    'checkport' => '',
    'versions' => '',
    'modulescount' => '',
    'aliasescount' => '',
    'vhostscount' => '',
    'moduleslist' => '',
    'aliaseslist' => '',
    'wwwdirectory' => '',
    'vhostslist' => '',
);

// Check port status and update result
$port = $bearsamppBins->getApache()->getPort();
$sslPort = $bearsamppBins->getApache()->getSslPort();

$textServiceStarted = $bearsamppLang->getValue(Lang::HOMEPAGE_SERVICE_STARTED);
$textServiceStopped = $bearsamppLang->getValue(Lang::HOMEPAGE_SERVICE_STOPPED);
$textDisabled = $bearsamppLang->getValue(Lang::DISABLED);

if ($bearsamppBins->getApache()->isEnable()) {
    if ($bearsamppBins->getApache()->checkPort($sslPort, true)) {
        $result['checkport'] .= '<span class="m-1 float-end badge text-bg-success">' . sprintf($textServiceStarted, $sslPort) . ' (SSL)</span>';
    } else {
        $result['checkport'] .= '<span class="m-1 float-end badge text-bg-danger">' . $textServiceStopped . ' (SSL)</span>';
    }
    if ($bearsamppBins->getApache()->checkPort($port)) {
        $result['checkport'] .= '<span class="m-1 float-end badge text-bg-success">' . sprintf($textServiceStarted, $port) . '</span>';
    } else {
        $result['checkport'] .= '<span class="m-1 float-end badge text-bg-danger">' . $textServiceStopped . '</span>';
    }
} else {
    $result['checkport'] = '<span class="m-1 float-end badge text-bg-secondary">' . $textDisabled . '</span>';
}

// Retrieve and update Apache versions
foreach ($bearsamppBins->getApache()->getVersionList() as $version) {
    if ($version != $bearsamppBins->getApache()->getVersion()) {
        $result['versions'] .= '<span class="m-1 badge float-end text-bg-secondary">' . $version . '</span>';
    } else {
        $result['versions'] .= '<span class="m-1 badge float-end text-bg-primary">' . $bearsamppBins->getApache()->getVersion() . '</span>';
    }
}

// Retrieve and update modules count
$modules = count($bearsamppBins->getApache()->getModules());
$modulesLoaded = count($bearsamppBins->getApache()->getModulesLoaded());
$result['modulescount'] .= '<span class="m-1 float-end badge text-bg-primary">' . $modulesLoaded . ' / ' . $modules . '</span>';

// Retrieve and update aliases count
$result['aliasescount'] .= '<span class="m-1 float-end badge text-bg-primary">' . count($bearsamppBins->getApache()->getAlias()) . '</span>';

// Retrieve and update vhosts count
$result['vhostscount'] .= '<span class="m-1 float-end badge text-bg-primary">' . count($bearsamppBins->getApache()->getVhosts()) . '</span>';

// Retrieve and update modules list
foreach ($bearsamppBins->getApache()->getModulesFromConf() as $moduleName => $moduleStatus) {
    if ($moduleStatus == ActionSwitchApacheModule::SWITCH_ON) {
        $result['moduleslist'] .= '<span class="p-1 col col-xs-12"><i class="fa-regular fa-circle-check"></i> <strong>' . $moduleName . '</strong></span>';
    } else {
        $result['moduleslist'] .= '<span class="p-1 col col-xs-12"><i class="fa-regular fa-circle"></i> ' . $moduleName . '</span>';
    }
}

// Retrieve and update aliases list
foreach ($bearsamppBins->getApache()->getAlias() as $alias) {
    $result['aliaseslist'] .= '<div class="float-start p-1"><a class="btn btn-outline-dark" target="_blank" href="' . $bearsamppRoot->getLocalUrl($alias) . '"><i class="fa-solid fa-link"></i> ' . $alias . '</a></div>';
}

// Retrieve and update www directories
foreach ($bearsamppBins->getApache()->getWwwDirectories() as $wwwDirectory) {
    $result['wwwdirectory'] .= '<div class="float-start p-1"><a class="btn btn-outline-dark" target="_blank" href="' . $bearsamppRoot->getLocalUrl($wwwDirectory) . '"><i class="fa-solid fa-link"></i> ' . $wwwDirectory . '</a></div>';
}

// Retrieve and update vhosts list
foreach ($bearsamppBins->getApache()->getVhostsUrl() as $vhost => $enabled) {
    if ($enabled) {
        $result['vhostslist'] .= '<div class="float-start p-1"><a class="btn btn-outline-dark" target="_blank" href="http://' . $vhost . '"><i class="fa-regular fa-circle-check"></i> ' . $vhost . '</a></div>';
    } else {
        $result['vhostslist'] .= '<div class="float-start p-1"><a class="btn btn-outline-dark" target="_blank" href="http://' . $vhost . '"><i class="fa-regular fa-circle"></i> ' . $vhost . '</a></div>';
    }
}

// Output the result as JSON
echo json_encode($result);
