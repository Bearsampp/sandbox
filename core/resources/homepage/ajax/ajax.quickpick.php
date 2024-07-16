<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

include __DIR__ . '/../../../classes/actions/class.action.quickPick.php';
Util::logDebug('File accessed successfully.');
$QuickPick = new QuickPick();

header('Content-Type: application/json');

$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module = isset($_POST['module']) ? $_POST['module'] : null;
    $version = isset($_POST['version']) ? $_POST['version'] : null;

    if ($module && $version) {
        $QuickPick->installModule($module, $version);
        $response['message'] = "Module $module version $version installed successfully.";
    } else {
        $response['error'] = "Invalid module or version.";
    }
} else {
    $response['error'] = "Invalid request method.";
}

echo json_encode($response);
