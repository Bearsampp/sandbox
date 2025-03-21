<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionStartup
 * Handles the startup process of the Bearsampp application, including initializing services,
 * cleaning temporary files, refreshing configurations, and more.
 */
class ActionStartup
{
    private $splash;
    private $restart;
    private $startTime;
    private $error;

    private $rootPath;
    private $filesToScan;

    const GAUGE_SERVICES = 5;
    const GAUGE_OTHERS = 19;

    /**
     * ActionStartup constructor.
     * Initializes the startup process, including the splash screen and various configurations.
     *
     * @param   array  $args  Command line arguments.
     */
    public function __construct($args)
    {
        global $bearsamppRoot, $bearsamppCore, $bearsamppLang, $bearsamppBins, $bearsamppWinbinder;
        $this->writeLog( 'Starting ' . APP_TITLE );

        // Init
        $this->splash    = new Splash();
        $this->restart   = false;
        $this->startTime = Util::getMicrotime();
        $this->error     = '';

        $this->rootPath    = $bearsamppRoot->getRootPath();
        $this->filesToScan = array();

        $gauge = self::GAUGE_SERVICES * count( $bearsamppBins->getServices() );
        $gauge += self::GAUGE_OTHERS + 1;

        // Start splash screen
        $this->splash->init(
            $bearsamppLang->getValue( Lang::STARTUP ),
            $gauge,
            sprintf( $bearsamppLang->getValue( Lang::STARTUP_STARTING_TEXT ), APP_TITLE . ' ' . $bearsamppCore->getAppVersion() )
        );

        $bearsamppWinbinder->setHandler( $this->splash->getWbWindow(), $this, 'processWindow', 1000 );
        $bearsamppWinbinder->mainLoop();
        $bearsamppWinbinder->reset();
    }

    /**
     * Processes the main window events during startup.
     *
     * @param   mixed  $window  The window handle.
     * @param   int    $id      The event ID.
     * @param   mixed  $ctrl    The control that triggered the event.
     * @param   mixed  $param1  Additional parameter 1.
     * @param   mixed  $param2  Additional parameter 2.
     */
    public function processWindow($window, $id, $ctrl, $param1, $param2)
    {
        global $bearsamppRoot, $bearsamppCore, $bearsamppLang, $bearsamppBins, $bearsamppTools, $bearsamppApps, $bearsamppWinbinder;

        // Rotation logs
        $this->rotationLogs();

        // Clean
        $this->cleanTmpFolders();
        $this->cleanOldBehaviors();

        // List procs
        if ( $bearsamppRoot->getProcs() !== false ) {
            $this->writeLog( 'List procs:' );
            $listProcs = array();
            foreach ( $bearsamppRoot->getProcs() as $proc ) {
                $unixExePath = Util::formatUnixPath( $proc[Win32Ps::EXECUTABLE_PATH] );
                $listProcs[] = '-> ' . basename( $unixExePath ) . ' (PID ' . $proc[Win32Ps::PROCESS_ID] . ') in ' . $unixExePath;
            }
            sort( $listProcs );
            foreach ( $listProcs as $proc ) {
                $this->writeLog( $proc );
            }
        }

        // List modules
        $this->writeLog( 'List bins modules:' );
        foreach ( $bearsamppBins->getAll() as $module ) {
            if ( !$module->isEnable() ) {
                $this->writeLog( '-> ' . $module->getName() . ': ' . $bearsamppLang->getValue( Lang::DISABLED ) );
            }
            else {
                $this->writeLog( '-> ' . $module->getName() . ': ' . $module->getVersion() . ' (' . $module->getRelease() . ')' );
            }
        }
        $this->writeLog( 'List tools modules:' );
        foreach ( $bearsamppTools->getAll() as $module ) {
            if ( !$module->isEnable() ) {
                $this->writeLog( '-> ' . $module->getName() . ': ' . $bearsamppLang->getValue( Lang::DISABLED ) );
            }
            else {
                $this->writeLog( '-> ' . $module->getName() . ': ' . $module->getVersion() . ' (' . $module->getRelease() . ')' );
            }
        }
        $this->writeLog( 'List apps modules:' );
        foreach ( $bearsamppApps->getAll() as $module ) {
            if ( !$module->isEnable() ) {
                $this->writeLog( '-> ' . $module->getName() . ': ' . $bearsamppLang->getValue( Lang::DISABLED ) );
            }
            else {
                $this->writeLog( '-> ' . $module->getName() . ': ' . $module->getVersion() . ' (' . $module->getRelease() . ')' );
            }
        }

        // Kill old instances
        $this->killOldInstances();

        // Prepare app
        $this->refreshHostname();
        $this->checkLaunchStartup();
        $this->checkBrowser();
        $this->sysInfos();
        $this->refreshAliases();
        $this->refreshVhosts();

        // Check app path
        $this->checkPath();
        $this->scanFolders();
        $this->changePath();
        $this->savePath();

        // Check BEARSAMPP_PATH, BEARSAMPP_BINS and System Path reg keys
        $this->checkPathRegKey();
        $this->checkBinsRegKey();
        $this->checkSystemPathRegKey();

        // Update config
        $this->updateConfig();

        // Create SSL certificates
        $this->createSslCrts();

        // Install
        $this->installServices();

        // Actions if everything OK
        if ( !$this->restart && empty( $this->error ) ) {
            $this->refreshGitRepos();
            $this->writeLog( 'Started in ' . round( Util::getMicrotime() - $this->startTime, 3 ) . 's' );
        }
        else {
            $this->splash->incrProgressBar( 2 );
        }

        if ($this->restart) {
            $this->writeLog(APP_TITLE . ' has to be restarted');
            $this->splash->setTextLoading(
                sprintf(
                    $bearsamppLang->getValue(Lang::STARTUP_PREPARE_RESTART_TEXT),
                    APP_TITLE . ' ' . $bearsamppCore->getAppVersion()
                )
            );

            // Set restart flag without trying to delete services
            // Services will be properly handled during the next startup
            $bearsamppCore->setExec(ActionExec::RESTART);

            // Exit the main loop to allow restart to proceed
            $bearsamppWinbinder->exitMainLoop();
        }

        if ( !empty( $this->error ) ) {
            $this->writeLog( 'Error: ' . $this->error );
            $bearsamppWinbinder->messageBoxError( $this->error, $bearsamppLang->getValue( Lang::STARTUP_ERROR_TITLE ) );
        }

        Util::startLoading();
        $bearsamppWinbinder->destroyWindow( $window );
    }

