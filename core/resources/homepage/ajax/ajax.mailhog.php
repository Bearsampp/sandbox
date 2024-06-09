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
 * This script retrieves information about Mailhog server status and versions.
 * It returns a JSON-encoded array with the collected data.
 */

// Initialize result array
$result = array(
    'checkport' => '',
    'versions' => '',
);

/**
 * Check the SMTP port status and update the result array.
 */
$smtpPort = $bearsamppBins->getMailhog()->getSmtpPort();

$textServiceStarted = $bearsamppLang->getValue(Lang::HOMEPAGE_SERVICE_STARTED);
$textServiceStopped = $bearsamppLang->getValue(Lang::HOMEPAGE_SERVICE_STOPPED);
$textDisabled = $bearsamppLang->getValue(Lang::DISABLED);

if ($bearsamppBins->getMailhog()->checkPort($smtpPort)) {
    if ($bearsamppBins->getMailhog()->checkPort($smtpPort)) {
        $result['checkport'] .= '<span class="float-end badge text-bg-success">' . sprintf($textServiceStarted, $smtpPort) . '</span>';
    } else {
        $result['checkport'] .= '<span class="float-end badge text-bg-danger">' . $textServiceStopped . '</span>';
    }
} else {
    $result['checkport'] = '<span class="float-end badge text-bg-secondary">' . $textDisabled . '</span>';
}

/**
 * Retrieve and update Mailhog versions.
 */
foreach ($bearsamppBins->getMailhog()->getVersionList() as $version) {
    if ($version != $bearsamppBins->getMailhog()->getVersion()) {
        $result['versions'] .= '<span class="m-1 badge text-bg-secondary">' . $version . '</span>';
    } else {
        $result['versions'] .= '<span class="m-1 badge text-bg-primary">' . $bearsamppBins->getMailhog()->getVersion() . '</span>';
    }
}

/**
 * Output the result as JSON.
 */
echo json_encode($result);
