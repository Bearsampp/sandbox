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
    // Membership Pro api key & url
    const API_KEY = '4abe15e5-95f2-4663-ad12-eadb245b28b4';
    const API_URL = 'https://bearsampp.com/index.php?option=com_osmembership&task=api.get_active_plan_ids&api_key=';

    // URL where quickpick-releases.json lives.
    const JSON_URL = 'https://raw.githubusercontent.com/Bearsampp/Bearsampp/main/core/resources/quickpick-releases.json';

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
     * @var string $jsonFilePath
     *
     * The file path to the local quickpick-releases.json file.
     */
    private $jsonFilePath;

    /**
     * Constructor to initialize the jsonFilePath.
     */
    public function __construct()
    {
        global $bearsamppCore;
        $this->jsonFilePath = $bearsamppCore->getResourcesPath() . '/quickpick-releases.json';
    }

    /**
     * Retrieves the list of available modules.
     *
     * @return array An array of module names.
     */
    public function getModules(): array
    {
        return array_keys( $this->modules );
    }

    /**
     * Loads the QuickPick interface with the available modules and their versions.
     *
     * @param   string  $imagesPath  The path to the images directory.
     *
     * @return string The HTML content of the QuickPick interface.
     * @throws Exception
     */
    public function loadQuickpick(string $imagesPath): string
    {
        $this->checkQuickpickJson();

        $modules = $this->getModules();

        return $this->getQuickpickMenu( $modules, $imagesPath );
    }

    /**
     * Checks if the local `quickpick-releases.json` file is up-to-date with the remote version.
     *
     * This method compares the creation time of the local JSON file with the remote file's last modified time.
     * If the remote file is newer or the local file does not exist, it fetches the latest JSON data by calling
     * the `rebuildQuickpickJson` method.
     *
     * @return array|false Returns the JSON data if the remote file is newer or the local file does not exist,
     *                     otherwise returns false.
     * @throws Exception
     */
    public function checkQuickpickJson()
    {
        // Initialize variables
        $localFileCreationTime = 0;

        // Get the creation time of the local file if it exists
        if ( file_exists( $this->jsonFilePath ) ) {
            $localFileCreationTime = filectime( $this->jsonFilePath );
        }

        // Get the creation time of the remote file
        $headers = get_headers( self::JSON_URL, 1 );
        if ( $headers === false || !isset( $headers['Last-Modified'] ) ) {
            // If we cannot get the headers or Last-Modified is not set, assume no update is needed
            return false;
        }
        $remoteFileCreationTime = strtotime( $headers['Last-Modified'] );

        // Compare the creation times
        if ( $remoteFileCreationTime > $localFileCreationTime || $localFileCreationTime === 0 ) {
            return $this->rebuildQuickpickJson();
        }

        // Return false if the local file is up-to-date
        return false;
    }

    /**
     * Retrieves the QuickPick JSON data from the local file.
     *
     * @return array The decoded JSON data, or an error message if the file cannot be fetched or decoded.
     */
    public function getQuickpickJson(): array
    {
        $content = @file_get_contents( $this->jsonFilePath );
        if ( $content === false ) {
            Util::logError( 'Error fetching content from JSON file: ' . $this->jsonFilePath );

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
     * Rebuilds the local quickpick-releases.json file by fetching the latest data from the remote URL.
     *
     * @return array An array containing the status and message of the rebuild process.
     * @throws Exception If the JSON content cannot be fetched or saved.
     */
    public function rebuildQuickpickJson(): array
    {
        Util::logDebug( 'Fetching JSON file: ' . $this->jsonFilePath );

        // Define the URL of the remote JSON file
        $url = self::JSON_URL;

        // Fetch the JSON content from the URL
        $jsonContent = file_get_contents( $url );

        if ( $jsonContent === false ) {
            // Handle error if the file could not be fetched
            throw new Exception( 'Failed to fetch JSON content from the URL.' );
        }

        // Save the JSON content to the specified path
        $result = file_put_contents( $this->jsonFilePath, $jsonContent );

        if ( $result === false ) {
            // Handle error if the file could not be saved
            throw new Exception( 'Failed to save JSON content to the specified path.' );
        }

        // Return success message
        return ['success' => 'JSON content fetched and saved successfully'];
    }

    /**
     * Retrieves the list of available versions for a specified module.
     *
     * This method fetches the QuickPick JSON data and iterates through the entries to find
     * all versions associated with the specified module. If no versions are found, an error
     * message is logged and returned.
     *
     * @param   string  $module  The name of the module for which to retrieve versions.
     *
     * @return array An array of version strings for the specified module, or an error message if no versions are found.
     */
    public function getModuleVersions(string $module): array
    {
        global $bearsamppCore;
        Util::logDebug( 'getModuleVersions called for module: ' . (is_string( $module ) ? $module : 'Invalid module type') );

        // Check if $module is a string
        if ( !is_string( $module ) ) {
            Util::logError( 'Invalid module type: ' . gettype( $module ) );

            return ['error' => 'Invalid module type'];
        }

        $versions = [];

        // convert $module to lowercase
        $module   = 'module-' . strtolower( $module );
        $jsonData = $this->getQuickpickJson();

        foreach ( $jsonData as $entry ) {
            if ( isset( $entry['module'] ) && is_string( $entry['module'] ) && strtolower( $entry['module'] ) === $module ) {
                if ( isset( $entry['versions'] ) && is_array( $entry['versions'] ) ) {
                    foreach ( $entry['versions'] as $versionEntry ) {
                        if ( isset( $versionEntry['version'] ) && is_string( $versionEntry['version'] ) ) {
                            $versions[] = $versionEntry['version'];
                        }
                    }
                }
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
     * @param   string  $module   The name of the module.
     * @param   string  $version  The version of the module.
     *
     * @return string|array The URL of the specified module version or an error message if the version is not found.
     */
    public function getModuleUrl(string $module, string $version)
    {
        Util::logDebug( 'getModuleUrl called for module: ' . $module . ' version: ' . $version );

        $data = $this->getQuickpickJson();

        foreach ( $data as $entry ) {
            if ( isset( $entry['module'] ) && strtolower( $entry['module'] ) === strtolower( $module ) &&
                isset( $entry['versions'] ) && is_array( $entry['versions'] ) ) {
                foreach ( $entry['versions'] as $versionEntry ) {
                    if ( isset( $versionEntry['version'] ) && $versionEntry['version'] === $version ) {
                        Util::logDebug( 'Found URL for version: ' . $version . ' URL: ' . $versionEntry['url'] );

                        return (string) trim( $versionEntry['url'] );
                    }
                }
            }
        }

        Util::logError( 'Version not found: ' . $version );

        return ['error' => 'Version not found'];
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
     */
    public function checkDownloadId(): bool
    {
        global $bearsamppConfig;

        Util::logDebug( 'checkDownloadId method called.' );

        // Ensure the global config is available
        if ( !isset( $bearsamppConfig ) ) {
            Util::logError( 'Global configuration is not set.' );

            return false;
        }

        $DownloadId = $bearsamppConfig->getDownloadId();
        Util::logDebug( 'DownloadId is: ' . $DownloadId );

        // Ensure the license key is not empty
        if ( empty( $DownloadId ) ) {
            Util::logError( 'License key is empty.' );

            return false;
        }

        $url = self::API_URL . self::API_KEY . '&download_id=' . $DownloadId;
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
            Util::logDebug( 'License key valid: ' . $DownloadId );

            return true;
        }

        Util::logError( 'Invalid license key: ' . $DownloadId );

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
     */
    public function installModule(string $module, string $version): array
    {
        $data = $this->getQuickpickJson();

        // Find the module URL and module name from the data
        $moduleUrl = '';
        $moduleKey = 'module-' . strtolower( $module );
        $moduleUrl = $this->getModuleUrl( $moduleKey, $version );

        if ( is_array( $moduleUrl ) && isset( $moduleUrl['error'] ) ) {
            Util::logError( 'Module URL not found for module: ' . $moduleKey . ' version: ' . $version );

            return ['error' => 'Module URL not found'];
        }

        if ( empty( $moduleUrl ) ) {
            Util::logError( 'Module URL not found for module: ' . $moduleKey . ' version: ' . $version );

            return ['error' => 'Module URL not found'];
        }

        $state = Util::checkInternetState();
        if ( $state ) {
            $response = $this->fetchAndUnzipModule( $moduleUrl, $module );
            Util::logDebug( 'Response is: ' . print_r( $response, true ) );

            return $response;
        }
        else {
            Util::logError( 'No internet connection available.' );

            return ['error' => 'No internet connection'];
        }
    }
/**
     * Fetches the module URL and stores it in /tmp, then unzips the file based on its extension.
     *
     * @param   string  $moduleUrl  The URL of the module to fetch.
     * @param   string  $module     The name of the module.
     *
     * @return array An array containing the status and message.
     */
    public function fetchAndUnzipModule($moduleUrl, $module): array
    {
        Util::logDebug( "$module is: " . $module );

        global $bearsamppRoot, $bearsamppCore;
        $tmpDir = $bearsamppRoot->getTmpPath();
        Util::logDebug( 'Temporary Directory: ' . $tmpDir );

        $fileName = basename( $moduleUrl );
        Util::logDebug( 'File Name: ' . $fileName );

        $filePath = $tmpDir . '/' . $fileName;
        Util::logDebug( 'File Path: ' . $filePath );

        $moduleName = str_replace( 'module-', '', $module );
        Util::logDebug( 'Module Name: ' . $moduleName );

        $moduleType = $this->modules[$module]['type'];
        Util::logDebug( 'Module Type: ' . $moduleType );

        // Get path to write module to.
        if ( $moduleType === 'application' ) {
            $destination = $bearsamppRoot->getAppsPath() . '/' . $moduleName . '/';
        }
        elseif ( $moduleType === 'binary' ) {
            $destination = $bearsamppRoot->getBinPath() . '/' . $moduleName . '/';
        }
        elseif ( $moduleType === 'tools' ) {
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
                return ['error' => 'Failed to unzip .7z file.  File: ' . $filePath . ' could not be unzipped', 'Destination: ' . $destination];
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
     * Generates the HTML content for the QuickPick menu.
     *
     * This method creates the HTML structure for the QuickPick interface, including a dropdown
     * for selecting modules and their respective versions. It checks if the license key is valid
     * before displaying the modules. If the license key is invalid, it displays a subscription prompt.
     * If there is no internet connection, it displays a message indicating the lack of internet.
     *
     * @param   array   $modules     An array of available modules.
     * @param   string  $imagesPath  The path to the images directory.
     *
     * @return string The HTML content of the QuickPick menu.
     */
    public function getQuickpickMenu($modules, $imagesPath): string
    {
        if ( Util::checkInternetState() ) {

            ob_start();
            // Check if the license key is valid
            if ( $this->checkDownloadId() ):
                //  if (1 == 1):
                ?>
                <style>

                    .custom-select {
                        position: relative;
                        width: 100%;
                        max-width: 100%;
                        font-size: 1.15rem;
                        color: #000;
                        }

                    .custom-select .select-button {
                        width: 100%;
                        font-size: 16px;
                        background-color: #fff;
                        padding: 0.675em 1em;
                        border: 1px solid #caced1;
                        border-radius: 0.25rem;
                        cursor: pointer;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        white-space: nowrap;
                        font-weight: normal !important;
                        }

                    .custom-select .selected-value {
                        text-align: left;
                        padding-right: 5px;
                        font-weight: bold
                        }

                    .custom-select .arrow {
                        border-left: 5px solid transparent;
                        border-right: 5px solid transparent;
                        border-top: 6px solid #000;
                        transition: transform ease-in-out 0.3s;

                        }

                    .custom-select.active .select-dropdown {
                        opacity: 1;
                        visibility: visible;
                        transform: scaleY(1);
                        }

                    .select-dropdown {
                        position: absolute;
                        list-style: none;
                        width: 100%;
                        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                        box-sizing: border-box;
                        background-color: #fff;
                        border: 1px solid #caced1;
                        border-radius: 4px;
                        padding: 10px;
                        margin-top: 10px;
                        max-height: 200px;
                        overflow-y: auto;
                        transition: 0.5s ease;
                        transform: scaleY(0);
                        opacity: 0;
                        visibility: hidden;
                        }

                    .select-dropdown:focus-within {
                        box-shadow: 0 10px 25px rgba(94, 108, 233, 0.6);
                        }

                    .select-dropdown li {
                        position: relative;
                        cursor: pointer;
                        display: flex;
                        gap: 1rem;
                        align-items: center;
                        }

                    .select-dropdown li label {
                        width: 100%;
                        padding: 8px 10px;
                        cursor: pointer;
                        }

                    .select-dropdown::-webkit-scrollbar {
                        width: 7px;
                        }

                    .select-dropdown::-webkit-scrollbar-track {
                        background: #f1f1f1;
                        border-radius: 25px;
                        }

                    .select-dropdown::-webkit-scrollbar-thumb {
                        background: #ccc;
                        border-radius: 25px;
                        }

                    .select-dropdown li:hover,
                    .select-dropdown input:checked ~ label {
                        background-color: #f2f2f2;
                        }

                    .select-dropdown input:focus ~ label {
                        background-color: #dfdfdf;
                        }

                    .select-dropdown input[type="radio"] {
                        position: absolute;
                        left: 0;
                        opacity: 0;
                        }

                    .moduleheader {
                        font-weight: bold
                        }

                    ;
                </style>
                <div id = 'quickPickContainer'>
                    <div class = 'quickpick me-5'>

                        <div class = "custom-select">
                            <button class = "select-button" role = "combobox"
                                    aria-label = "select button"
                                    aria-haspopup = "listbox"
                                    aria-expanded = "false"
                                    aria-controls = "select-dropdown">
                                <span class = "selected-value">Select a module and version</span>
                                <span class = "arrow"></span>
                            </button>
                            <ul class = "select-dropdown" role = "listbox" id = "select-dropdown">

                                <?php
                                foreach ( $modules as $module ): ?>
                                    <?php if ( is_string( $module ) ): ?>
                                        <li role = "option" class = "moduleheader">
                                            <!-- <input type="radio" id="<?php echo htmlspecialchars( $module ); ?>" name="module"/>
                                <label for="<?php echo htmlspecialchars( $module ); ?>"><?php echo htmlspecialchars( $module ); ?></label> -->
                                            <?php echo htmlspecialchars( $module ); ?>
                                        </li>

                                        <?php
                                        foreach ( $this->getModuleVersions( $module ) as $version ): ?>
                                            <li role = "option" class = "moduleoption"
                                                id = "<?php echo htmlspecialchars( $module ); ?>-version-<?php echo htmlspecialchars( $version ); ?>-li">
                                                <input type = "radio" id = "<?php echo htmlspecialchars( $module ); ?>-version-<?php echo htmlspecialchars( $version ); ?>"
                                                       name = "module" data-module = "<?php echo htmlspecialchars( $module ); ?>"
                                                       data-value = "<?php echo htmlspecialchars( $version ); ?>">
                                                <label
                                                    for = "<?php echo htmlspecialchars( $module ); ?>-version-<?php echo htmlspecialchars( $version ); ?>"><?php echo htmlspecialchars( $version ); ?></label>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
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
        else {
            ob_start();
            ?>
            <div id = "InternetState" class = "text-center mt-3 pe-3">
                <img src = "<?php echo $imagesPath . 'no-wifi-icon.svg'; ?>" alt = "No Wifi Icon" class = "me-2">
                <span>No internet present</span>
            </div>
            <?php
            return ob_get_clean();
        }
    }
}