    /**
     * Rotates the logs by archiving old logs and purging old archives.
     */
    private function rotationLogs()
    {
        global $bearsamppRoot, $bearsamppCore, $bearsamppConfig, $bearsamppLang, $bearsamppBins;

        $this->splash->setTextLoading( $bearsamppLang->getValue( Lang::STARTUP_ROTATION_LOGS_TEXT ) );
        $this->splash->incrProgressBar();

        $archivesPath = $bearsamppRoot->getLogsPath() . '/archives';
        if ( !is_dir( $archivesPath ) ) {
            mkdir( $archivesPath, 0777, true );

            return;
        }

        $date               = date( 'Y-m-d-His', time() );
        $archiveLogsPath    = $archivesPath . '/' . $date;
        $archiveScriptsPath = $archiveLogsPath . '/scripts';

        // Create archive folders
        mkdir( $archiveLogsPath, 0777, true );
        mkdir( $archiveScriptsPath, 0777, true );

        // Count archives
        $archives = array();
        $handle   = @opendir( $archivesPath );
        if ( !$handle ) {
            return;
        }
        while ( false !== ($file = readdir( $handle )) ) {
            if ( $file == '.' || $file == '..' ) {
                continue;
            }
            $archives[] = $archivesPath . '/' . $file;
        }
        closedir( $handle );
        sort( $archives );

        // Remove old archives
        if ( count( $archives ) > $bearsamppConfig->getMaxLogsArchives() ) {
            $total = count( $archives ) - $bearsamppConfig->getMaxLogsArchives();
            for ( $i = 0; $i < $total; $i++ ) {
                Util::deleteFolder( $archives[$i] );
            }
        }

        // Logs
        $srcPath = $bearsamppRoot->getLogsPath();
        $handle  = @opendir( $srcPath );
        if ( !$handle ) {
            return;
        }
        while ( false !== ($file = readdir( $handle )) ) {
            if ( $file == '.' || $file == '..' || is_dir( $srcPath . '/' . $file ) ) {
                continue;
            }
            copy( $srcPath . '/' . $file, $archiveLogsPath . '/' . $file );
        }
        closedir( $handle );

        // Scripts
        $srcPath = $bearsamppCore->getTmpPath();
        $handle  = @opendir( $srcPath );
        if ( !$handle ) {
            return;
        }
        while ( false !== ($file = readdir( $handle )) ) {
            if ( $file == '.' || $file == '..' || is_dir( $srcPath . '/' . $file ) ) {
                continue;
            }
            copy( $srcPath . '/' . $file, $archiveScriptsPath . '/' . $file );
        }
        closedir( $handle );

        // Purge logs
        Util::clearFolder( $bearsamppRoot->getLogsPath(), array('archives', '.gitignore') );
    }

    /**
     * Cleans temporary folders by removing unnecessary files.
     */
    private function cleanTmpFolders()
    {
        global $bearsamppRoot, $bearsamppLang, $bearsamppCore;

        $this->splash->setTextLoading( $bearsamppLang->getValue( Lang::STARTUP_CLEAN_TMP_TEXT ) );
        $this->splash->incrProgressBar();

        $this->writeLog( 'Clear tmp folders' );
        Util::clearFolder( $bearsamppRoot->getTmpPath(), array('cachegrind', 'composer', 'openssl', 'mailpit', 'xlight', 'npm-cache', 'pip', '.gitignore') );
        Util::clearFolder( $bearsamppCore->getTmpPath(), array('.gitignore') );
    }

    /**
     * Cleans old behaviors by removing outdated registry entries.
     * This method is simplified to avoid freezing.
     */
    private function cleanOldBehaviors()
    {
        global $bearsamppLang;

        $this->writeLog('Clean old behaviors');

        $this->splash->setTextLoading($bearsamppLang->getValue(Lang::STARTUP_CLEAN_OLD_BEHAVIORS_TEXT));
        $this->splash->incrProgressBar();

        try {
            // Use direct REG command instead of VBS or PowerShell
            // Redirect errors to nul to avoid issues if the key doesn't exist
            $regCmd = 'reg delete "HKLM\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Run" /v "' . APP_TITLE . '" /f 2>nul';
            exec($regCmd);
            
            $this->writeLog('Removed startup registry entry for ' . APP_TITLE);
            
            // Skip other registry operations that might cause freezing
            $this->writeLog('Skipping other registry operations to prevent freezing');
        } catch (Exception $e) {
            $this->writeLog('Error in cleanOldBehaviors: ' . $e->getMessage());
            // Continue execution even if there's an exception
        }
    }

