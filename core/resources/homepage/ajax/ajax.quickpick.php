<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * This script handles AJAX requests for installing modules in the Bearsampp application.
 * It expects a POST request with 'module' and 'version' parameters.
 *
 * The script performs the following actions:
 * - Includes the QuickPick class.
 * - Logs the file access.
 * - Creates an instance of the QuickPick class.
 * - Sets the response header to JSON.
 * - Initializes an empty response array.
 * - Checks if the request method is POST.
 * - Validates the 'module' and 'version' parameters.
 * - Calls the installModule method of the QuickPick class.
 * - Constructs a response message based on the installation result.
 * - Returns the response as a JSON object.
 *
 * @package    Bearsampp
 * @subpackage Core
 * @category   AJAX
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       https://bearsampp.com
 */

include __DIR__ . '/../../../classes/actions/class.action.quickPick.php';
Util::logDebug('File accessed successfully.');
$QuickPick = new QuickPick();

header('Content-Type: application/json');

$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module  = isset($_POST['module']) ? $_POST['module'] : null;
    $version = isset($_POST['version']) ? $_POST['version'] : null;

    if ($module && $version) {
        $response = $QuickPick->installModule($module, $version);
        if (!isset($response['error'])) {
            $response['message'] = "Module $module version $version installed successfully.";
            if (isset($QuickPick->modules[$module]) && $QuickPick->modules[$module]['type'] === "binary") {
                $response['message'] .= "\nReload needed... Right click on menu and choose reload.";
            } else {
                $response['message'] .= "\nEdit Bearsampp.conf to use new version then";
                $response['message'] .= "\nRight click on menu and choose reload.";
            }
        } else {
            error_log('Error in response: ' . json_encode($response));
        }
        Util::logDebug('Response: ' . json_encode($response));
    } else {
        $response = ['error' => 'Invalid module or version.'];
    }
} else {
    $response = ['error' => 'Invalid request method.'];
}

echo json_encode($response);
