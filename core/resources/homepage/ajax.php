<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Include the root configuration file.
 */
include_once __DIR__ . '/../../root.php';

/**
 * List of valid process names that can be included.
 *
 * @var array
 */
$procs = array(
    'summary',
    'latestversion',
    'apache',
    'filezilla',
    'mailhog',
    'memcached',
    'mariadb',
    'mysql',
    'nodejs',
    'php',
    'postgresql'
);

/**
 * Clean and retrieve the 'proc' POST variable.
 *
 * @var string $proc The cleaned 'proc' parameter.
 */
$proc = Util::cleanPostVar('proc', 'text');  // Ensure 'proc' is cleaned and read correctly

/**
 * Include the corresponding AJAX file if 'proc' is valid.
 * Otherwise, return an error message in JSON format.
 */
if (in_array($proc, $procs)) {
    include 'ajax/ajax.' . $proc . '.php';  // This line should correctly include 'ajax.latestversion.php'
} else {
    // It's a good practice to handle the case where 'proc' is not valid
    echo json_encode(['error' => 'Invalid proc parameter']);
}