    /**
     * Kills old instances of Bearsampp processes.
     */
    private function killOldInstances()
    {
        global $bearsamppLang;

        $this->splash->setTextLoading( $bearsamppLang->getValue( Lang::STARTUP_KILL_OLD_PROCS_TEXT ) );
        $this->splash->incrProgressBar();

        // Stop services
        /*foreach ($bearsamppBins->getServices() as $sName => $service) {
            $serviceInfos = $service->infos();
            if ($serviceInfos === false) {
                continue;
            }
            $service->stop();
        }*/

        // Stop third party procs
        $procsKilled = Win32Ps::killBins();
        if ( !empty( $procsKilled ) ) {
            $this->writeLog( 'Procs killed:' );
            $procsKilledSort = array();
            foreach ( $procsKilled as $proc ) {
                $unixExePath       = Util::formatUnixPath( $proc[Win32Ps::EXECUTABLE_PATH] );
                $procsKilledSort[] = '-> ' . basename( $unixExePath ) . ' (PID ' . $proc[Win32Ps::PROCESS_ID] . ') in ' . $unixExePath;
            }
            sort( $procsKilledSort );
            foreach ( $procsKilledSort as $proc ) {
                $this->writeLog( $proc );
            }
        }
    }

    /**
     * Refreshes the hostname in the configuration.
     */
    private function refreshHostname()
    {
        global $bearsamppConfig, $bearsamppLang;

        $this->splash->setTextLoading( $bearsamppLang->getValue( Lang::STARTUP_REFRESH_HOSTNAME_TEXT ) );
        $this->splash->incrProgressBar();
        $this->writeLog( 'Refresh hostname' );

        $bearsamppConfig->replace( Config::CFG_HOSTNAME, gethostname() );
    }

    /**
     * Checks and sets the launch startup configuration.
     */
    private function checkLaunchStartup()
    {
        global $bearsamppConfig;

        $this->writeLog( 'Check launch startup' );

        if ( $bearsamppConfig->isLaunchStartup() ) {
            Util::enableLaunchStartup();
        }
        else {
            Util::disableLaunchStartup();
        }
    }

    /**
     * Checks and sets the default browser configuration.
     */
    private function checkBrowser()
    {
        global $bearsamppConfig, $bearsamppLang;

        $this->splash->setTextLoading( $bearsamppLang->getValue( Lang::STARTUP_CHECK_BROWSER_TEXT ) );
        $this->splash->incrProgressBar();
        $this->writeLog( 'Check browser' );

        $currentBrowser = $bearsamppConfig->getBrowser();
        if ( empty( $currentBrowser ) || !file_exists( $currentBrowser ) ) {
            $bearsamppConfig->replace( Config::CFG_BROWSER, Vbs::getDefaultBrowser() );
        }
    }

    /**
     * Logs system information.
     */
    private function sysInfos()
    {
        global $bearsamppLang;

        $this->splash->setTextLoading( $bearsamppLang->getValue( Lang::STARTUP_SYS_INFOS ) );
        $this->splash->incrProgressBar();

        $os = Batch::getOsInfo();
        $this->writeLog( sprintf( 'OS: %s', $os ) );
    }

    /**
     * Refreshes the aliases in the Apache configuration.
     */
    private function refreshAliases()
    {
        global $bearsamppConfig, $bearsamppLang, $bearsamppBins;

        $this->splash->setTextLoading( $bearsamppLang->getValue( Lang::STARTUP_REFRESH_ALIAS_TEXT ) );
        $this->splash->incrProgressBar();
        $this->writeLog( 'Refresh aliases' );

        $bearsamppBins->getApache()->refreshAlias( $bearsamppConfig->isOnline() );
    }

    /**
     * Refreshes the virtual hosts in the Apache configuration.
     */
    private function refreshVhosts()
    {
        global $bearsamppConfig, $bearsamppLang, $bearsamppBins;

        $this->splash->setTextLoading( $bearsamppLang->getValue( Lang::STARTUP_REFRESH_VHOSTS_TEXT ) );
        $this->splash->incrProgressBar();
        $this->writeLog( 'Refresh vhosts' );

        $bearsamppBins->getApache()->refreshVhosts( $bearsamppConfig->isOnline() );
    }

    /**
     * Checks the application path and logs the last path content.
     */
    private function checkPath()
    {
        global $bearsamppCore, $bearsamppLang;

        $this->splash->setTextLoading( $bearsamppLang->getValue( Lang::STARTUP_CHECK_PATH_TEXT ) );
        $this->splash->incrProgressBar();

        $this->writeLog( 'Last path: ' . $bearsamppCore->getLastPathContent() );
    }

