<?php
/*
 * Copyright (c) 2022 - 2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

global $bearsamppCore, $bearsamppHomepage, $bearsamppLang;
const APP_AUTHOR_NAME = '/N6REJ';
const APP_TITLE = 'Bearsampp';
const APP_WEBSITE = 'https://bearsampp.com';
const APP_LICENSE = 'GPL3 License';
const APP_GITHUB_USER = 'Bearsampp';
const APP_GITHUB_REPO = 'Bearsampp';
const APP_GITHUB_TOKEN = 'Bearsampp';
const APP_GITHUB_LATEST_URL = 'https://api.github.com/repos/' . APP_GITHUB_USER . '/' . APP_GITHUB_REPO . '/releases/latest';
const RETURN_TAB = '	';

// isRoot
require_once __DIR__ . '/classes/class.root.php';
$bearsamppRoot = new Root(__DIR__);
$bearsamppRoot->register();

// Process action
$bearsamppAction = new Action();
$bearsamppAction->process();

// Stop loading
if ($bearsamppRoot->isRoot()) {
    Util::stopLoading();
}

/* THESE MUST BE DEFINES -- DO NOT MOVE THESE FILES WITHOUT HEAVY TESTING -- */
define('APP_VERSION', $bearsamppCore->getAppVersion());
define('RESOURCES_PATH', $bearsamppHomepage->getResourcesPath());
