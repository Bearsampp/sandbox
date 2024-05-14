<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */


/**
* This script checks the SMTP port status and lists all versions of Mailhog.
* It outputs a JSON-encoded array containing the port status and available versions.
*
* @package Bearsampp
* @author @author@
* @license GNU General Public License version 3 or later
* @link https://bearsampp.com
*/

/**
* @var array $result Holds the results of the port check and versions list.
*/
$result = array(
'checkport' => '',
'versions' => '',
);

/**
* Retrieves the SMTP port for Mailhog and checks its status.
* Updates $result['checkport'] with the status in HTML badge format.
*
* @var int $smtpPort SMTP port used by Mailhog.
*/
$smtpPort = $bearsamppBins->getMailhog()->getSmtpPort();

/**
* @var string $textServiceStarted Message when service is started.
* @var string $textServiceStopped Message when service is stopped.
* @var string $textDisabled Message when service is disabled.
*/
$textServiceStarted = $bearsamppLang->getValue(Lang::HOMEPAGE_SERVICE_STARTED);
$textServiceStopped = $bearsamppLang->getValue(Lang::HOMEPAGE_SERVICE_STOPPED);
$textDisabled = $bearsamppLang->getValue(Lang::DISABLED);

/**
* Checks if the SMTP port is open and sets the appropriate status message.
*/
if ($bearsamppBins->getMailhog()->checkPort($smtpPort)) {
if ($bearsamppBins->getMailhog()->checkPort($smtpPort)) {
$result['checkport'] .= '<span class="float-right badge text-bg-success">' . sprintf($textServiceStarted, $smtpPort) . '</span>';
} else {
$result['checkport'] .= '<span class="float-right badge text-bg-danger">' . $textServiceStopped . '</span>';
}
} else {
$result['checkport'] = '<span class="float-right badge text-bg-secondary">' . $textDisabled . '</span>';
}

/**
* Retrieves and formats a list of all Mailhog versions, highlighting the current version.
* Updates $result['versions'] with the versions in HTML badge format.
*/
foreach ($bearsamppBins->getMailhog()->getVersionList() as $version) {
if ($version != $bearsamppBins->getMailhog()->getVersion()) {
$result['versions'] .= '<span class="m-1 badge text-bg-secondary">' . $version . '</span>';
}
}
$result['versions'] .= '<span class="m-1 badge text-bg-primary">' . $bearsamppBins->getMailhog()->getVersion() . '</span>';

/**
* Outputs the results as a JSON-encoded string.
*/
echo json_encode($result);