    /**
     * Scans folders and logs the number of files to scan.
     */
    private function scanFolders()
    {
        global $bearsamppLang;

        $this->splash->setTextLoading( $bearsamppLang->getValue( Lang::STARTUP_SCAN_FOLDERS_TEXT ) );
        $this->splash->incrProgressBar();

        $this->filesToScan = Util::getFilesToScan();
        $this->writeLog( 'Files to scan: ' . count( $this->filesToScan ) );
    }

    /**
     * Changes the application path and logs the number of files and occurrences changed.
     */
    private function changePath()
    {
        global $bearsamppLang;

        $this->splash->setTextLoading( sprintf( $bearsamppLang->getValue( Lang::STARTUP_CHANGE_PATH_TEXT ), $this->rootPath ) );
        $this->splash->incrProgressBar();

        $result = Util::changePath( $this->filesToScan, $this->rootPath );
        $this->writeLog( 'Nb files changed: ' . $result['countChangedFiles'] );
        $this->writeLog( 'Nb occurences changed: ' . $result['countChangedOcc'] );
    }

    /**
     * Saves the current application path.
     */
    private function savePath()
    {
        global $bearsamppCore;

        file_put_contents( $bearsamppCore->getLastPath(), $this->rootPath );
        $this->writeLog( 'Save current path: ' . $this->rootPath );
    }

    /**
     * Checks and updates the PATH registry key.
     * This method uses direct registry access to prevent freezing.
     */
    private function checkPathRegKey()
    {
        global $bearsamppRoot, $bearsamppLang, $bearsamppRegistry;

        $this->splash->setTextLoading(sprintf($bearsamppLang->getValue(Lang::STARTUP_REGISTRY_TEXT), Registry::APP_PATH_REG_ENTRY));
        $this->splash->incrProgressBar();

        // Use direct registry access via REG command instead of VBS or PowerShell
        $regCmd = 'reg query "HKLM\\' . Registry::ENV_KEY . '" /v ' . Registry::APP_PATH_REG_ENTRY . ' 2>nul';
        $output = [];
        exec($regCmd, $output);

        $currentAppPathRegKey = '';
        foreach ($output as $line) {
            if (strpos($line, Registry::APP_PATH_REG_ENTRY) !== false) {
                $parts = preg_split('/\s+/', $line, 4);
                if (isset($parts[3])) {
                    $currentAppPathRegKey = $parts[3];
                }
            }
        }

        $genAppPathRegKey = Util::formatWindowsPath($bearsamppRoot->getRootPath());
        $this->writeLog('Current app path reg key: ' . $currentAppPathRegKey);
        $this->writeLog('Gen app path reg key: ' . $genAppPathRegKey);

        if ($currentAppPathRegKey != $genAppPathRegKey) {
            // Use direct REG command to set the registry key
            $regCmd = 'reg add "HKLM\\' . Registry::ENV_KEY . '" /v ' . Registry::APP_PATH_REG_ENTRY . ' /t REG_SZ /d "' . $genAppPathRegKey . '" /f';
            $output = [];
            exec($regCmd, $output);

            // Check if the command was successful
            $success = false;
            foreach ($output as $line) {
                if (strpos($line, 'success') !== false) {
                    $success = true;
                    break;
                }
            }

            if (!$success) {
                if (!empty($this->error)) {
                    $this->error .= PHP_EOL . PHP_EOL;
                }
                $this->error .= sprintf($bearsamppLang->getValue(Lang::STARTUP_REGISTRY_ERROR_TEXT), Registry::APP_PATH_REG_ENTRY);
                $this->error .= PHP_EOL . $bearsamppRegistry->getLatestError();
            } else {
                $this->writeLog('Need restart: checkPathRegKey');
                $this->restart = true;
            }
        }
    }

    /**
     * Checks and updates the application bins registry key.
     * If the current registry key does not match the generated key, it updates the registry key.
     * Logs the current and generated registry keys.
     * Sets an error message if the registry key update fails.
     * Sets a restart flag if the registry key is updated.
     * Includes a fallback mechanism to prevent freezing.
     */
    private function checkBinsRegKey()
    {
        global $bearsamppRoot, $bearsamppLang, $bearsamppRegistry;

        $this->splash->setTextLoading(sprintf($bearsamppLang->getValue(Lang::STARTUP_REGISTRY_TEXT), Registry::APP_BINS_REG_ENTRY));
        $this->splash->incrProgressBar();

        // Use direct registry access via REG command instead of VBS or PowerShell
        $regCmd = 'reg query "HKLM\\' . Registry::ENV_KEY . '" /v ' . Registry::APP_BINS_REG_ENTRY . ' 2>nul';
        $output = [];
        exec($regCmd, $output);

        $currentAppBinsRegKey = '';
        foreach ($output as $line) {
            if (strpos($line, Registry::APP_BINS_REG_ENTRY) !== false) {
                $parts = preg_split('/\s+/', $line, 4);
                if (isset($parts[3])) {
                    $currentAppBinsRegKey = $parts[3];
                }
            }
        }

        $genAppBinsRegKey = Util::formatWindowsPath($bearsamppRoot->getBinPath());
        $this->writeLog('Current app bins reg key: ' . $currentAppBinsRegKey);
        $this->writeLog('Gen app bins reg key: ' . $genAppBinsRegKey);

        if ($currentAppBinsRegKey != $genAppBinsRegKey) {
            // Use direct REG command to set the registry key
            $regCmd = 'reg add "HKLM\\' . Registry::ENV_KEY . '" /v ' . Registry::APP_BINS_REG_ENTRY . ' /t REG_SZ /d "' . $genAppBinsRegKey . '" /f';
            $output = [];
            exec($regCmd, $output);

            // Check if the command was successful
            $success = false;
            foreach ($output as $line) {
                if (strpos($line, 'success') !== false) {
                    $success = true;
                    break;
                }
            }

            if (!$success) {
                if (!empty($this->error)) {
                    $this->error .= PHP_EOL . PHP_EOL;
                }
                $this->error .= sprintf($bearsamppLang->getValue(Lang::STARTUP_REGISTRY_ERROR_TEXT), Registry::APP_BINS_REG_ENTRY);
            } else {
                $this->writeLog('Need restart: checkBinsRegKey');
                $this->restart = true;
            }
        }
    }

