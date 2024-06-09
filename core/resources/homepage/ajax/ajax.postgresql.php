<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

global $bearsamppBins, $bearsamppLang;

/**
 * This script retrieves information about the PostgreSQL service status and versions.
 * It checks if the PostgreSQL service is enabled, checks the port, and displays a corresponding badge.
 * It also retrieves the list of PostgreSQL versions and displays them as badges.
 * The final result is encoded in JSON format and returned.
 */

// Initialize result array
$result = array(
    'checkport' => '',
    'versions' => '',
);

// Check port
$port = $bearsamppBins->getPostgresql()->getPort();

$textServiceStarted = $bearsamppLang->getValue(Lang::HOMEPAGE_SERVICE_STARTED);
$textServiceStopped = $bearsamppLang->getValue(Lang::HOMEPAGE_SERVICE_STOPPED);
$textDisabled = $bearsamppLang->getValue(Lang::DISABLED);

/**
 * Check if PostgreSQL service is enabled and update the result array with the port status.
 */
if ($bearsamppBins->getPostgresql()->isEnable()) {
    if ($bearsamppBins->getPostgresql()->checkPort($port)) {
        $result['checkport'] .= '<span class="float-end badge text-bg-success">' . sprintf($textServiceStarted, $port) . '</span>';
    } else {
        $result['checkport'] .= '<span class="float-end badge text-bg-danger">' . $textServiceStopped . '</span>';
    }
} else {
    $result['checkport'] = '<span class="float-end badge text-bg-secondary">' . $textDisabled . '</span>';
}

/**
 * Retrieve and update the result array with the list of PostgreSQL versions.
 */
foreach ($bearsamppBins->getPostgresql()->getVersionList() as $version) {
    if ($version != $bearsamppBins->getPostgresql()->getVersion()) {
        $result['versions'] .= '<span class="m-1 badge text-bg-secondary">' . $version . '</span>';
    } else {
        $result['versions'] .= '<span class="m-1 badge text-bg-primary">' . $bearsamppBins->getPostgresql()->getVersion() . '</span>';
    }
}

// Output the result as JSON
echo json_encode($result);
