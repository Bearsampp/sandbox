<?php
/*
 *
 *  * Copyright (c) 2022-2025 Bearsampp
 *  * License: GNU General Public License version 3 or later; see LICENSE.txt
 *  * Website: https://bearsampp.com
 *  * Github: https://github.com/Bearsampp
 *
 */

/**
 * Class ActionQuit
 * Handles the quitting process of the Bearsampp application.
 * Displays a splash screen and stops all services and processes.
 */
class ActionQuit
{
    /**
     * @var Splash The splash screen instance.
     */
    private $splash;

    /**
     * Gauge values for progress bar increments.
     */
    const GAUGE_PROCESSES = 1;
    const GAUGE_OTHERS = 1;

    /**
     * ActionQuit constructor.
     * Initializes the quitting process, displays the splash screen, and sets up the main loop.
     *
     * @param   array  $args  Command line arguments.
     */
    public function __construct($args)
    {
        global $bearsamppCore, $bearsamppLang, $bearsamppBins, $bearsamppWinbinder, $arrayOfCurrents;

        Util::logInfo('ActionQuit constructor called - starting exit process');
        Util::logDebug('Number of services to stop: ' . count($bearsamppBins->getServices()));

        // Start splash screen
        $this->splash = new Splash();
        $this->splash->init(
            $bearsamppLang->getValue( Lang::QUIT ),
            self::GAUGE_PROCESSES * count( $bearsamppBins->getServices() ) + self::GAUGE_OTHERS,
            sprintf( $bearsamppLang->getValue( Lang::EXIT_LEAVING_TEXT ), APP_TITLE . ' ' . $bearsamppCore->getAppVersion() )
        );

        Util::logDebug('Splash screen initialized');

        // Set handler for the splash screen window
        $bearsamppWinbinder->setHandler( $this->splash->getWbWindow(), $this, 'processWindow', 2000 );
        Util::logDebug('Window handler set, starting main loop');

        $bearsamppWinbinder->mainLoop();
        Util::logDebug('Main loop exited');

        $bearsamppWinbinder->reset();
        Util::logInfo('ActionQuit constructor completed');
    }


    /**
     * Get the optimal service shutdown order based on dependencies.
     * Services are ordered to stop dependent services first, then core services.
     *
     * @return array Array of service names in shutdown order
     */
    private function getServiceShutdownOrder()
    {
        // Define shutdown order: dependent services first, then core services
        // This prevents connection errors and ensures clean shutdown
        return [
            // Tier 1: Application services (no dependencies on other services)
            BinMailpit::SERVICE_NAME,      // Mail testing tool
            BinMemcached::SERVICE_NAME,    // Caching service
            BinXlight::SERVICE_NAME,       // FTP server

            // Tier 2: Database services (web server depends on these)
            BinPostgresql::SERVICE_NAME,   // PostgreSQL database
            BinMariadb::SERVICE_NAME,      // MariaDB database
            BinMysql::SERVICE_NAME,        // MySQL database

            // Tier 3: Web server (depends on databases and other services)
            BinApache::SERVICE_NAME,       // Apache web server (stopped last)
        ];
    }

    /**
     * Get the display name for a service.
     *
     * @param   string  $sName    The service name constant
     * @param   object  $service  The service object
     * @return  string  The formatted display name
     */
    private function getServiceDisplayName($sName, $service)
    {
        global $bearsamppBins;

        $name = '';

        if ($sName == BinApache::SERVICE_NAME) {
            $name = $bearsamppBins->getApache()->getName() . ' ' . $bearsamppBins->getApache()->getVersion();
        }
        elseif ($sName == BinMysql::SERVICE_NAME) {
            $name = $bearsamppBins->getMysql()->getName() . ' ' . $bearsamppBins->getMysql()->getVersion();
        }
        elseif ($sName == BinMailpit::SERVICE_NAME) {
            $name = $bearsamppBins->getMailpit()->getName() . ' ' . $bearsamppBins->getMailpit()->getVersion();
        }
        elseif ($sName == BinMariadb::SERVICE_NAME) {
            $name = $bearsamppBins->getMariadb()->getName() . ' ' . $bearsamppBins->getMariadb()->getVersion();
        }
        elseif ($sName == BinPostgresql::SERVICE_NAME) {
            $name = $bearsamppBins->getPostgresql()->getName() . ' ' . $bearsamppBins->getPostgresql()->getVersion();
        }
        elseif ($sName == BinMemcached::SERVICE_NAME) {
            $name = $bearsamppBins->getMemcached()->getName() . ' ' . $bearsamppBins->getMemcached()->getVersion();
        }
        elseif ($sName == BinXlight::SERVICE_NAME) {
            $name = $bearsamppBins->getXlight()->getName() . ' ' . $bearsamppBins->getXlight()->getVersion();
        }

        $name .= ' (' . $service->getName() . ')';
        return $name;
    }

