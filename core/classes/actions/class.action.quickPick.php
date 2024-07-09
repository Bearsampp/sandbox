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

    const GH_PREFIX = 'https://api.github.com/repos/Bearsampp/module-';

    public static function getModules()
    {
        return array_keys( self::$modules );
    }

public static function getModuleVersions($module)
{
    $versions = [];
    $content = Util::getApiJson(self::GH_PREFIX . strtolower($module) . '/contents/releases.properties');

    if ($content !== '') {
        // Decode the JSON string into a PHP array
        $jsonArray = json_decode($content, true);

        // Check if decoding was successful and the 'content' key exists
        if (json_last_error() === JSON_ERROR_NONE && isset($jsonArray['content'])) {
            $contentValue = $jsonArray['content'];
            $versions[$module] = self::parseReleasesProperties($contentValue);
        } else {
            $versions[$module] = 'Error fetching version';
        }
    } else {
        $versions[$module] = 'Error fetching version';
    }
    return $versions;
}

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

    public function validateLicenseKey()
    {
        $licenseKey = $this->getLicenseKey();
        if ( $licenseKey === false ) {
            return false;
        }

        return $this->isLicenseKeyValid( $licenseKey );
    }

    public function installModule($module, $version)
    {

    }
}
