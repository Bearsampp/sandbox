<?php
/*
 * Copyright (c) 2022 - 2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

define('APP_AUTHOR_NAME', '/N6REJ');
define('APP_TITLE', 'Bearsampp');
define('APP_WEBSITE', 'https://bearsampp.com');
define('APP_LICENSE', 'GPL3 License');
define('APP_GITHUB_USER', 'Bearsampp');
define('APP_GITHUB_REPO', 'Bearsampp');
define('APP_GITHUB_TOKEN', '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');
define('APP_GITHUB_USERAGENT', 'Bearsampp');
define('APP_GITHUB_LATEST_URL', 'https://api.github.com/repos/' . APP_GITHUB_USER . '/' . APP_GITHUB_REPO . '/releases/latest');
$appGithubHeader = array(
    'Accept: application/vnd.github+json',
    'Authorization: Bearer ' . APP_GITHUB_TOKEN,
    'User-Agent: ' . APP_GITHUB_USERAGENT,
    'X-GitHub-Api-Version: 2022-11-28'
);

define('RETURN_TAB', '	');


// isRoot
require_once dirname(__FILE__) . '/classes/class.root.php';
$bearsamppRoot = new Root(dirname(__FILE__));
$bearsamppRoot->register();

// Process action
$bearsamppAction = new Action();
$bearsamppAction->process();
// Stop loading
if ($bearsamppRoot->isRoot()) {
    Util::stopLoading();
}
