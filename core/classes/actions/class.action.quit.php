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

        // Start splash screen
        $this->splash = new Splash();
        $this->splash->init(
            $bearsamppLang->getValue( Lang::QUIT ),
            self::GAUGE_PROCESSES * count( $bearsamppBins->getServices() ) + self::GAUGE_OTHERS,
            sprintf( $bearsamppLang->getValue( Lang::EXIT_LEAVING_TEXT ), APP_TITLE . ' ' . $bearsamppCore->getAppVersion() )
        );

        // Set handler for the splash screen window
        $bearsamppWinbinder->setHandler( $this->splash->getWbWindow(), $this, 'processWindow', 2000 );
        $bearsamppWinbinder->mainLoop();
        $bearsamppWinbinder->reset();
    }


    /**
     * Processes the splash screen window events.
     * Stops all services, deletes symlinks, and kills remaining processes.
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

        // Stop all services
        foreach ( $bearsamppBins->getServices() as $sName => $service ) {
            $name = $bearsamppBins->getApache()->getName() . ' ' . $bearsamppBins->getApache()->getVersion();
            if ( $sName == BinMysql::SERVICE_NAME ) {
                $name = $bearsamppBins->getMysql()->getName() . ' ' . $bearsamppBins->getMysql()->getVersion();
            }
            elseif ( $sName == BinMailpit::SERVICE_NAME ) {
                $name = $bearsamppBins->getMailpit()->getName() . ' ' . $bearsamppBins->getMailpit()->getVersion();
            }
            elseif ( $sName == BinMariadb::SERVICE_NAME ) {
                $name = $bearsamppBins->getMariadb()->getName() . ' ' . $bearsamppBins->getMariadb()->getVersion();
            }
            elseif ( $sName == BinPostgresql::SERVICE_NAME ) {
                $name = $bearsamppBins->getPostgresql()->getName() . ' ' . $bearsamppBins->getPostgresql()->getVersion();
            }
            elseif ( $sName == BinMemcached::SERVICE_NAME ) {
                $name = $bearsamppBins->getMemcached()->getName() . ' ' . $bearsamppBins->getMemcached()->getVersion();
            }
            elseif ($sName == BinXlight::SERVICE_NAME) {
                $name = $bearsamppBins->getXlight()->getName() . ' ' . $bearsamppBins->getXlight()->getVersion();
            }
            $name .= ' (' . $service->getName() . ')';

            $this->splash->incrProgressBar();
            $this->splash->setTextLoading( sprintf( $bearsamppLang->getValue( Lang::EXIT_REMOVE_SERVICE_TEXT ), $name ) );
            $service->delete();
        }

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

            // 1. Terminate PHP processes first
            self::terminatePhpProcesses($currentPid, $window, $this->splash);

            // 4. Force exit if still running
            exit(0);
        }

        // Non-Windows fallback
        $bearsamppWinbinder->destroyWindow($window);
        exit(0);
    }

    /**
     * Terminates PHP processes except for the one with the given PID.
     *
     * @param   int     $excludePid  Process ID to exclude from termination
     * @param   mixed   $window      Window handle to destroy
     * @param   mixed   $splash      Splash screen instance
     * @return  bool    Success status
     */
    public static function terminatePhpProcesses($excludePid, $window = null, $splash = null)
    {
        global $bearsamppWinbinder;

        Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Starting with excludePid: ' . $excludePid . ' - ' . microtime(true));

        $currentPid = Win32Ps::getCurrentPid();
        Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Current PID: ' . $currentPid . ' - ' . microtime(true));

        // 1. First terminate other PHP processes
        $targets = ['php-win.exe', 'php.exe'];
        Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Targeting executables: ' . implode(', ', $targets) . ' - ' . microtime(true));
        
        $processes = Win32Ps::getListProcs();
        Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Found ' . count($processes) . ' total processes - ' . microtime(true));
        
        $terminatedCount = 0;
        $phpProcsCount = 0;
        $result = false;
        
        foreach ($processes as $proc) {
            $exe = strtolower(basename($proc[Win32Ps::EXECUTABLE_PATH]));
            $pid = $proc[Win32Ps::PROCESS_ID];

            if (in_array($exe, $targets)) {
                $phpProcsCount++;
                
                if ($pid != $excludePid) {
                    Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Terminating process: ' . $exe . ' (PID: ' . $pid . '), path: ' . $proc[Win32Ps::EXECUTABLE_PATH] . ' - ' . microtime(true));
                    try {
                        $terminationResult = Win32Ps::kill($pid);
                        if ($terminationResult) {
                            $terminatedCount++;
                            $result = true;
                            Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Successfully terminated process: ' . $pid . ' - ' . microtime(true));
                        } else {
                            Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Failed to terminate process: ' . $pid . ' (kill returned false) - ' . microtime(true));
                        }
                    } catch (Exception $e) {
                        Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Exception terminating process: ' . $pid . ' - Error: ' . $e->getMessage() . ' - ' . microtime(true));
                    }
                    usleep(100000); // 100ms delay between terminations
                } else {
                    Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Skipping current PHP process: ' . $pid . ' - ' . microtime(true));
                }
            }
        }
        
        Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Stats: ' . $phpProcsCount . ' PHP processes found, ' . $terminatedCount . ' terminated - ' . microtime(true));
        Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Termination overall result: ' . ($result ? 'success' : 'no processes terminated') . ' - ' . microtime(true));

        // 2. Update splash screen if available
        if ($splash !== null) {
            Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Updating splash screen with final message - ' . microtime(true));
            $splash->setTextLoading('Final cleanup...');
        }
        
        // 3. Initiate self-termination if needed
        if ($currentPid != $excludePid) {
            Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Initiating self-termination for PID: ' . $currentPid . ' - ' . microtime(true));
            try {
                $selfTermResult = Vbs::killProc($currentPid);
                Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Kill command issued for self, result: ' . ($selfTermResult ? 'success' : 'failed') . ' - ' . microtime(true));
            } catch (Exception $e) {
                Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Exception during self-termination: ' . $e->getMessage() . ' - ' . microtime(true));
            }
        }

        // 4. Destroy window after process termination
        if ($window && $bearsamppWinbinder) {
            Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Destroying window - ' . microtime(true));
            try {
                $windowResult = $bearsamppWinbinder->destroyWindow($window);
                Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Window destruction result: ' . ($windowResult ? 'success' : 'failed') . ' - ' . microtime(true));
            } catch (Exception $e) {
                Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Exception destroying window: ' . $e->getMessage() . ' - ' . microtime(true));
            }
        } else {
            Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] No window to destroy or WinBinder not available - ' . microtime(true));
        }
        
        Util::logTrace('ActionQuit::terminatePhpProcesses - [QUIT_FLOW] Termination process completed - ' . microtime(true));
        return $result;
    }
}