    /**
     * Processes the splash screen window events.
     * Stops all services in optimal order, deletes symlinks, and kills remaining processes.
     *
     * @param   resource  $window  The window resource.
     * @param   int       $id      The event ID.
     * @param   int       $ctrl    The control ID.
     * @param   mixed     $param1  Additional parameter 1.
     * @param   mixed     $param2  Additional parameter 2.
     */
    public function processWindow($window, $id, $ctrl, $param1, $param2)
    {
        global $bearsamppBins, $bearsamppLang, $bearsamppWinbinder;

        Util::logInfo('Starting graceful shutdown process with optimized service order');

        // Get all available services
        $allServices = $bearsamppBins->getServices();

        // Get optimal shutdown order
        $shutdownOrder = $this->getServiceShutdownOrder();

        Util::logDebug('Service shutdown order: ' . implode(' -> ', $shutdownOrder));

        // Stop services in optimal order
        foreach ($shutdownOrder as $sName) {
            // Check if this service exists and is installed
            if (!isset($allServices[$sName])) {
                Util::logDebug('Service not found in available services: ' . $sName);
                continue;
            }

            $service = $allServices[$sName];
            $displayName = $this->getServiceDisplayName($sName, $service);

            Util::logInfo('Stopping service: ' . $displayName);

            $this->splash->incrProgressBar();
            $this->splash->setTextLoading(sprintf($bearsamppLang->getValue(Lang::EXIT_REMOVE_SERVICE_TEXT), $displayName));

            // Delete (stop and remove) the service
            $result = $service->delete();

            if ($result) {
                Util::logInfo('Successfully stopped and removed service: ' . $displayName);
            } else {
                Util::logWarning('Failed to stop/remove service: ' . $displayName . ' (may not be installed)');
            }
        }

        // Handle any services not in the shutdown order (for extensibility)
        foreach ($allServices as $sName => $service) {
            if (!in_array($sName, $shutdownOrder)) {
                $displayName = $this->getServiceDisplayName($sName, $service);
                Util::logWarning('Stopping unlisted service: ' . $displayName);

                $this->splash->incrProgressBar();
                $this->splash->setTextLoading(sprintf($bearsamppLang->getValue(Lang::EXIT_REMOVE_SERVICE_TEXT), $displayName));
                $service->delete();
            }
        }

        Util::logInfo('All services stopped successfully');

        // Purge "current" symlinks
        Symlinks::deleteCurrentSymlinks();

        // Stop other processes
        $this->splash->incrProgressBar();
        $this->splash->setTextLoading( $bearsamppLang->getValue( Lang::EXIT_STOP_OTHER_PROCESS_TEXT ) );
        Win32Ps::killBins( true );

        // Terminate any remaining processes
        // Final termination sequence
        $this->splash->setTextLoading('Completing shutdown...');
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $currentPid = Win32Ps::getCurrentPid();

            // Terminate PHP processes with a timeout of 15 seconds
            self::terminatePhpProcesses($currentPid, $window, $this->splash, 15);

            // Force exit if still running
            exit(0);
        }

        // Non-Windows fallback
        $bearsamppWinbinder->destroyWindow($window);
        exit(0);
    }

    /**
     * Terminates PHP processes with timeout handling.
     *
     * @param   int     $excludePid  Process ID to exclude
     * @param   mixed   $window      Window handle or null
     * @param   mixed   $splash      Splash screen or null
     * @param   int     $timeout     Maximum time to wait for termination (seconds)
     * @return  void
     */
    public static function terminatePhpProcesses($excludePid, $window = null, $splash = null, $timeout = 10)
    {
        global $bearsamppWinbinder, $bearsamppCore;

        $currentPid = Win32Ps::getCurrentPid();
        $startTime = microtime(true);

        Util::logTrace('Starting PHP process termination (excluding PID: ' . $excludePid . ')');

        // Get list of loading PIDs to exclude from termination
        $loadingPids = array();
        if (file_exists($bearsamppCore->getLoadingPid())) {
            $pids = file($bearsamppCore->getLoadingPid());
            foreach ($pids as $pid) {
                $loadingPids[] = intval(trim($pid));
            }
            Util::logTrace('Loading PIDs to preserve: ' . implode(', ', $loadingPids));
        }

        $targets = ['php-win.exe', 'php.exe'];
        foreach (Win32Ps::getListProcs() as $proc) {
            // Check if we've exceeded our timeout
            if (microtime(true) - $startTime > $timeout) {
                Util::logTrace('Process termination timeout exceeded, continuing with remaining operations');
                break;
            }

            $exe = strtolower(basename($proc[Win32Ps::EXECUTABLE_PATH]));
            $pid = $proc[Win32Ps::PROCESS_ID];

            // Skip if this is the excluded PID or a loading window PID
            if (in_array($exe, $targets) && $pid != $excludePid && !in_array($pid, $loadingPids)) {
                Util::logTrace('Terminating PHP process: ' . $pid);
                Win32Ps::kill($pid);
                usleep(100000); // 100ms delay between terminations
            } elseif (in_array($pid, $loadingPids)) {
                Util::logTrace('Preserving loading window process: ' . $pid);
            }
        }

        // Initiate self-termination with timeout
        if ($splash !== null) {
            $splash->setTextLoading('Final cleanup...');
        }

        try {
            Util::logTrace('Initiating self-termination for PID: ' . $currentPid);
            // Add a timeout wrapper around the killProc call
            $killSuccess = Vbs::killProc($currentPid);
            if (!$killSuccess) {
                Util::logTrace('Self-termination via Vbs::killProc failed, using alternative method');
            }
        } catch (\Exception $e) {
            Util::logTrace('Exception during self-termination: ' . $e->getMessage());
        }

        // Destroy window after process termination
        // Fix for PHP 8.2: Check if window is not null before destroying
        if ($window && $bearsamppWinbinder) {
            try {
                Util::logTrace('Destroying window');
                $bearsamppWinbinder->destroyWindow($window);
            } catch (\Exception $e) {
                Util::logTrace('Exception during window destruction: ' . $e->getMessage());
            }
        }

        // Force exit if still running after timeout
        if (microtime(true) - $startTime > $timeout * 1.5) {
            Util::logTrace('Forcing exit due to timeout');
            exit(0);
        }
    }
}
