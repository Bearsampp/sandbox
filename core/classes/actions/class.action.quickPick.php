<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

class QuickPick
{
    /**
     * Define an array of modules containing our module names.
     */
    private $modules = [
        'Adminer',
        'Apache',
        'Composer',
        'ConsoleZ',
        'Ghostscript',
        'Git',
        'Mailpit',
        'MariaDB',
        'Memcached',
        'MySQL',
        'Ngrok',
        'NodeJS',
        'Perl',
        'PHP',
        'PostgreSQL',
        'PhpMyAdmin',
        'PhpPgAdmin',
        'Python',
        'Ruby',
        'Webgrind',
        'Xlight',
        'Yarn'
    ];

    const GH_PREFIX = 'https://github.com/Bearsampp/module-';

    public function getModules()
    {
        return $this->modules;
    }

    public static function getModuleVersions($modules) {
        $versions = [];
        foreach ($modules as $module) {
            $url = self::GH_PREFIX . strtolower($module) . '/blob/main/releases.properties';
            $content = file_get_contents($url);
            if ($content !== false) {
                $versions[$module] = self::parseReleasesProperties($content);
            } else {
                $versions[$module] = 'Error fetching version';
            }
        }
        return $versions;
    }

    private static function parseReleasesProperties($content) {
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (strpos($line, 'version=') === 0) {
                return trim(substr($line, strlen('version=')));
            }
        }
        return 'Version not found';
    }

    public function getLicenseKey()
    {
        if (!file_exists($this->configFilePath)) {
            Util::logError('Config file not found: ' . $this->configFilePath);
            return false;
        }

        $config = parse_ini_file($this->configFilePath);
        if ($config === false || !isset($config['licenseKey'])) {
            Util::logError('License key not found in config file: ' . $this->configFilePath);
            return false;
        }

        return $config['licenseKey'];
    }

    public function isLicenseKeyValid($licenseKey)
    {
        // Implement your validation logic here
        // For example, check if the license key matches a specific pattern
        if (preg_match('/^[A-Z0-9]{16}$/', $licenseKey)) {
            return true;
        }

        Util::logError('Invalid license key: ' . $licenseKey);
        return false;
    }

    public function validateLicenseKey()
    {
        $licenseKey = $this->getLicenseKey();
        if ($licenseKey === false) {
            return false;
        }

        return $this->isLicenseKeyValid($licenseKey);
    }
}
