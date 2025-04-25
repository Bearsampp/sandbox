<?php
/*
 *
 *  * Copyright (c) 2022-2025 Bearsampp
 *  * License: GNU General Public License version 3 or later; see LICENSE.txt
 *  * Website: https://bearsampp.com
 *  * Github: https://github.com/Bearsampp
 *
 */

/**
 * This script handles AJAX requests for QuickPick functionality in the Bearsampp application.
 * It supports various actions related to module management.
 *
 * @package    Bearsampp
 * @subpackage Core
 * @category   AJAX
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       https://bearsampp.com
 */
include __DIR__ . '/../../../classes/actions/class.action.quickPick.php';
Util::logDebug('QuickPick AJAX handler accessed.');

header('Content-Type: application/json');
$response = array();

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

// Get the requested action
$action = isset($_POST['action']) ? $_POST['action'] : 'install'; // Default to install for backward compatibility

// Initialize QuickPick
$QuickPick = new QuickPick();

// Handle different actions
switch ($action) {
    case 'install':
        // Handle module installation
        $module = isset($_POST['module']) ? $_POST['module'] : null;
        $version = isset($_POST['version']) ? $_POST['version'] : null;
        $filesize = isset($_POST['filesize']) ? $_POST['filesize'] : null;
        
        if (!$module || !$version) {
            $response = ['error' => 'Invalid module or version.'];
            break;
        }
        
        $response = $QuickPick->installModule($module, $version);
        
        if (!isset($response['error'])) {
            $response['message'] = "Module $module version $version installed successfully.";
            if (isset($QuickPick->modules[$module]) && $QuickPick->modules[$module]['type'] === "binary") {
                $response['message'] .= "\nReload needed...";
                $response['message'] .= "\nWhen you are done installing modules then";
                $response['message'] .= "\nRight click on menu and choose reload.";
            } else {
                $response['message'] .= "\nEdit Bearsampp.conf to use new version(s) then";
                $response['message'] .= "\nWhen you are done installing modules";
                $response['message'] .= "\nRight click on menu and choose reload.";
            }
        } else {
            error_log('Error in response: ' . json_encode($response));
        }
        Util::logDebug('Install response: ' . json_encode($response));
        break;
        
    case 'list':
        // Return a list of available modules
        $response = [
            'success' => true,
            'modules' => $QuickPick->getAvailableModules()
        ];
        break;
        
    case 'check-updates':
        // Check for module updates
        $module = isset($_POST['module']) ? $_POST['module'] : null;
        
        if (!$module) {
            $response = ['error' => 'Invalid module.'];
            break;
        }
        
        $response = $QuickPick->checkModuleUpdates($module);
        break;
        
    case 'load_quickpick':
        // Load QuickPick menu asynchronously
        global $bearsamppHomepage;
        $imagesPath = $bearsamppHomepage->getImagesPath();
        
        try {
            $html = $QuickPick->loadQuickpick($imagesPath);
            $response = ['success' => true, 'html' => $html];
        } catch (Exception $e) {
            $response = ['success' => false, 'error' => $e->getMessage()];
        }
        break;
        
    default:
        $response = ['error' => 'Unknown action.'];
        break;
}

echo json_encode($response);
