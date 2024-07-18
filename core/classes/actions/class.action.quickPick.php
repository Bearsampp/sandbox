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
    private $modules = [
        'Adminer'     => ['type' => 'application'],
        'Apache'      => ['type' => 'binary'],
        'Composer'    => ['type' => 'tools'],
        'ConsoleZ'    => ['type' => 'tools'],
        'Ghostscript' => ['type' => 'tools'],
        'Git'         => ['type' => 'tools'],
        'Mailpit'     => ['type' => 'binary'],
        'MariaDB'     => ['type' => 'binary'],
        'Memcached'   => ['type' => 'binary'],
        'MySQL'       => ['type' => 'binary'],
        'Ngrok'       => ['type' => 'tools'],
        'NodeJS'      => ['type' => 'binary'],
        'Perl'        => ['type' => 'tools'],
        'PHP'         => ['type' => 'binary'],
        'PhpMyAdmin'  => ['type' => 'application'],
        'PhpPgAdmin'  => ['type' => 'application'],
        'PostgreSQL'  => ['type' => 'binary'],
        'Python'      => ['type' => 'tools'],
        'Ruby'        => ['type' => 'tools'],
        'Webgrind'    => ['type' => 'application'],
        'Xlight'      => ['type' => 'binary'],
        'Yarn'        => ['type' => 'tools']
    ];

    const API_KEY = "4abe15e5-95f2-4663-ad12-eadb245b28b4";
    const API_URL = "https://bearsampp.com/index.php?option=com_osmembership&task=api.get_active_plan_ids&api_key=";

    /**
     * Retrieves the list of available modules.
     *
     * @return array An array of module names.
     */
    public function getModules()
    {
        return array_keys( $this->modules );
    }

    /**
     * Fetches the versions of a specified module from a remote repository.
     *
     * @param   string  $module  The name of the module.
     *
     * @return array An associative array containing the module name and its versions or an error message.
     */
    public function getModuleVersions($module)
    {
        global $bearsamppCore;
        Util::logDebug( "getModuleVersions called for module: " . $module );

        $url = 'https://raw.githubusercontent.com/Bearsampp/module-' . strtolower( $module ) . '/main/releases.properties';
        Util::logDebug( "Fetching URL: " . $url );

        $content = @file_get_contents( $url );
        if ( $content === false ) {
            Util::logError( "Error fetching content from URL: " . $url );

            return ['error' => 'Error fetching version'];
        }

        Util::logDebug( "Fetched content: " . $content );

        $versions = []; // Initialize the versions array

        if ( !empty( $content ) ) {
            // Parse the content to get versions
            $Versions = $this->getVersions( $content );
            Util::logDebug( " versions: " . print_r( $Versions, true ) );

            // Iterate over the $modules array and add the "version" key
            foreach ( $this->modules as $moduleName => &$moduleType ) {
                if ( strtolower( $moduleName ) === strtolower( $module ) ) {
                    $moduleType['version'] = $Versions;
                    $versions              = $Versions; // Populate the versions array
                }
            }
        }
        else {
            Util::logError( "Error fetching version for module: " . $module );
            $versions[$module] = 'Error fetching version';
        }

        Util::logDebug( "Returning versions: " . print_r( $versions, true ) );
        Util::logDebug( 'Module info ' . print_r( $moduleType, true ) );

        return $versions;
    }

    /**
     * Fetches the URL of a specified module version from a remote repository.
     *
     * @param   string  $module   The name of the module.
     * @param   string  $version  The version of the module.
     *
     * @return string|array The URL of the specified module version or an error message.
     */
    public function getModuleUrl($module, $version)
    {
        global $bearsamppCore;
        Util::logDebug( 'getModuleUrl called for module: ' . $module . ' version: ' . $version );

        $url = 'https://raw.githubusercontent.com/Bearsampp/module-' . strtolower( $module ) . '/main/releases.properties';
        Util::logDebug( 'Fetching URL: ' . $url );

        $content = @file_get_contents( $url );
        if ( $content === false ) {
            Util::logError( 'Error fetching content from URL: ' . $url );

            return ['error' => 'Error fetching version URL'];
        }

        Util::logDebug( 'Fetched content: ' . $content );

        $lines = explode( "\n", $content );
        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( !empty( $line ) ) {
                $parts = explode( '=', $line, 2 );
                if ( count( $parts ) == 2 ) {
                    list( $lineVersion, $lineUrl ) = $parts;
                    if ( trim( $lineVersion ) === $version ) {
                        Util::logDebug( 'Found URL for version: ' . $version . ' URL: ' . $lineUrl );

                        return (string) trim( $lineUrl );
                    }
                }
                else {
                    Util::logError( 'Invalid line format: ' . $line );
                }
            }
        }

        Util::logError( 'Version not found: ' . $version );

        return ['error' => 'Version not found'];
    }

    /**
     * Parses the content of a releases.properties file to extract version keys.
     *
     * This method processes the content of a releases.properties file, extracting
     * only the version keys from each line. Each line is expected to be in the format
     * "version=url". If a line does not contain an '=' character, it is logged as an error.
     *
     * @param   string  $content  The content of the releases.properties file.
     *
     * @return array An array of version keys.
     */
    public function getVersions($content)
    {
        $lines    = explode( "\n", $content );
        $versions = [];

        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( !empty( $line ) ) {
                $parts = explode( '=', $line, 2 );
                if ( count( $parts ) == 2 ) {
                    list( $version, $url ) = $parts;
                    $versions[] = trim( $version ); // Collect only the version key
                }
                else {
                    // Handle the case where the line does not contain an '=' character
                    Util::logError( 'Invalid line format: ' . $line );
                }
            }
        }

        Util::logDebug( 'Parsed versions: ' . print_r( $versions, true ) );

        return $versions;
    }

    /**
     * Validates the format of a given license key.
     *
     * @param   string  $licenseKey  The license key to validate.
     *
     * @return bool True if the license key is valid, false otherwise.
     */
    public function isLicenseKeyValid()
    {
        global $bearsamppConfig;

        Util::logError( 'isLicenseKeyValid method called.' );

        // Ensure the global config is available
        if ( !isset( $bearsamppConfig ) ) {
            Util::logError( 'Global configuration is not set.' );

            return false;
        }

        $licenseKey = $bearsamppConfig->getLicenseKey();
        Util::logDebug( 'LicenseKey is: ' . $licenseKey );

        // Ensure the license key is not empty
        if ( empty( $licenseKey ) ) {
            Util::logError( 'License key is empty.' );

            return false;
        }

        $url = self::API_URL . self::API_KEY . '&username=' . $licenseKey;
        Util::logDebug( 'API URL: ' . $url );

        $response = @file_get_contents( $url );

        // Check if the response is false
        if ( $response === false ) {
            $error = error_get_last();
            Util::logError( 'Error fetching API response: ' . $error['message'] );

            return false;
        }

        Util::logDebug( 'API response: ' . $response );

        $data = json_decode( $response, true );

        // Check if the JSON decoding was successful
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            Util::logError( 'Error decoding JSON response: ' . json_last_error_msg() );

            return false;
        }

        // Validate the response data
        if ( isset( $data['success'] ) && $data['success'] === true ) {
            Util::logDebug( "License key valid: " . $licenseKey );

            return true;
        }

        Util::logError( 'Invalid license key: ' . $licenseKey );

        return false;
    }

    /**
     * Installs a specified module with a given version.
     *
     * @param   string  $module   The name of the module to install.
     * @param   string  $version  The version of the module to install.
     */
    public function installModule($module, $version)
    {
        Util::logDebug( 'install module routine instantiated ' . $module . ' ' . $version );

        // Check if the module exists in the list of available modules
        if ( !isset( $this->modules[$module] ) ) {
            Util::logError( 'Module not found: ' . $module );

            return ['error' => 'Module not found'];
        }

        Util::logDebug( 'Module found: ' . $module );

        // Fetch the module versions to ensure the specified version is available
        $moduleType = $this->modules[$module]['type'];
        $moduleUrl  = $this->getModuleUrl( $module, $version ); // Pass both module and version

        if ( is_array( $moduleUrl ) && isset( $moduleUrl['error'] ) ) {
            Util::logError( 'Error fetching module URL: ' . $moduleUrl['error'] );

            return $moduleUrl;
        }

        $response = $this->fetchAndUnzipModule( $moduleUrl, $module );
        Util::logDebug( "Response is: " . print_r( $response, true ) );

        return $response;
    }

    // Assuming other methods and properties are defined here

    /**
     * Fetches the module URL and stores it in /tmp, then unzips the file based on its extension.
     *
     * @param   string  $moduleUrl  The URL of the module to fetch.
     *
     * @return array An array containing the status and message.
     */
    public function fetchAndUnzipModule($moduleUrl, $module)
    {
        global $bearsamppRoot, $bearsamppCore;
        $tmpDir     = $bearsamppRoot->getTmpPath();
        $fileName   = basename( $moduleUrl );
        $filePath   = $tmpDir . '/' . $fileName;
        $moduleName = strtolower( $module );;
        $moduleType = $this->modules[$module]['type'];


        if ( $moduleType === "application" ) {
            $destination = $bearsamppRoot->getAppsPath() . '/' . $moduleName . '/';
        }
        elseif ( $moduleType === "binary" ) {
            $destination = $bearsamppRoot->getBinPath() . '/' . $moduleName . '/';
        }
        elseif ( $moduleType === "tools" ) {
            $destination = $bearsamppRoot->getToolsPath() . '/' . $moduleName . '/';
        }
        else {
            $destination = '';
        }

        // Fetch the file from the URL
        $fileContent = @file_get_contents( $moduleUrl );
        if ( $fileContent === false ) {
            Util::logError( 'Error fetching content from URL: ' . $moduleUrl );

            return ['error' => 'Error fetching module'];
        }

        // Save the file to /tmp
        if ( file_put_contents( $filePath, $fileContent ) === false ) {
            Util::logError( 'Error saving file to: ' . $filePath );

            return ['error' => 'Error saving module'];
        }

        Util::logDebug( 'File saved to: ' . $filePath );

        // Determine the file extension and call the appropriate unzipping function
        $fileExtension = pathinfo( $filePath, PATHINFO_EXTENSION );
        Util::logDebug( 'File extension: ' . $fileExtension );
        if ( $fileExtension === '7z' ) {
            if ( !$bearsamppCore->unzip7zFile( $filePath, $destination ) ) {
                return ['error' => 'Failed to unzip .7z file'];
            }
        }
        elseif ( $fileExtension === 'zip' ) {
            if ( !$bearsamppCore->unzipFile( $filePath, $destination ) ) {
                return ['error' => 'Failed to unzip .zip file'];
            }
        }
        else {
            Util::logError( 'Unsupported file extension: ' . $fileExtension );

            return ['error' => 'Unsupported file extension'];
        }

        return ['success' => 'Module fetched and unzipped successfully'];
    }
}