    /**
     * Checks and updates the system PATH registry key.
     * Uses direct registry access to prevent freezing during startup.
     * Ensures the application bins registry entry is at the beginning of the system PATH.
     * Properly handles portable installations by checking for both variable and actual path.
     */
    private function checkSystemPathRegKey()
    {
        global $bearsamppLang, $bearsamppRegistry, $bearsamppRoot;

        $this->splash->setTextLoading(sprintf($bearsamppLang->getValue(Lang::STARTUP_REGISTRY_TEXT), Registry::SYSPATH_REG_ENTRY));
        $this->splash->incrProgressBar();

        // Use direct registry access via REG command
        $regCmd = 'reg query "HKLM\\' . Registry::ENV_KEY . '" /v ' . Registry::SYSPATH_REG_ENTRY . ' 2>nul';
        $output = [];
        exec($regCmd, $output);
        
        $currentSysPathRegKey = '';
        foreach ($output as $line) {
            if (strpos($line, Registry::SYSPATH_REG_ENTRY) !== false) {
                $parts = preg_split('/\s+/', $line, 4);
                if (isset($parts[3])) {
                    $currentSysPathRegKey = $parts[3];
                }
            }
        }
        
        if (empty($currentSysPathRegKey)) {
            $this->writeLog('Current system PATH is empty');
            $currentSysPathRegKey = '';
        }
        
        $this->writeLog('Current system PATH: ' . $currentSysPathRegKey);

        // Get the actual bin path and the environment variable
        $binPath = Util::formatWindowsPath($bearsamppRoot->getBinPath());
        $binPathVar = '%' . Registry::APP_BINS_REG_ENTRY . '%';
        
        // Check if PATH starts with either the actual bin path or the variable
        $pathStartsWithBin = (strpos($currentSysPathRegKey, $binPath . ';') === 0);
        $pathStartsWithVar = (strpos($currentSysPathRegKey, $binPathVar . ';') === 0);
        
        // Also check if the bin path is already in the PATH (for portable installations)
        $binPathInPath = (strpos($currentSysPathRegKey, ';' . $binPath . ';') !== false) || 
                         (strpos($currentSysPathRegKey, $binPath . ';') === 0);
        $varPathInPath = (strpos($currentSysPathRegKey, ';' . $binPathVar . ';') !== false) || 
                         (strpos($currentSysPathRegKey, $binPathVar . ';') === 0);
        
        // Only modify PATH if it doesn't already contain what we need
        if (!$pathStartsWithBin && !$pathStartsWithVar && !$binPathInPath && !$varPathInPath) {
            // Create new system PATH with BEARSAMPP_BINS at the beginning
            $newSysPathRegKey = str_replace('%' . Registry::APP_BINS_REG_ENTRY . '%;', '', $currentSysPathRegKey);
            $newSysPathRegKey = str_replace('%' . Registry::APP_BINS_REG_ENTRY . '%', '', $newSysPathRegKey);
            $newSysPathRegKey = '%' . Registry::APP_BINS_REG_ENTRY . '%;' . $newSysPathRegKey;
            $this->writeLog('New system PATH: ' . $newSysPathRegKey);

            // Use direct REG command to set the registry key
            $regCmd = 'reg add "HKLM\\' . Registry::ENV_KEY . '" /v ' . Registry::SYSPATH_REG_ENTRY . ' /t REG_EXPAND_SZ /d "' . $newSysPathRegKey . '" /f';
            $output = [];
            exec($regCmd, $output);
            
            // Check if the command was successful
            $success = false;
            foreach ($output as $line) {
                if (strpos($line, 'success') !== false) {
                    $success = true;
                    break;
                }
            }
            
            if (!$success) {
                if (!empty($this->error)) {
                    $this->error .= PHP_EOL . PHP_EOL;
                }
                $this->error .= sprintf($bearsamppLang->getValue(Lang::STARTUP_REGISTRY_ERROR_TEXT), Registry::SYSPATH_REG_ENTRY);
                $this->error .= PHP_EOL . $bearsamppRegistry->getLatestError();
            } else {
                $this->writeLog('Need restart: checkSystemPathRegKey');
                $this->restart = true;
            }
        } else {
            $this->writeLog('System PATH already contains the bin path - no changes needed');
        }
    }

    /**
     * Updates the configuration for bins, tools, and apps.
     * Logs the update process.
     */
    private function updateConfig()
    {
        global $bearsamppLang, $bearsamppBins, $bearsamppTools, $bearsamppApps;

        $this->splash->setTextLoading( $bearsamppLang->getValue( Lang::STARTUP_UPDATE_CONFIG_TEXT ) );
        $this->splash->incrProgressBar();
        $this->writeLog( 'Update config' );

        $bearsamppBins->update();
        $bearsamppTools->update();
        $bearsamppApps->update();
    }

