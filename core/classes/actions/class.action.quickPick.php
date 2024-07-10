<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class QuickPick
 *
 * The QuickPick class provides functionalities for managing and installing various modules
 * within the Bearsampp application. It includes methods for retrieving available modules,
 * fetching module versions, parsing release properties, and validating license keys.
 */
class QuickPick
{
    /**
     * @var array $modules An associative array of module names and their types.
     */
    private static $modules = [
        'Adminer'     => 'application',
        'Apache'      => 'binary',
        'Composer'    => 'tool',
        'ConsoleZ'    => 'tool',
        'Ghostscript' => 'tool',
        'Git'         => 'tool',
        'Mailpit'     => 'binary',
        'MariaDB'     => 'binary',
        'Memcached'   => 'binary',
        'MySQL'       => 'binary',
        'Ngrok'       => 'tool',
        'NodeJS'      => 'binary',
        'Perl'        => 'tool',
        'PHP'         => 'binary',
        'PhpMyAdmin'  => 'application',
        'PhpPgAdmin'  => 'application',
        'PostgreSQL'  => 'binary',
        'Python'      => 'tool',
        'Ruby'        => 'tool',
        'Webgrind'    => 'application',
        'Xlight'      => 'binary',
        'Yarn'        => 'tool'
    ];

    /**
     * Retrieves the list of available modules.
     *
     * @return array An array of module names.
     */
    public static function getModules()
    {
        return array_keys(self::$modules);
    }

    /**
     * Fetches the versions of a specified module from a remote repository.
     *
     * @param string $module The name of the module.
     * @return array An associative array containing the module name and its versions or an error message.
     */
    public static function getModuleVersions($module)
    {
        global $bearsamppCore;
        $url      = 'https://raw.githubusercontent.com/Bearsampp/module-' . strtolower($module) . '/main/releases.properties';
        $content  = Util::getGithubRawUrl($url);
        Util::logError("Text output " . $content);

        if (!empty($content)) {
            $versions[$module] = self::parseReleasesProperties($content);
        } else {
            $versions[$module] = 'Error fetching version';
        }

        return $versions;
    }

    /**
     * Parses the content of a releases.properties file to extract version information.
     *
     * @param string $content The content of the releases.properties file.
     * @return array An associative array of versions and their corresponding URLs.
     */
    private static function parseReleasesProperties($content)
    {
        $lines    = explode("\n", $content);
        $versions = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                list($version, $url) = explode('=', $line, 2);
                $versions[trim($version)] = trim($url);
            }
        }

        return $versions;
    }

    /**
     * Retrieves the license key from the configuration file.
     *
     * @return string|false The license key if found, or false if not found or an error occurs.
     */
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

    /**
     * Validates the format of a given license key.
     *
     * @param string $licenseKey The license key to validate.
     * @return bool True if the license key is valid, false otherwise.
     */
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

    /**
     * Validates the license key by retrieving it from the configuration file and checking its format.
     *
     * @return bool True if the license key is valid, false otherwise.
     */
    public function validateLicenseKey()
    {
        $licenseKey = $this->getLicenseKey();
        if ($licenseKey === false) {
            return false;
        }

        return $this->isLicenseKeyValid($licenseKey);
    }

    /**
     * Installs a specified module with a given version.
     *
     * @param string $module The name of the module to install.
     * @param string $version The version of the module to install.
     */
    public function installModule($module, $version)
    {
        // Implementation for installing the module goes here
    }
}
