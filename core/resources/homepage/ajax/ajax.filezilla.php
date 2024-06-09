<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * This script retrieves information about the status of the FileZilla service and its versions.
 * It checks if the FileZilla service is enabled, checks the ports it is running on, and lists available versions.
 * The output is encoded in JSON format and includes 'checkport' and 'versions' keys with corresponding information.
 */

// Declare global variables
global $bearsamppBins, $bearsamppLang;

// Initialize result array
$result = array(
    'checkport' => '',
    'versions' => '',
);

// Check port status and update result
$port = $bearsamppBins->getFilezilla()->getPort();
$sslPort = $bearsamppBins->getFilezilla()->getSslPort();

$textServiceStarted = $bearsamppLang->getValue(Lang::HOMEPAGE_SERVICE_STARTED);
$textServiceStopped = $bearsamppLang->getValue(Lang::HOMEPAGE_SERVICE_STOPPED);
$textDisabled = $bearsamppLang->getValue(Lang::DISABLED);

/**
 * Check if FileZilla service is enabled and update port status in the result array.
 */
if ($bearsamppBins->getFilezilla()->isEnable()) {
    if ($bearsamppBins->getFilezilla()->checkPort($sslPort, true)) {
        $result['checkport'] .= '<span class="float-end m-1 badge text-bg-success">' . sprintf($textServiceStarted, $sslPort) . ' (SSL)</span>';
    } else {
        $result['checkport'] .= '<span class="float-end m-1 badge text-bg-danger">' . $textServiceStopped . ' (SSL)</span>';
    }
    if ($bearsamppBins->getFilezilla()->checkPort($port)) {
        $result['checkport'] .= '<span class="float-end m-1 badge text-bg-success">' . sprintf($textServiceStarted, $port) . '</span>';
    } else {
        $result['checkport'] .= '<span class="float-end m-1 badge text-bg-danger">' . $textServiceStopped . '</span>';
    }
} else {
    $result['checkport'] = '<span class="float-end m-1 badge text-bg-secondary">' . $textDisabled . '</span>';
}

/**
 * Retrieve and update FileZilla versions in the result array.
 */
foreach ($bearsamppBins->getFilezilla()->getVersionList() as $version) {
    if ($version != $bearsamppBins->getFilezilla()->getVersion()) {
        $result['versions'] .= '<span class="m-1 badge text-bg-secondary">' . $version . '</span>';
    } else {
        $result['versions'] .= '<span class="m-1 badge text-bg-primary">' . $bearsamppBins->getFilezilla()->getVersion() . '</span>';
    }
}

// Output the result as JSON
echo json_encode($result);
