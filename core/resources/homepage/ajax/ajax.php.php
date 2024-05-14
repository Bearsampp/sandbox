<?php
/*
 * Copyright (c) 2-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: @author@
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Prepare and output JSON-encoded PHP configuration and status data.
 *
 * This script gathers various pieces of information about the PHP configuration
 * and its extensions, formats them with HTML for display purposes, and outputs
 * them in a JSON-encoded format. This includes the PHP version, status (enabled/disabled),
 * count of extensions, PEAR version, and a list of extensions with their respective statuses.
 *
 * @global object $bearsamppBins Instance of a class that provides methods to get PHP-related configurations.
 * @global object $bearsamppLang Instance of a class that provides methods to retrieve language-specific values.
 */
$result = array(
    'status' => '',
    'versions' => '',
    'extscount' => '',
    'pearversion' => '',
    'extslist' => '',
);

// Status
if ($bearsamppBins->getPhp()->isEnable()) {
    $result['status'] = '<span class="float-right badge text-bg-primary">' . $bearsamppLang->getValue(Lang::ENABLED) . '</span>';
} else {
    $result['status'] = '<span class="float-right badge text-bg-secondary">' . $bearsamppLang->getValue(Lang::DISABLED) . '</span>';
}

// Versions
foreach ($bearsamppBins->getPhp()->getVersionList() as $version) {
    if ($version != $bearsamppBins->getPhp()->getVersion()) {
        $result['versions'] .= '<span class="m-1 badge text-bg-secondary">' . $version . '</span>';
    }
}
$result['versions'] .= '<span class="m-1 badge text-bg-primary">' . $bearsamppBins->getPhp()->getVersion() . '</span>';

// Extensions count
$exts = count($bearsamppBins->getPhp()->getExtensions());
$extsLoaded = count($bearsamppBins->getPhp()->getExtensionsLoaded());
$result['extscount'] .= '<span class="float-right badge text-bg-primary">' . $extsLoaded . ' / ' . $exts . '</span>';

// PEAR version
$result['pearversion'] .= '<span class="float-right badge text-bg-primary">' . $bearsamppBins->getPhp()->getPearVersion(true) . '</span>';

// Extensions list
foreach ($bearsamppBins->getPhp()->getExtensionsFromConf() as $extName => $extStatus) {
    if ($extStatus == ActionSwitchPhpExtension::SWITCH_ON) {
        $result['extslist'] .= '<span class="span-grid col-xs-12 col-md-2"><i class="fa fa-check-square-o"></i> <strong>' . $extName . ' <sup>' . phpversion(substr($extName, 4)) . '</sup></strong></span>';
    } else {
        $result['extslist'] .= '<span class="span-grid col-xs-12 col-md-2"><i class="fa fa-square-o"></i> ' . $extName . '</span>';
    }
}

echo json_encode($result);
