<?php
/*
 * Copyright (c) 2-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: @author@
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */
/**
 * This script checks the status and versions of Node.js installed and enabled in the Bearsampp environment.
 * It outputs a JSON-encoded array containing the status (enabled/disabled) and the versions available,
 * highlighting the currently active version.
 */

/**
 * Initialize result array with default values for status and versions.
 *
 * @var array $result
 */
$result = array(
    'status' => '',
    'versions' => ''
);

/**
 * Check if Node.js is enabled and set the status in the result array.
 * Uses the Lang class constants for ENABLED and DISABLED to fetch the appropriate language-specific message.
 */
if ($bearsamppBins->getNodejs()->isEnable()) {
    $result['status'] = '<span class="float-right badge text-bg-primary">' . $bearsamppLang->getValue(Lang::ENABLED) . '</span>';
} else {
    $result['status'] = '<span class="float-right badge text-bg-secondary">' . $bearsamppLang->getValue(Lang::DISABLED) . '</span>';
}

/**
 * Iterate over the list of available Node.js versions.
 * Append each version to the versions key in the result array, marking the current version distinctly.
 */
foreach ($bearsamppBins->getNodejs()->getVersionList() as $version) {
    if ($version != $bearsamppBins->getNodejs()->getVersion()) {
        $result['versions'] .= '<span class="m-1 badge text-bg-secondary">' . $version . '</span>';
    }
}
$result['versions'] .= '<span class="m-1 badge text-bg-primary">' . $bearsamppBins->getNodejs()->getVersion() . '</span>';

/**
 * Output the result array as a JSON string.
 */
echo json_encode($result);
