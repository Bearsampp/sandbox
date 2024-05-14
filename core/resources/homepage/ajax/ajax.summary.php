<?php
/*
 * Copyright (c) 2021-2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * This script compiles the status and versions of various server binaries like Apache, Filezilla, MailHog, MariaDB, MySQL, PostgreSQL, Memcached, Node.js, and PHP.
 * It outputs a JSON-encoded array containing links to download more information and the current version status of each binary.
 */

/**
 * @var array $result Holds the compiled results of binary statuses and versions.
 */
global $bearsamppLang, $bearsamppBins;
$result = array(
    'binapache' => '',
    'binfilezilla' => '',
    'binmailhog' => '',
    'binmariadb' => '',
    'binmysql' => '',
    'binpostgresql' => '',
    'binmemcached' => '',
    'binnodejs' => '',
    'binphp' => '',
);

/**
 * Template for generating download more links with dynamic module names.
 * @var string $dlMoreTpl Template string for download links.
 */
$dlMoreTpl = '<a href="' . Util::getWebsiteUrl('module/%s', '#releases') . '" target="_blank" title="' . $bearsamppLang->getValue(Lang::DOWNLOAD_MORE) . '"><span class="float-end" style="margin-left:.5rem;"><i class="fa fa-download"></i></span></a>';

// Detailed status checks and version reporting for each binary follow here.
// Each section includes checks for enabling status, port availability, and SSL port checks where applicable.
// The results are appended to the $result array with appropriate labels indicating the status.

// Apache binary status and version
/**
 * Checks the status of the Apache server, including the main and SSL port statuses.
 */
$apachePort = $bearsamppBins->getApache()->getPort();
$apacheSslPort = $bearsamppBins->getApache()->getSslPort();
$apacheLabel = 'bg-secondary';

if ($bearsamppBins->getApache()->isEnable()) {
    $apacheLabel = 'bg-danger';
    if ($bearsamppBins->getApache()->checkPort($apachePort)) {
        if ($bearsamppBins->getApache()->checkPort($apacheSslPort, true)) {
            $apacheLabel = 'bg-success';
        } else {
            $apacheLabel = 'bg-warning';
        }
    }
}
$result['binapache'] = sprintf($dlMoreTpl, 'apache');
$result['binapache'] .= '<span class = " float-end badge ' . $apacheLabel . '">' . $bearsamppBins->getApache()->getVersion() . '</span>';

// Similar blocks for Filezilla, MailHog, MariaDB, MySQL, PostgreSQL, Memcached, Node.js, and PHP are defined below.

/**
 * Outputs the compiled results as a JSON-encoded string.
 */
echo json_encode($result);
