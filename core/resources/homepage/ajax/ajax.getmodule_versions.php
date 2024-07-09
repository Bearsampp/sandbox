<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

if (isset($_POST['module'])) {
    $module = $_POST['module'];
    $versions = QuickPick::getModuleVersions($module);

    header('Content-Type: application/json');
    echo json_encode($versions);
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Module parameter is missing']);
}
