<?php
/*
 * Copyright (c) 2021-2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * This script checks the status of the MySQL service and retrieves the available versions.
 * It outputs a JSON-encoded array containing the port check status and the versions of MySQL.
 */

/**
 * @var array $result Holds the results of the port check and versions list.
 */
$result = array(
    'checkport' => '',
    'versions' => '',
);

/**
 * Retrieve the current port used by MySQL.
 * @var int $port The port number on which MySQL is running.
 */
$port = $bearsamppBins->getMysql()->getPort();

/**
 * Language strings for various statuses.
 * @var string $textServiceStarted Message when the service is started.
 * @var string $textServiceStopped Message when the service is stopped.
 * @var string $textDisabled Message when the service is disabled.
 */
$textServiceStarted = $bearsamppLang->getValue(Lang::HOMEPAGE_SERVICE_STARTED);
$textServiceStopped = $bearsamppLang->getValue(Lang::HOMEPAGE_SERVICE_STOPPED);
$textDisabled = $bearsamppLang->getValue(Lang::DISABLED);

/**
 * Check if MySQL is enabled and its port status, updating the result array accordingly.
 */
if ($bearsamppBins->getMysql()->isEnable()) {
    if ($bearsamppBins->getMysql()->checkPort($port)) {
        $result['checkport'] .= '<span class="float-right badge text-bg-success">' . sprintf($textServiceStarted, $port) . '</span>';
    } else {
        $result['checkport'] .= '<span class="float-right badge text-bg-danger">' . $textServiceStopped . '</span>';
    }
} else {
    $result['checkport'] = '<span class="float-right badge text-bg-secondary">' . $textDisabled . '</span>';
}

/**
 * Compile a list of available MySQL versions, highlighting the current version.
 */
foreach ($bearsamppBins->getMysql()->getVersionList() as $version) {
    if ($version != $bearsamppBins->getMysql()->getVersion()) {
        $result['versions'] .= '<span class="m-1 badge text-bg-secondary">' . $version . '</span>';
    }
}
$result['versions'] .= '<span class="m-1 badge text-bg-primary">' . $bearsamppBins->getMysql()->getVersion() . '</span>';

/**
 * Output the results as a JSON string.
 */
echo json_encode($result);
