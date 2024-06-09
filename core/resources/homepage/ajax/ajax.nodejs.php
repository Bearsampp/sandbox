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
 * This script generates a JSON-encoded array containing the status and versions of Node.js.
 * It checks if Node.js is enabled and sets the status accordingly.
 * Then, it loops through the Node.js version list, adding versions to the 'versions' key.
 * Finally, it encodes the result array into a JSON format and echoes it.
 */

// Initialize result array
$result = array(
    'status' => '',
    'versions' => ''
);

/**
 * Check the status of Node.js and update the result array.
 * If Node.js is enabled, set the status to 'enabled' with a success badge.
 * Otherwise, set the status to 'disabled' with a danger badge.
 */
if ($bearsamppBins->getNodejs()->isEnable()) {
    $result['status'] = '<span class="float-end badge text-bg-success">' . $bearsamppLang->getValue(Lang::ENABLED) . '</span>';
} else {
    $result['status'] = '<span class="float-end badge text-bg-danger">' . $bearsamppLang->getValue(Lang::DISABLED) . '</span>';
}

/**
 * Loop through the Node.js version list and update the result array.
 * If the version is not the current version, add it with a secondary badge.
 * Otherwise, add the current version with a primary badge.
 */
foreach ($bearsamppBins->getNodejs()->getVersionList() as $version) {
    if ($version != $bearsamppBins->getNodejs()->getVersion()) {
        $result['versions'] .= '<span class="m-1 badge text-bg-secondary">' . $version . '</span>';
    } else {
        $result['versions'] .= '<span class="m-1 badge text-bg-primary">' . $bearsamppBins->getNodejs()->getVersion() . '</span>';
    }
}

// Output the result as JSON
echo json_encode($result);
