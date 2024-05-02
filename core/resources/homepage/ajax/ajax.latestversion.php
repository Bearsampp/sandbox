<?php
/*
 * Copyright (c) 2022 - 2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */
global $appGithubHeader, $bearsamppLang, $bearsamppCore;

$result = array(
    'display' => false,
    'download' => '',
);

// Assuming getAppVersion() returns the current version number
$bearsamppCurrentVersion = $bearsamppCore->getAppVersion();

// Assuming getLatestVersion now returns an array with version and URL
$latestVersionData = Util::getLatestVersion(APP_GITHUB_LATEST_URL, APP_GITHUB_TOKEN, $appGithubHeader);

if ($latestVersionData != null) {
    $bearsamppLatestVersion = $latestVersionData['version'];
    $latestVersionUrl = $latestVersionData['url']; // URL of the latest version

    $currentVersionDate = DateTime::createFromFormat('Y.n.j', $bearsamppCurrentVersion);
    $latestVersionDate = DateTime::createFromFormat('Y.n.j', $bearsamppLatestVersion);

    if ($currentVersionDate < $latestVersionDate) {
        $result['display'] = true;
        $result['download'] .= '<a role="button" class="btn btn-success fullversionurl" href="' . $latestVersionUrl . '" target="_blank"><i class="fa fa-download"></i> ';
        $result['download'] .= $bearsamppLang->getValue(Lang::DOWNLOAD) . ' <strong>' . APP_TITLE . ' ' . $bearsamppLatestVersion . '</strong><br />';
        $result['download'] .= '<small>bearsampp-' . $bearsamppLatestVersion . '.7z</small></a>';
    }
}
echo json_encode($result);
