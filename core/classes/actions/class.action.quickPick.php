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
    const API_KEY = '4abe15e5-95f2-4663-ad12-eadb245b28b4';
    const API_URL = 'https://bearsampp.com/index.php?option=com_osmembership&task=api.get_active_plan_ids&api_key=';

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

    /**
     * A list of URLs pointing to the release properties files for various Bearsampp modules.
     * These URLs are used to fetch the latest release information for each module.
     *
     * @var array $urls An array of URLs for the release properties files.
     */
    private $urls = [
        'https://raw.githubusercontent.com/Bearsampp/module-adminer/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-apache/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-composer/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-consolez/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-filezilla/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-ghostscript/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-git/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-gitlist/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-mailhog/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-mariadb/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-memcached/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-mysql/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-ngrok/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-nodejs/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-perl/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-php/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-phpmemadmin/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-phpmyadmin/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-phppgadmin/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-postgresql/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-python/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-ruby/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-webgrind/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-xdc/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-xlight/main/releases.properties',
        'https://raw.githubusercontent.com/Bearsampp/module-yarn/main/releases.properties'
    ];

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
     * Loads the QuickPick interface with the available modules and their versions.
     *
     * @param   array   $modules     An array of available modules.
     * @param   string  $imagesPath  The path to the images directory.
     *
     * @return string The HTML content of the QuickPick interface.
     */
    public function loadQuickpick($modules, $imagesPath)
    {
        $test     = $this->checkQuickpickLocal();
        $versions = $this->getModuleVersions( $modules );

        return $this->getQuickpickMenu( $modules, $imagesPath );
    }

    /**
     * Retrieves the list of available versions for a specified module.
     *
     * This method fetches the QuickPick JSON data and iterates through the entries to find
     * all versions associated with the specified module. If no versions are found, an error
     * message is logged and returned.
     *
     * @param   string  $module        The name of the module for which to retrieve versions.
     *
     * @return array An array of version strings for the specified module, or an error message if no versions are found.
     * @global object   $bearsamppCore The core object providing access to application resources.
     *
     */
    public function getModuleVersions($module)
    {
        global $bearsamppCore;

        Util::logDebug( 'getModuleVersions called for module: ' . $module );

        $data = $this->getQuickpickJson();

        $versions = [];
        foreach ( $data as $entry ) {
            if ( isset( $entry['module'] ) && is_string( $entry['module'] ) && strtolower( $entry['module'] ) === strtolower( (string) $module ) ) {
                $versions[] = $entry['version'];
            }
        }

        if ( empty( $versions ) ) {
            Util::logError( 'No versions found for module: ' . $module );

            return ['error' => 'No versions found'];
        }

        Util::logDebug( 'Found versions for module: ' . $module . ' Versions: ' . implode( ', ', $versions ) );

        return $versions;
    }


    /**
     * Fetches the URL of a specified module version from the local quickpick-releases.json file.
     *
     * This method reads the quickpick-releases.json file to find the URL associated with the given module
     * and version. It logs the process and returns the URL if found, or an error message if not.
     *
     * @param   string  $module        The name of the module.
     * @param   string  $version       The version of the module.
     *
     * @return string|array The URL of the specified module version or an error message if the version is not found.
     * @global object   $bearsamppCore The core object providing access to application resources.
     */
    public function getModuleUrl($module, $version)
    {
        global $bearsamppCore;

        Util::logDebug( 'getModuleUrl called for module: ' . $module . ' version: ' . $version );

        $data = $this->getQuickpickJson();

        foreach ( $data as $entry ) {
            if ( isset( $entry['module'] ) && strtolower( $entry['module'] ) === strtolower( $module ) &&
                isset( $entry['version'] ) && $entry['version'] === $version ) {
                Util::logDebug( 'Found URL for version: ' . $version . ' URL: ' . $entry['url'] );

                return (string) trim( $entry['url'] );
            }
        }

        Util::logError( 'Version not found: ' . $version );

        return ['error' => 'Version not found'];
    }

    /**
     * Retrieves the QuickPick JSON data.
     *
     * This method fetches the QuickPick JSON file from the resources path, logs the process,
     * and handles any errors that may occur during the file operations or JSON decoding.
     *
     * @return array The decoded JSON data as an associative array, or an error message if an issue occurs.
     * @global object $bearsamppCore The core object providing access to application resources.
     *
     */
    public function getQuickpickJson()
    {
        global $bearsamppCore;
        $jsonFilePath = $bearsamppCore->getResourcesPath() . '/quickpick-releases.json';
        Util::logDebug( 'Fetching JSON file: ' . $jsonFilePath );

        if ( !file_exists( $jsonFilePath ) ) {
            Util::logError( 'JSON file not found: ' . $jsonFilePath );

            return ['error' => 'JSON file not found'];
        }

        $content = @file_get_contents( $jsonFilePath );
        if ( $content === false ) {
            Util::logError( 'Error fetching content from JSON file: ' . $jsonFilePath );

            return ['error' => 'Error fetching JSON file'];
        }

        $data = json_decode( $content, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            Util::logError( 'Error decoding JSON content: ' . json_last_error_msg() );

            return ['error' => 'Error decoding JSON content'];
        }

        return $data;
    }

    /**
     * Validates the format of a given username key by checking it against an external API.
     *
     * This method performs several checks to ensure the validity of the username key:
     * 1. Logs the method call.
     * 2. Ensures the global configuration is available.
     * 3. Retrieves the username key from the global configuration.
     * 4. Ensures the username key is not empty.
     * 5. Constructs the API URL using the username key.
     * 6. Fetches the API response.
     * 7. Decodes the JSON response.
     * 8. Validates the response data.
     *
     * @return bool True if the username key is valid, false otherwise.
     * @global object $bearsamppConfig The global configuration object.
     *
     */
    public function isUsernameKeyValid()
    {
        global $bearsamppConfig;

        Util::logError( 'isusernameKeyValid method called.' );

        // Ensure the global config is available
        if ( !isset( $bearsamppConfig ) ) {
            Util::logError( 'Global configuration is not set.' );

            return false;
        }

        $usernameKey = $bearsamppConfig->getUsernameKey();
        Util::logDebug( 'usernameKey is: ' . $usernameKey );

        // Ensure the license key is not empty
        if ( empty( $usernameKey ) ) {
            Util::logError( 'License key is empty.' );

            return false;
        }

        $url = self::API_URL . self::API_KEY . '&username=' . $usernameKey;
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
        if ( isset( $data['success'] ) && $data['success'] === true && isset( $data['data'] ) && is_array( $data['data'] ) && count( $data['data'] ) > 0 ) {
            Util::logDebug( "License key valid: " . $usernameKey );

            return true;
        }

        Util::logError( 'Invalid license key: ' . $usernameKey );

        return false;
    }

    /**
     * Installs a specified module by fetching its URL and unzipping its contents.
     *
     * This method retrieves the URL of the specified module and version from the QuickPick JSON data.
     * If the URL is found, it fetches and unzips the module. If the URL is not found, it logs an error
     * and returns an error message.
     *
     * @param   string  $module   The name of the module to install.
     * @param   string  $version  The version of the module to install.
     *
     * @return array An array containing the status and message of the installation process.
     *               If successful, it returns the response from the fetchAndUnzipModule method.
     *               If unsuccessful, it returns an error message indicating the issue.
     *
     * @see QuickPick::getQuickpickJson() For retrieving the QuickPick JSON data.
     * @see QuickPick::fetchAndUnzipModule() For fetching and unzipping the module.
     */
    public function installModule($module, $version)
    {
        $data = $this->getQuickpickJson();

        // Find the module URL and module name from the data
        $moduleUrl = '';
        foreach ( $data as $entry ) {
            if ( isset( $entry['module'] ) && strtolower( $entry['module'] ) === strtolower( $module ) &&
                isset( $entry['version'] ) && $entry['version'] === $version ) {
                $moduleUrl = (string) trim( $entry['url'] );
                break;
            }
        }

        if ( empty( $moduleUrl ) ) {
            Util::logError( 'Module URL not found for module: ' . $module . ' version: ' . $version );

            return ['error' => 'Module URL not found'];
        }

        $state = Util::checkInternetState();
        if ( $state ) {

            $response = $this->fetchAndUnzipModule( $moduleUrl, $module );
            Util::logDebug( "Response is: " . print_r( $response, true ) );

            return $response;
        }
    }

    // Assuming other methods and properties are defined here

    /**
     * Fetches the module URL and stores it in /tmp, then unzips the file based on its extension.
     *
     * @param   string  $moduleUrl  The URL of the module to fetch.
     * @param   string  $module     The name of the module.
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

    /**
     * Checks if the QuickPick JSON file exists and is up to date.
     *
     * This method verifies the existence of the QuickPick JSON file and checks its modification time.
     * If the file is older than 24 hours, it recreates the file. If the file does not exist, it also
     * recreates the file.
     *
     * @return bool True if the JSON file exists and is up to date, or was successfully recreated. False otherwise.
     * @global object $bearsamppCore The core object providing access to application resources.
     *
     */
    public function checkQuickpickLocal(): bool
    {
        global $bearsamppCore, $bearsamppConfig;
        $json = $bearsamppCore->getResourcesPath() . '/quickpick-releases.json';

        // Debug statement to print the path being checked
        Util::logDebug( 'Checking path: ' . $json );

        if ( $this->isQuickpickJsonExists( $json ) ) {
            // Check the file modification time
            $fileModTime = filemtime( $json );
            $currentTime = time();
            $timeDiff    = $currentTime - $fileModTime;

            // If the file is older than the configured cache time, recreate it
            if ( $timeDiff > $bearsamppConfig->getCacheTime() ) {
                Util::logDebug( 'Quickpick Releases json file is older than 24 hours, recreating it.' );

                return $this->recreateQuickpickJson( $json );
            }

            Util::logDebug( 'Quickpick Releases json file exists and is up to date.' );

            return true;
        }
        else {
            Util::logError( 'Quickpick Releases json file missing at path: ' . $json );

            return $this->recreateQuickpickJson( $json );
        }
    }

    /**
     * Recreates the QuickPick JSON file.
     *
     * This method attempts to recreate the QuickPick JSON file by calling the createQuickpickJson method.
     * It checks if the file was successfully created and logs the appropriate messages.
     *
     * @param   string  $json  The path to the JSON file to be recreated.
     *
     * @return bool True if the JSON file was successfully created, false otherwise.
     */
    private function recreateQuickpickJson($json): bool
    {
        try {
            $this->createQuickpickJson();
            if ( $this->isQuickpickJsonExists( $json ) ) {
                Util::logDebug( 'Quickpick Releases json file created successfully' );

                return true;
            }
            else {
                Util::logError( 'Quickpick Releases json file could not be created' );

                return false;
            }
        }
        catch ( Exception $e ) {
            Util::logError( 'Error creating Quickpick JSON file: ' . $e->getMessage() );

            return false;
        }
    }

    /**
     * Checks if the specified QuickPick JSON file exists.
     *
     * This method verifies the existence of a JSON file at the given path.
     *
     * @param   string  $json  The path to the JSON file to check.
     *
     * @return bool True if the JSON file exists, false otherwise.
     */
    private function isQuickpickJsonExists($json)
    {
        return file_exists( $json );
    }

    /**
     * Combines the content from multiple URLs into a single JSON file.
     * Each URL is expected to contain lines in the format "version=url".
     * The combined data is saved to the 'quickpick-releases.json' file.
     */
    public function createQuickpickJson()
    {
        global $bearsamppCore;
        $combinedData = [];

        foreach ( $this->urls as $url ) {
            $content = @file_get_contents( $url );
            if ( $content === false ) {
                Util::logError( 'Error fetching content from URL: ' . $url );
                continue;
            }

            $lines = explode( "\n", $content );
            foreach ( $lines as $line ) {
                $line = trim( $line );
                if ( !empty( $line ) ) {
                    $parts = explode( '=', $line, 2 );
                    if ( count( $parts ) == 2 ) {
                        list( $version, $versionUrl ) = $parts;
                        $moduleName     = $this->extractModuleNameFromUrl( $url );
                        $combinedData[] = [
                            'module'  => $moduleName,
                            'version' => trim( $version ),
                            'url'     => trim( $versionUrl )
                        ];
                    }
                    else {
                        Util::logError( 'Invalid line format: ' . $line );
                    }
                }
            }
        }

        $jsonFilePath = $bearsamppCore->getResourcesPath() . '/quickpick-releases.json';
        if ( file_put_contents( $jsonFilePath, json_encode( $combinedData, JSON_PRETTY_PRINT ) ) === false ) {
            Util::logError( 'Error saving combined data to ' . $jsonFilePath );
        }
        else {
            Util::logDebug( 'Combined data saved to ' . $jsonFilePath );
        }
    }

    /**
     * Extracts the module name from a given URL.
     *
     * This method uses a regular expression to match and extract the module name
     * from the provided URL. The module name is expected to be in the format
     * 'module-{moduleName}/'. If the module name is found, it is returned with
     * the first letter capitalized. If not found, 'Unknown' is returned.
     *
     * @param   string  $url  The URL from which to extract the module name.
     *
     * @return string The extracted module name with the first letter capitalized, or 'Unknown' if not found.
     */
    private function extractModuleNameFromUrl($url)
    {
        preg_match( '/module-([a-zA-Z0-9]+)\//', $url, $matches );

        return isset( $matches[1] ) ? ucfirst( $matches[1] ) : 'Unknown';
    }

    public function getQuickpickMenu($modules, $imagesPath)
    {
        ob_start();
        // Check if the license key is valid
        if ( $this->isUsernameKeyValid() ): ?>
            <div id = 'quickPickContainer'>
                <div class = 'quickpick me-5'>
                    <select class = 'modules' id = 'modules' aria-label = 'Quick Pick Modules'>
                        <option value = '' disabled selected>Select a module</option>
                        <?php foreach ( $modules as $module ): ?>
                            <?php if ( is_string( $module ) ): ?>
                                <option value = "<?php echo htmlspecialchars( $module ); ?>" data-target = "<?php echo htmlspecialchars( $module ); ?>"
                                        id = "<?php echo htmlspecialchars( $module ); ?>">
                                    <?php echo htmlspecialchars( $module ); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php foreach ( $modules as $module ): ?>
                    <?php if ( is_string( $module ) ): ?>
                        <div id = "modules-<?php echo htmlspecialchars( $module ); ?>" class = "modules-<?php echo htmlspecialchars( $module ); ?>" style = "display: none;">
                            <select name = "modules-<?php echo htmlspecialchars( $module ); ?>" id = "modules-<?php echo htmlspecialchars( $module ); ?>"
                                    class = "<?php echo htmlspecialchars( $module ); ?>" data-module = "<?php echo htmlspecialchars( $module ); ?>">
                                <option value = '' selected>Select a version</option>
                                <?php foreach ( $this->getModuleVersions( $module ) as $version ): ?>
                                    <option value = "<?php echo htmlspecialchars( $version ); ?>"
                                            id = "version-<?php echo htmlspecialchars( $version ); ?>"><?php echo htmlspecialchars( $version ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div id = "subscribeContainer" class = "text-center mt-3 pe-3">
                <a href = "<?php echo Util::getWebsiteUrl( 'subscribe' ); ?>" class = "btn btn-dark d-inline-flex align-items-center">
                    <img src = "<?php echo $imagesPath . 'subscribe.svg'; ?>" alt = "Subscribe Icon" class = "me-2">
                    Subscribe to QuickPick now
                </a>
            </div>
        <?php endif;

        return ob_get_clean();
    }
}
