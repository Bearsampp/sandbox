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
 * AJAX handler for the homepage
 * Handles various AJAX requests including asynchronous QuickPick loading
 */

/**
 * Include the root configuration file.
 * This file is expected to set up the environment and include necessary configurations.
 */
include_once __DIR__ . '/../../root.php';
// QuickPick class will be included only when needed


/**
 * Define a mapping of valid process names to their corresponding file paths.
 * This approach is more secure than direct string concatenation.
 * 
 * @var array $procMap A mapping of process names to their file paths.
 */
$procMap = [
    'summary' => __DIR__ . '/ajax/ajax.summary.php',
    'latestversion' => __DIR__ . '/ajax/ajax.latestversion.php',
    'apache' => __DIR__ . '/ajax/ajax.apache.php',
    'mailpit' => __DIR__ . '/ajax/ajax.mailpit.php',
    'memcached' => __DIR__ . '/ajax/ajax.memcached.php',
    'mariadb' => __DIR__ . '/ajax/ajax.mariadb.php',
    'mysql' => __DIR__ . '/ajax/ajax.mysql.php',
    'nodejs' => __DIR__ . '/ajax/ajax.nodejs.php',
    'php' => __DIR__ . '/ajax/ajax.php.php',
    'postgresql' => __DIR__ . '/ajax/ajax.postgresql.php',
    'xlight' => __DIR__ . '/ajax/ajax.xlight.php',
    'quickpick' => __DIR__ . '/ajax/ajax.quickpick.php'
];

// Handle QuickPick loading separately
if (isset($_POST['proc']) && $_POST['proc'] === 'load_quickpick') {
    // Set headers for JSON response
    header('Content-Type: application/json');
    
    // Disable output buffering to prevent any additional content
    if (ob_get_level()) ob_end_clean();
    
    try {
        // Include the QuickPick class if not already loaded
        if (!class_exists('QuickPick', false)) {
            require_once __DIR__ . '/../../classes/actions/class.action.quickPick.php';
        }
        
        // Create QuickPick instance
        $quickPick = new QuickPick();
        
        // Get images path
        global $bearsamppHomepage;
        $imagesPath = $bearsamppHomepage->getImagesPath();
        
        // Load QuickPick menu
        $html = $quickPick->loadQuickpick($imagesPath);
        
        // Return success response with HTML
        echo json_encode([
            'success' => true,
            'html' => $html
        ]);
        exit;
    } catch (Exception $e) {
        // Return error response
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// Handle QuickPick get_modules action
if (isset($_POST['proc']) && $_POST['proc'] === 'quickpick' && isset($_POST['action']) && $_POST['action'] === 'get_modules') {
    // Set headers for JSON response
    header('Content-Type: application/json');
    
    // Disable output buffering to prevent any additional content
    if (ob_get_level()) ob_end_clean();
    
    try {
        // Include the QuickPick class if not already loaded
        if (!class_exists('QuickPick', false)) {
            require_once __DIR__ . '/../../classes/actions/class.action.quickPick.php';
        }
        
        $quickPick = new QuickPick();
        
        // Get modules and versions
        $modules = $quickPick->getModules();
        $versions = $quickPick->getVersions();
        
        // Return success response with modules and versions
        echo json_encode([
            'success' => true,
            'modules' => $modules,
            'versions' => $versions
        ]);
        exit;
    } catch (Exception $e) {
        // Return error response
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

/**
 * Clean and retrieve the 'proc' POST variable.
 *
 * Util::cleanPostVar is assumed to be a method that sanitizes the input to prevent security issues such as SQL injection or XSS.
 *
 * @var string $proc The cleaned 'proc' parameter from the POST request.
 */
$proc = Util::cleanPostVar('proc', 'text');  // Ensure 'proc' is cleaned and read correctly

/**
 * Check if the cleaned 'proc' parameter exists in our secure mapping.
 * If valid, include the corresponding AJAX handler file using the pre-defined path.
 * If not valid, return a JSON error message.
 */
if (isset($procMap[$proc]) && file_exists($procMap[$proc])) {
    /**
     * Include the corresponding AJAX handler file based on the secure mapping.
     */
    // Set headers for JSON response
    header('Content-Type: application/json');
    
    // If we're handling quickpick, ensure the class is loaded
    if ($proc === 'quickpick' && !class_exists('QuickPick', false)) {
        require_once __DIR__ . '/../../classes/actions/class.action.quickPick.php';
    }
    
    include $procMap[$proc];
} else {
    /**
     * Handle the case where the 'proc' parameter is not valid.
     * Return a JSON encoded error message indicating the invalid parameter.
     */
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid proc parameter: ' . $proc]);
}
