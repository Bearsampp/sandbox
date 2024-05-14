<?php
/*
 * Copyright (c) 2-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: @author@
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * This script checks the status of the PostgreSQL service and lists available versions.
 * It outputs a JSON-encoded array containing the port check status and the versions of PostgreSQL.
 */

/**
 * @var array $result Holds the results of the port check and versions list.
 */
global $bearsamppBins;
$result = array(
    'checkport' => '',
    'versions' => '',
);

/**
 * Retrieves the port number for the PostgreSQL service.
 *
 * @var int $port The port number used by PostgreSQL.
 */
$port = $bearsamppBins->getPostgresql()->getPort();

/**
 * Language strings for various statuses.
 *
 * @var string $textServiceStarted Message when the service is started.
 * @var string $textServiceStopped Message when the service is stopped.
 * @var string $textDisabled Message when the service is disabled.
 */
$textServiceStarted = $bearsamppLang->getValue(Lang::HOMEPAGE_SERVICE_STARTED);
$textServiceStopped = $bearsamppLang->getValue(Lang::HOMEPAGE_SERVICE_STOPPED);
$textDisabled = $bearsamppLang->getValue(Lang::DISABLED);

/**
 * Checks if PostgreSQL service is enabled and updates the port check status accordingly.
 */
if ($bearsamppBins->getPostgresql()->isEnable()) {
    if ($bearsamppBins->getPostgresql()->checkPort($port)) {
        $result['checkport'] .= '<span class="float-right badge text-bg-success">' . sprintf($textServiceStarted, $port) . '</span>';
    } else {
        $result['checkport'] .= '<span class="float-right badge text-bg-danger">' . $textServiceStopped . '</span>';
    }
} else {
    $result['checkport'] = '<span class="float-right badge text-bg-secondary">' . $textDisabled . '</span>';
}

/**
 * Compiles a list of available PostgreSQL versions, highlighting the current version.
 */
foreach ($bearsamppBins->getPostgresql()->getVersionList() as $version) {
    if ($version != $bearsamppBins->getPostgresql()->getVersion()) {
        $result['versions'] .= '<span class="m-1 badge text-bg-secondary">' . $version . '</span>';
    }
}
$result['versions'] .= '<span class="m-1 badge text-bg-primary">' . $bearsamppBins->getPostgresql()->getVersion() . '</span>';

/**
 * Outputs the results as a JSON-encoded string.
 */
echo json_encode($result);
