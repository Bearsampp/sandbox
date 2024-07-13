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
     * @var array $modules
     *
     * An associative array where the key is the module name and the value is an array containing the module type.
     * The module type can be one of the following:
     * - 'application'
     * - 'binary'
     * - 'tool'
     */
    private static $modules = [
        'Adminer'     => ['type' => 'application'],
        'Apache'      => ['type' => 'binary'],
        'Composer'    => ['type' => 'tool'],
        'ConsoleZ'    => ['type' => 'tool'],
        'Ghostscript' => ['type' => 'tool'],
        'Git'         => ['type' => 'tool'],
        'Mailpit'     => ['type' => 'binary'],
        'MariaDB'     => ['type' => 'binary'],
        'Memcached'   => ['type' => 'binary'],
        'MySQL'       => ['type' => 'binary'],
        'Ngrok'       => ['type' => 'tool'],
        'NodeJS'      => ['type' => 'binary'],
        'Perl'        => ['type' => 'tool'],
        'PHP'         => ['type' => 'binary'],
        'PhpMyAdmin'  => ['type' => 'application'],
        'PhpPgAdmin'  => ['type' => 'application'],
        'PostgreSQL'  => ['type' => 'binary'],
        'Python'      => ['type' => 'tool'],
        'Ruby'        => ['type' => 'tool'],
        'Webgrind'    => ['type' => 'application'],
        'Xlight'      => ['type' => 'binary'],
        'Yarn'        => ['type' => 'tool']
    ];

    /**
     * QuickPick constructor.
     *
     * This constructor initializes the QuickPick class by retrieving the list of available modules
     * and fetching their respective versions. It calls the `getModules` method to obtain the module names
     * and the `getModuleVersions` method to fetch the versions of each module from a remote repository.
     *
     * @see QuickPick::getModules()
     * @see QuickPick::getModuleVersions()
     */
    public function __construct()
    {
        $this->getModules();

        /* populate versions */
        foreach ( self::$modules as $moduleName => $moduleInfo ) {
            $this->getModuleVersions( $moduleName );
        }
    }

    /**
     * Retrieves the list of available modules.
     *
     * @return array An array of module names.
     */
    public static function getModules()
    {
        return array_keys( self::$modules );
    }

    /**
     * Fetches the versions of a specified module from a remote repository.
     *
     * @param   string  $module  The name of the module.
     *
     * @return array An associative array containing the module name and its versions or an error message.
     */
    public static function getModuleVersions($module)
    {
        global $bearsamppCore;
        $url     = 'https://raw.githubusercontent.com/Bearsampp/module-' . strtolower( $module ) . '/main/releases.properties';
        $content = Util::getGithubRawUrl( $url );
        Util::logError( "Text output " . $content );

        $versions = []; // Initialize the versions array

        if ( !empty( $content ) ) {
            // Parse the content to get versions
            $parsedVersions = self::parseReleasesProperties( $content );

            // Iterate over the $modules array and add the "version" key
            foreach ( self::$modules as $moduleName => &$moduleInfo ) {
                if ( strtolower( $moduleName ) === strtolower( $module ) ) {
                    $moduleInfo['version'] = $parsedVersions;
                    $versions              = $parsedVersions; // Populate the versions array
                }
            }
        }
        else {
            $versions[$module] = 'Error fetching version';
        }

        return $versions;
    }

    /**
     * Parses the content of a releases.properties file to extract version information.
     *
     * @param   string  $content  The content of the releases.properties file.
     *
     * @return array An associative array of versions and their corresponding URLs.
     */
    private static function parseReleasesProperties($content)
    {
        $lines    = explode( "\n", $content );
        $versions = [];

        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( !empty( $line ) ) {
                list( $version, $url ) = explode( '=', $line, 2 );
                $versions[trim( $version )] = trim( $url );
            }
        }

        return $versions;
    }

    /**
     * Get the type of a specific module.
     *
     * @param   string  $moduleName  The name of the module.
     *
     * @return string|null The type of the module, or null if the module does not exist.
     */
    public static function getModuleType($moduleName)
    {
        return isset( self::$modules[$moduleName] ) ? self::$modules[$moduleName]['type'] : null;
    }

    /**
     * Retrieves the license key from the configuration file.
     *
     * @return string|false The license key if found, or false if not found or an error occurs.
     */
    public function getLicenseKey()
    {
        if ( !file_exists( $this->configFilePath ) ) {
            Util::logError( 'Config file not found: ' . $this->configFilePath );

            return false;
        }

        $config = parse_ini_file( $this->configFilePath );
        if ( $config === false || !isset( $config['licenseKey'] ) ) {
            Util::logError( 'License key not found in config file: ' . $this->configFilePath );

            return false;
        }

        return $config['licenseKey'];
    }

    /**
     * Validates the format of a given license key.
     *
     * @param   string  $licenseKey  The license key to validate.
     *
     * @return bool True if the license key is valid, false otherwise.
     */
    public function isLicenseKeyValid($licenseKey)
    {
        // Implement your validation logic here
        // For example, check if the license key matches a specific pattern
        if ( preg_match( '/^[A-Z0-9]{16}$/', $licenseKey ) ) {
            return true;
        }

        Util::logError( 'Invalid license key: ' . $licenseKey );

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
        if ( $licenseKey === false ) {
            return false;
        }

        return $this->isLicenseKeyValid( $licenseKey );
    }

    /**
     * Installs a specified module with a given version.
     *
     * @param   string  $module   The name of the module to install.
     * @param   string  $version  The version of the module to install.
     */
    public function installModule($module, $version)
    {
        // Implementation for installing the module goes here
    }
}