    /**
     * Creates SSL certificates if they do not already exist.
     * Logs the creation process.
     */
    private function createSslCrts()
    {
        global $bearsamppLang, $bearsamppOpenSsl;

        $this->splash->incrProgressBar();
        if ( !$bearsamppOpenSsl->existsCrt( 'localhost' ) ) {
            $this->splash->setTextLoading( sprintf( $bearsamppLang->getValue( Lang::STARTUP_GEN_SSL_CRT_TEXT ), 'localhost' ) );
            $bearsamppOpenSsl->createCrt( 'localhost' );
        }
    }

    /**
     * Installs and starts services for the application.
     * Checks if services are already installed and updates them if necessary.
     * Logs the installation process and any errors encountered.
     */
    private function installServices()
    {
        global $bearsamppLang, $bearsamppBins, $bearsamppRoot;

        Util::logTrace('STARTUP: Beginning installServices method');

        if ( !$this->restart ) {
            Util::logTrace('STARTUP: No restart flag set, proceeding with service installation checks');

            foreach ( $bearsamppBins->getServices() as $sName => $service ) {
                Util::logTrace("STARTUP: Processing service: $sName");
                
                $serviceError            = '';
                $serviceRestart          = false;
                $serviceAlreadyInstalled = false;
                $serviceToRemove         = false;
                $startServiceTime        = Util::getMicrotime();
                
                Util::logTrace("STARTUP: $sName - Initial variables set");

                $syntaxCheckCmd = null;
                $bin            = null;
                $port           = 0;
                if ( $sName == BinMailpit::SERVICE_NAME ) {
                    $bin  = $bearsamppBins->getMailpit();
                    $port = $bearsamppBins->getMailpit()->getSmtpPort();
                    Util::logTrace("STARTUP: $sName - Identified as Mailpit service, port: $port");
                }
                elseif ( $sName == BinMemcached::SERVICE_NAME ) {
                    $bin  = $bearsamppBins->getMemcached();
                    $port = $bearsamppBins->getMemcached()->getPort();
                    Util::logTrace("STARTUP: $sName - Identified as Memcached service, port: $port");
                }
                elseif ( $sName == BinApache::SERVICE_NAME ) {
                    $bin            = $bearsamppBins->getApache();
                    $port           = $bearsamppBins->getApache()->getPort();
                    $syntaxCheckCmd = BinApache::CMD_SYNTAX_CHECK;
                    Util::logTrace("STARTUP: $sName - Identified as Apache service, port: $port");
                }
                elseif ( $sName == BinMysql::SERVICE_NAME ) {
                    $bin            = $bearsamppBins->getMysql();
                    $port           = $bearsamppBins->getMysql()->getPort();
                    $syntaxCheckCmd = BinMysql::CMD_SYNTAX_CHECK;
                    Util::logTrace("STARTUP: $sName - Identified as MySQL service, port: $port");
                }
                elseif ( $sName == BinMariadb::SERVICE_NAME ) {
                    $bin            = $bearsamppBins->getMariadb();
                    $port           = $bearsamppBins->getMariadb()->getPort();
                    $syntaxCheckCmd = BinMariadb::CMD_SYNTAX_CHECK;
                    Util::logTrace("STARTUP: $sName - Identified as MariaDB service, port: $port");
                }
                elseif ( $sName == BinPostgresql::SERVICE_NAME ) {
                    $bin  = $bearsamppBins->getPostgresql();
                    $port = $bearsamppBins->getPostgresql()->getPort();
                    Util::logTrace("STARTUP: $sName - Identified as PostgreSQL service, port: $port");
                }
                elseif ( $sName == BinXlight::SERVICE_NAME ) {
                    $bin  = $bearsamppBins->getXlight();
                    $port = $bearsamppBins->getXlight()->getPort();
                    Util::logTrace("STARTUP: $sName - Identified as Xlight service, port: $port");
                }

                $name = $bin->getName() . ' ' . $bin->getVersion() . ' (' . $service->getName() . ')';
                Util::logTrace("STARTUP: $sName - Full service name: $name");

                $this->splash->incrProgressBar();
                $this->splash->setTextLoading( sprintf( $bearsamppLang->getValue( Lang::STARTUP_CHECK_SERVICE_TEXT ), $name ) );
                
                Util::logTrace("STARTUP: $sName - About to check if service is already installed");
                $serviceInfos = $service->infos();
                Util::logTrace("STARTUP: $sName - Service info check completed: " . ($serviceInfos !== false ? "Service exists" : "Service does not exist"));
                
                if ( $serviceInfos !== false ) {
                    $serviceAlreadyInstalled = true;
                    $this->writeLog( $name . ' service already installed' );
                    Util::logTrace("STARTUP: $sName - Service already installed, checking details");
                    
                    foreach ( $serviceInfos as $key => $value ) {
                        $this->writeLog( '-> ' . $key . ': ' . $value );
                    }
                    // For all services, normalize the path strings before comparison
                    $serviceGenPathName = trim(str_replace('"', '', $service->getBinPath() . ($service->getParams() ? ' ' . $service->getParams() : '')));
                    $serviceVbsPathName = trim(str_replace('"', '', $serviceInfos[Win32Service::VBS_PATH_NAME]));
                    Util::logTrace("STARTUP: $sName - Comparing service paths");
                    Util::logTrace("STARTUP: $sName - Generated path: $serviceGenPathName");
                    Util::logTrace("STARTUP: $sName - Installed path: $serviceVbsPathName");

                    // Normalize spaces (replace multiple spaces with single space)
                    $serviceGenPathName = preg_replace('/\s+/', ' ', $serviceGenPathName);
                    $serviceVbsPathName = preg_replace('/\s+/', ' ', $serviceVbsPathName);

                    // Special handling for PostgreSQL which needs to compare only the executable path
                    if ($sName == BinPostgresql::SERVICE_NAME) {
                        // Extract just the executable path from both strings
                        $serviceGenExePath = explode(' ', $serviceGenPathName)[0];
                        $serviceVbsExePath = explode(' ', $serviceVbsPathName)[0];
                        Util::logTrace("STARTUP: $sName - PostgreSQL special handling - comparing executable paths only");
                        Util::logTrace("STARTUP: $sName - Generated exe path: $serviceGenExePath");
                        Util::logTrace("STARTUP: $sName - Installed exe path: $serviceVbsExePath");

                        if ($serviceGenExePath != $serviceVbsExePath) {
                            $serviceToRemove = true;
                            $this->writeLog($name . ' service has to be removed');
                            $this->writeLog('-> serviceGenPathName: ' . $serviceGenPathName);
                            $this->writeLog('-> serviceVbsPathName: ' . $serviceVbsPathName);
                            Util::logTrace("STARTUP: $sName - Service needs to be removed due to path mismatch");
                        }
                    } else {
                        // For other services, compare the full normalized paths
                        if ($serviceGenPathName != $serviceVbsPathName) {
                            $serviceToRemove = true;
                            $this->writeLog($name . ' service has to be removed');
                            $this->writeLog('-> serviceGenPathName: ' . $serviceGenPathName);
                            $this->writeLog('-> serviceVbsPathName: ' . $serviceVbsPathName);
                            Util::logTrace("STARTUP: $sName - Service needs to be removed due to path mismatch");
                        }
                    }
                }

                $this->splash->incrProgressBar();
                if ( $serviceToRemove ) {
                    Util::logTrace("STARTUP: $sName - Attempting to delete service");
                    $deleteResult = $service->delete();
                    Util::logTrace("STARTUP: $sName - Service delete result: " . ($deleteResult ? "Success" : "Failed"));
                    
                    if (!$deleteResult) {
                        $serviceRestart = true;
                        Util::logTrace("STARTUP: $sName - Service delete failed, setting restart flag");
                    }
                }

                if ( !$serviceRestart ) {
                    // Log port check details
                    Util::logTrace("STARTUP: $sName - Checking if port $port is in use");
                    $isPortInUse = Util::isPortInUse( $port );
                    $portStatus = $isPortInUse ? 'Port in use by: ' . (is_string($isPortInUse) ? $isPortInUse : 'Unknown process') : 'Port available';
                    Util::logTrace("STARTUP: $sName - Port check result: $portStatus");
                    
                    // For Apache, also check SSL port if available
                    if ($sName == BinApache::SERVICE_NAME && method_exists($bin, 'getSslPort')) {
                        $sslPort = $bin->getSslPort();
                        Util::logTrace("STARTUP: $sName - Checking if SSL port $sslPort is in use");
                        $isSslPortInUse = Util::isPortInUse( $sslPort );
                        $sslPortStatus = $isSslPortInUse ? 'Port in use by: ' . (is_string($isSslPortInUse) ? $isSslPortInUse : 'Unknown process') : 'Port available';
                        Util::logTrace("STARTUP: $sName - SSL port check result: $sslPortStatus");
                    }
                    
                    if ( $isPortInUse === false ) {
                        $this->splash->incrProgressBar();
                        
                        // Log configuration status for Apache and database services
                        if ($sName == BinApache::SERVICE_NAME) {
                            $confStatus = $bin->checkConfFile() ? 'Valid' : 'Invalid';
                            Util::logTrace("STARTUP: $sName - Configuration status: $confStatus");
                            
                            $exePath = $bin->getExeFilePath();
                            $exeExists = file_exists($exePath) ? 'Yes' : 'No';
                            Util::logTrace("STARTUP: $sName - Executable path: $exePath (exists: $exeExists)");
                        }
                        
                        if ( !$serviceAlreadyInstalled && !$serviceToRemove ) {
                            $this->splash->setTextLoading( sprintf( $bearsamppLang->getValue( Lang::STARTUP_INSTALL_SERVICE_TEXT ), $name ) );
                            Util::logTrace("STARTUP: $sName - About to create service");
                            
                            $createResult = $service->create();
                            Util::logTrace("STARTUP: $sName - Service creation result: " . ($createResult ? "Success" : "Failed"));
                            
                            if ( !$createResult ) {
                                $errorMsg = $service->getError();
                                $serviceError .= sprintf( $bearsamppLang->getValue( Lang::STARTUP_SERVICE_CREATE_ERROR ), $errorMsg );
                                Util::logTrace("STARTUP: $sName - Service creation failed: $errorMsg");
                            } else {
                                Util::logTrace("STARTUP: $sName - Service created successfully");
                            }
                        }

                        $this->splash->incrProgressBar();
                        $this->splash->setTextLoading( sprintf( $bearsamppLang->getValue( Lang::STARTUP_START_SERVICE_TEXT ), $name ) );
                        
                        // Log service status before startup attempt
                        $beforeStatus = ($service->isInstalled() ? 'Installed' : 'Not installed') . 
                                       ', ' . ($service->isRunning() ? 'Running' : 'Not running');
                        Util::logTrace("STARTUP: $sName - Service status before startup: $beforeStatus");
                        
                        Util::logTrace("STARTUP: $sName - About to start service");
                        
                        $startResult = $service->start();
                        Util::logTrace("STARTUP: $sName - Service start result: " . ($startResult ? "Success" : "Failed"));
                        
                        if ( !$startResult ) {
                            if ( !empty( $serviceError ) ) {
                                $serviceError .= PHP_EOL;
                            }
                            $errorMsg = $service->getError();
                            $serviceError .= sprintf( $bearsamppLang->getValue( Lang::STARTUP_SERVICE_START_ERROR ), $errorMsg );
                            Util::logTrace("STARTUP: $sName - Service start failed: $errorMsg");
                            
                            if ( !empty( $syntaxCheckCmd ) ) {
                                Util::logTrace("STARTUP: $sName - Running syntax check with command: $syntaxCheckCmd");
                                
                                $cmdSyntaxCheck = $bin->getCmdLineOutput( $syntaxCheckCmd );
                                $syntaxResult = $cmdSyntaxCheck['syntaxOk'] ? 'OK' : 'Failed';
                                Util::logTrace("STARTUP: $sName - Syntax check result: $syntaxResult");
                                
                                if ( !$cmdSyntaxCheck['syntaxOk'] ) {
                                    $syntaxContent = $cmdSyntaxCheck['content'];
                                    $serviceError .= PHP_EOL . sprintf( $bearsamppLang->getValue( Lang::STARTUP_SERVICE_SYNTAX_ERROR ), $syntaxContent );
                                    Util::logTrace("STARTUP: $sName - Syntax check output: $syntaxContent");
                                }
                            }
                        } else {
                            Util::logTrace("STARTUP: $sName - Service started successfully");
                            
                            // Log service status after startup attempt
                            $afterStatus = ($service->isInstalled() ? 'Installed' : 'Not installed') . 
                                         ', ' . ($service->isRunning() ? 'Running' : 'Not running');
                            Util::logTrace("STARTUP: $sName - Service status after startup: $afterStatus");
                        }
                        $this->splash->incrProgressBar();
                    }
                    else {
                        if ( !empty( $serviceError ) ) {
                            $serviceError .= PHP_EOL;
                        }
                        $serviceError .= sprintf( $bearsamppLang->getValue( Lang::STARTUP_SERVICE_PORT_ERROR ), $port, $isPortInUse );
                        Util::logTrace("STARTUP: $sName - Service cannot start: port $port is already in use by $isPortInUse");
                        $this->splash->incrProgressBar( 3 );
                    }
                }
                else {
                    Util::logTrace("STARTUP: $sName - Need restart for service installation");
                    $this->restart = true;
                    $this->splash->incrProgressBar( 3 );
                }

                if ( !empty( $serviceError ) ) {
                    if ( !empty( $this->error ) ) {
                        $this->error .= PHP_EOL . PHP_EOL;
                    }
                    $this->error .= sprintf( $bearsamppLang->getValue( Lang::STARTUP_SERVICE_ERROR ), $name ) . PHP_EOL . $serviceError;
                    Util::logTrace("STARTUP: $sName - Service error added to global error: $serviceError");
                }
                else {
                    $elapsedTime = round( Util::getMicrotime() - $startServiceTime, 3 );
                    Util::logTrace("STARTUP: $sName - Service processing completed in $elapsedTime seconds");
                }
            }
            
            Util::logTrace("STARTUP: All services processed. Restart flag: " . ($this->restart ? "Yes" : "No"));
        }
        else {
            Util::logTrace("STARTUP: Restart flag was set, skipping service installation");
            $this->splash->incrProgressBar( self::GAUGE_SERVICES * count( $bearsamppBins->getServices() ) );
        }
        
        Util::logTrace("STARTUP: installServices method completed");
    }

    /**
     * Refreshes Git repositories if the scan on startup is enabled.
     * Logs the number of repositories found.
     */
    private function refreshGitRepos()
    {
        global $bearsamppLang, $bearsamppTools;

        $this->splash->incrProgressBar();
        if ( $bearsamppTools->getGit()->isScanStartup() ) {
            $this->splash->setTextLoading( $bearsamppLang->getValue( Lang::STARTUP_REFRESH_GIT_REPOS_TEXT ) );

            $repos = $bearsamppTools->getGit()->findRepos( false );
            $this->writeLog( 'Update GIT repos: ' . count( $repos ) . ' found' );
        }
    }

    /**
     * Writes a log message to the startup log file.
     *
     * @param   string  $log  The log message to write.
     */
    private function writeLog($log)
    {
        global $bearsamppRoot;
        Util::logDebug( $log, $bearsamppRoot->getStartupLogFilePath() );
    }
}
