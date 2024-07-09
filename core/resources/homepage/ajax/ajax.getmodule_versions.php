<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

// Include necessary files and initialize the application environment
include __DIR__ . '/../../../root.php';
include __DIR__ . '/../../../classes/actions/class.action.quickPick.php';

// Check if the module parameter is set
if (isset($_POST['module'])) {
    $module = $_POST['module'];

    // Fetch module versions
    $versions = QuickPick::getModuleVersions($module);

    // Return the versions as a JSON response
    header('Content-Type: application/json');
    echo json_encode($versions);
} else {
    // Return an error response if the module parameter is missing
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Module parameter is missing']);
}
