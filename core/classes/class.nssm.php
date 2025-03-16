
<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class Nssm
 *
 * This class provides methods to manage Windows services using NSSM (Non-Sucking Service Manager).
 * It includes functionalities to create, delete, start, stop, and retrieve the status of services.
 * The class also logs operations and errors.
 */
class Nssm
{
    // Start params
    const SERVICE_AUTO_START = 'SERVICE_AUTO_START';
    const SERVICE_DELAYED_START = 'SERVICE_DELAYED_START';
    const SERVICE_DEMAND_START = 'SERVICE_DEMAND_START';
    const SERVICE_DISABLED = 'SERVICE_DISABLED';

    // Type params
    const SERVICE_WIN32_OWN_PROCESS = 'SERVICE_WIN32_OWN_PROCESS';
    const SERVICE_INTERACTIVE_PROCESS = 'SERVICE_INTERACTIVE_PROCESS';

    // Status
    const STATUS_CONTINUE_PENDING = 'SERVICE_CONTINUE_PENDING';
    const STATUS_PAUSE_PENDING = 'SERVICE_PAUSE_PENDING';
    const STATUS_PAUSED = 'SERVICE_PAUSED';
    const STATUS_RUNNING = 'SERVICE_RUNNING';
    const STATUS_START_PENDING = 'SERVICE_START_PENDING';
    const STATUS_STOP_PENDING = 'SERVICE_STOP_PENDING';
    const STATUS_STOPPED = 'SERVICE_STOPPED';
    const STATUS_NOT_EXIST = 'SERVICE_NOT_EXIST';
    const STATUS_NA = '-1';

    // Infos keys
    const INFO_APP_DIRECTORY = 'AppDirectory';
    const INFO_APPLICATION = 'Application';
    const INFO_APP_PARAMETERS = 'AppParameters';
    const INFO_APP_STDERR = 'AppStderr';
    const INFO_APP_STDOUT = 'AppStdout';
    const INFO_APP_ENVIRONMENT_EXTRA = 'AppEnvironmentExtra';

    const PENDING_TIMEOUT = 10;
    const SLEEP_TIME = 200000; // Reduced from 500000
    const MAX_STATUS_CHECKS = 5; // Maximum number of status checks

    private $name;
    private $displayName;
    private $binPath;
    private $params;
    private $start;
    private $stdout;
    private $stderr;
    private $environmentExtra;
    private $latestError;
    private $latestStatus;

    /**
     * Nssm constructor.
     * Initializes the Nssm class and logs the initialization.
     *
     * @param   string  $name  The name of the service.
     */
    public function __construct($name)
    {
        Util::logInitClass($this);
        $this->name = $name;
    }

    /**
     * Writes a log entry.
     *
     * @param   string  $log  The log message to write.
     */
    private function writeLog($log)
    {
        global $bearsamppRoot;
        Util::logDebug($log, $bearsamppRoot->getNssmLogFilePath());
    }

    /**
     * Writes an informational log entry.
     *
     * @param   string  $log  The log message to write.
     */
    private function writeLogInfo($log)
    {
        global $bearsamppRoot;
        Util::logInfo($log, $bearsamppRoot->getNssmLogFilePath());
    }

    /**
     * Writes an error log entry.
     *
     * @param   string  $log  The log message to write.
     */
    private function writeLogError($log)
    {
        global $bearsamppRoot;
        Util::logError($log, $bearsamppRoot->getNssmLogFilePath());
    }

    /**
     * Executes an NSSM command.
     *
     * @param   string  $args     The arguments for the NSSM command.
     * @param   int     $timeout  The timeout for the command in seconds.
     *
     * @return array|false The result of the execution, or false on failure.
     */
    private function exec($args, $timeout = 3)
    {
        global $bearsamppCore;

        $command = '"' . $bearsamppCore->getNssmExe() . '" ' . $args;
        $this->writeLogInfo('Cmd: ' . $command);

        $result = Batch::exec('nssm', $command, $timeout, true, false, true, true);
        if (is_array($result)) {
            $rebuildResult = array();
            foreach ($result as $row) {
                $row = trim($row);
                if (!empty($row)) {
                    $rebuildResult[] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $row);
                }
            }
            $result = $rebuildResult;
            if (count($result) > 1) {
                $this->latestError = implode(' ; ', $result);
            }

            return $result;
        }

        return false;
    }

    /**
     * Retrieves the status of the service.
     *
     * @param   bool  $timeout       Whether to apply a timeout for the status check.
     * @param   int   $timeoutSecs   The timeout duration in seconds.
     * @param   int   $maxChecks     Maximum number of status checks to perform.
     *
     * @return string The status of the service.
     */
    public function status($timeout = true, $timeoutSecs = self::PENDING_TIMEOUT, $maxChecks = self::MAX_STATUS_CHECKS)
    {
        usleep(self::SLEEP_TIME);

        $this->latestStatus = self::STATUS_NA;
        $maxtime = time() + $timeoutSecs;
        $checkCount = 0;

        while (($this->latestStatus == self::STATUS_NA || $this->isPending($this->latestStatus)) && $checkCount < $maxChecks) {
            $exec = $this->exec('status ' . $this->getName(), 2); // Very short timeout for status check
            $checkCount++;

            if ($exec !== false) {
                if (count($exec) > 1) {
                    $this->latestStatus = self::STATUS_NOT_EXIST;
                } else {
                    $this->latestStatus = $exec[0];
                }
            }

            if (($timeout && $maxtime < time()) || $checkCount >= $maxChecks) {
                $this->writeLog('Status check limit reached for service ' . $this->getName() . ' after ' . $checkCount . ' checks');
                break;
            }

            // Only sleep if we're going to do another check
            if ($checkCount < $maxChecks) {
                usleep(self::SLEEP_TIME);
            }
        }

        if ($this->latestStatus == self::STATUS_NOT_EXIST) {
            $this->latestError = 'Error 3: The specified service does not exist as an installed service.';
            $this->latestStatus = self::STATUS_NA;
        }

        return $this->latestStatus;
    }

    /**
     * Creates a new service.
     *
     * @return bool True if the service was created successfully, false otherwise.
     */
    public function create()
    {
        $this->writeLog('Create service');
        $this->writeLog('-> service: ' . $this->getName());
        $this->writeLog('-> display: ' . $this->getDisplayName());
        $this->writeLog('-> description: ' . $this->getDisplayName());
        $this->writeLog('-> path: ' . $this->getBinPath());
        $this->writeLog('-> params: ' . $this->getParams());
        $this->writeLog('-> stdout: ' . $this->getStdout());
        $this->writeLog('-> stderr: ' . $this->getStderr());
        $this->writeLog('-> environment extra: ' . $this->getEnvironmentExtra());
        $this->writeLog('-> start_type: ' . ($this->getStart() != null ? $this->getStart() : self::SERVICE_DEMAND_START));

        // Install bin
        $exec = $this->exec('install ' . $this->getName() . ' "' . $this->getBinPath() . '"');
        if ($exec === false) {
            return false;
        }

        // Params
        $exec = $this->exec('set ' . $this->getName() . ' AppParameters "' . $this->getParams() . '"');
        if ($exec === false) {
            return false;
        }

        // DisplayName
        $exec = $this->exec('set ' . $this->getName() . ' DisplayName "' . $this->getDisplayName() . '"');
        if ($exec === false) {
            return false;
        }

        // Description
        $exec = $this->exec('set ' . $this->getName() . ' Description "' . $this->getDisplayName() . '"');
        if ($exec === false) {
            return false;
        }

        // No AppNoConsole to fix nssm problems with Windows 10 Creators update.
        $exec = $this->exec('set ' . $this->getName() . ' AppNoConsole "1"');
        if ($exec === false) {
            return false;
        }

        // Start
        $exec = $this->exec('set ' . $this->getName() . ' Start "' . ($this->getStart() != null ? $this->getStart() : self::SERVICE_DEMAND_START) . '"');
        if ($exec === false) {
            return false;
        }

        // Stdout
        $exec = $this->exec('set ' . $this->getName() . ' AppStdout "' . $this->getStdout() . '"');
        if ($exec === false) {
            return false;
        }

        // Stderr
        $exec = $this->exec('set ' . $this->getName() . ' AppStderr "' . $this->getStderr() . '"');
        if ($exec === false) {
            return false;
        }

        // Environment Extra
        $exec = $this->exec('set ' . $this->getName() . ' AppEnvironmentExtra ' . $this->getEnvironmentExtra());
        if ($exec === false) {
            return false;
        }

        if (!$this->isInstalled()) {
            $this->latestError = null;

            return false;
        }

        return true;
    }

    /**
     * Attempts to kill processes related to the service to help with problematic service removal.
     */
    private function killRelatedProcesses()
    {
        global $bearsamppBins;

        if ($this->getName() == 'bearsampppostgresql') {
            $this->writeLog('Attempting to kill PostgreSQL related processes');

            // First try to terminate properly with increased timeout
            if (isset($bearsamppBins) && method_exists($bearsamppBins, 'getPostgresql') &&
                $bearsamppBins->getPostgresql() && method_exists($bearsamppBins->getPostgresql(), 'getCtlExe') &&
                file_exists($bearsamppBins->getPostgresql()->getCtlExe())) {

                $pgDataPath = Util::formatWindowsPath($bearsamppBins->getPostgresql()->getSymlinkPath()) . '\\data';
                $this->writeLog('PostgreSQL data path: ' . $pgDataPath);

                // Try immediate mode first
                $pgCtlCmd = '"' . Util::formatWindowsPath($bearsamppBins->getPostgresql()->getCtlExe()) .
                    '" stop -D "' . $pgDataPath . '" -m immediate';
                $this->writeLog('Executing immediate stop command: ' . $pgCtlCmd);
                Batch::execStandalone('pgCtlStopImmediate', $pgCtlCmd, true);
                
                // Wait longer for PostgreSQL to shut down
                sleep(3);
                
                // Check if processes are still running
                $stillRunning = false;
                $processes = [
                    'postgres.exe', 'pg_ctl.exe', 'postmaster.exe', 'pg_archiver.exe',
                    'pg_checkpointer.exe', 'pg_stat_monitor.exe', 'pg_wal_receiver.exe',
                    'pg_writer.exe'
                ];
                
                foreach ($processes as $process) {
                    $command = 'tasklist /FI "IMAGENAME eq ' . $process . '" /FO CSV';
                    $result = Batch::exec('checkProcess_' . $process, $command);
                    if ($result && count($result) > 1) {
                        $stillRunning = true;
                        break;
                    }
                }
                
                // If still running, try fast mode
                if ($stillRunning) {
                    $this->writeLog('Some PostgreSQL processes still running, trying fast mode');
                    $pgCtlCmd = '"' . Util::formatWindowsPath($bearsamppBins->getPostgresql()->getCtlExe()) .
                        '" stop -D "' . $pgDataPath . '" -m fast';
                    $this->writeLog('Executing fast stop command: ' . $pgCtlCmd);
                    Batch::execStandalone('pgCtlStopFast', $pgCtlCmd, true);
                    sleep(2);
                }
            }

            // Force kill all related processes
            $processes = [
                'postgres.exe', 'pg_ctl.exe', 'postmaster.exe', 'pg_archiver.exe',
                'pg_checkpointer.exe', 'pg_stat_monitor.exe', 'pg_wal_receiver.exe',
                'pg_writer.exe'
            ];
            
            foreach ($processes as $process) {
                $this->writeLog('Attempting to kill process: ' . $process);
                $command = 'taskkill /F /IM ' . $process . ' /T 2>nul';
                Batch::execStandalone('killPgProc_' . $process, $command, true);
                usleep(100000); // Small delay between kills
            }

            // Kill by service name
            $command = 'taskkill /F /FI "SERVICES eq bearsampppostgresql" /T 2>nul';
            Batch::execStandalone('killPostgresProcByService', $command, true);

            // Final verification that processes are gone
            sleep(1);
            $this->writeLog('Final verification of PostgreSQL process cleanup');
            $stillRunning = false;
            foreach ($processes as $process) {
                $command = 'tasklist /FI "IMAGENAME eq ' . $process . '" /FO CSV';
                $result = Batch::exec('checkProcess_' . $process, $command);
                if ($result && count($result) > 1) {
                    $this->writeLog('Process still running, attempting one more kill: ' . $process);
                    $command = 'taskkill /F /IM ' . $process . ' /T 2>nul';
                    Batch::execStandalone('finalKill_' . $process, $command, true);
                    $stillRunning = true;
                }
            }
            
            if ($stillRunning) {
                $this->writeLog('WARNING: Some PostgreSQL processes could not be terminated');
            } else {
                $this->writeLog('All PostgreSQL processes successfully terminated');
            }
        } else if ($this->getName() == 'bearsamppmemcached') {
            $this->writeLog('Attempting to kill Memcached related processes');

            // Kill by process name with timeout
            $command = 'taskkill /F /IM memcached.exe /T 2>nul';
            Batch::execStandalone('killMemcachedProc', $command, true, 5);

            // Also try to kill by service name with timeout
            $command = 'taskkill /F /FI "SERVICES eq bearsamppmemcached" /T 2>nul';
            Batch::execStandalone('killMemcachedProcByService', $command, true, 5);
            
            // Check if processes are still running
            $command = 'tasklist /FI "IMAGENAME eq memcached.exe" /FO CSV';
            $result = Batch::exec('checkMemcachedProcess', $command, 3);
            if ($result && count($result) > 1) {
                $this->writeLog('Memcached processes still running, attempting more aggressive termination');
                // Try one more time with higher priority
                Batch::execStandalone('killMemcachedProcAgain', 'taskkill /F /IM memcached.exe /T', true, 5);
            }
        }
    }

    /**
     * Deletes the service.
     *
     * @param  bool  $forceRemoval  Whether to force removal without verifying the service is gone.
     * @return bool True if the service was deleted successfully, false otherwise.
     */
    public function delete($forceRemoval = true)
    {
        // Special handling for PostgreSQL and Memcached
        if ($this->getName() == 'bearsampppostgresql' || $this->getName() == 'bearsamppmemcached') {
            $this->writeLog('Using aggressive removal for service ' . $this->getName());

            // Kill related processes first - this is critical for these problematic services
            $this->killRelatedProcesses();

            // For PostgreSQL, use more aggressive approach
            if ($this->getName() == 'bearsampppostgresql') {
                // Try to remove the service with NSSM as the first option
                Batch::execStandalone('removeServiceNssm',
                    '"' . Util::formatWindowsPath($GLOBALS['bearsamppCore']->getNssmExe()) . '" remove ' . $this->getName() . ' confirm',
                    true);

                // Longer delay to ensure NSSM has time to complete
                sleep(2);

                // Then try SC as a backup
                Batch::execStandalone('removeServiceSc',
                    'sc delete ' . $this->getName(),
                    true);

                // Final verification that the service is gone
                sleep(1);
                $scQueryCmd = 'sc query ' . $this->getName();
                $result = Batch::exec('scQuery', $scQueryCmd);
                
                if ($result && !preg_match('/1060/', implode(' ', $result))) {
                    $this->writeLog('Service still exists after removal attempts, using final fallback method');
                    
                    // Try forceful registry removal with more aggressive approach
                    Batch::execStandalone('forcefulRemoveService',
                        'sc delete ' . $this->getName() . ' && ' .
                        'reg delete "HKLM\\SYSTEM\\CurrentControlSet\\Services\\' . $this->getName() . '" /f && ' .
                        'reg delete "HKLM\\SYSTEM\\CurrentControlSet\\Services\\EventLog\\Application\\' . $this->getName() . '" /f',
                        true);
                        
                    // One more verification
                    sleep(1);
                    $result = Batch::exec('scQueryFinal', $scQueryCmd);
                    if ($result && !preg_match('/1060/', implode(' ', $result))) {
                        $this->writeLog('WARNING: Could not completely remove PostgreSQL service from registry');
                        
                        // Last resort - try to kill any wscript.exe processes that might be hanging
                        Batch::execStandalone('killWscript', 'taskkill /F /IM wscript.exe /T', true);
                    } else {
                        $this->writeLog('PostgreSQL service successfully removed');
                    }
                } else {
                    $this->writeLog('PostgreSQL service successfully removed');
                }
            } else if ($this->getName() == 'bearsamppmemcached') {
                // For Memcached, use more aggressive approach with timeouts
                // Try to remove the service with NSSM first with a timeout
                $nssmCmd = '"' . Util::formatWindowsPath($GLOBALS['bearsamppCore']->getNssmExe()) . '" remove ' . $this->getName() . ' confirm';
                Batch::execStandalone('removeServiceMemcached', $nssmCmd, true, 10); // Add 10-second timeout
            
                // Verify service is gone
                sleep(1);
                $scQueryCmd = 'sc query ' . $this->getName();
                $result = Batch::exec('scQuery', $scQueryCmd, 5); // Add 5-second timeout
            
                if ($result && !preg_match('/1060/', implode(' ', $result))) {
                    $this->writeLog('Memcached service still exists, using SC command');
                    Batch::execStandalone('scDeleteMemcached', 'sc delete ' . $this->getName(), true, 10); // Add 10-second timeout
                
                    // Check again
                    sleep(1);
                    $result = Batch::exec('scQueryAgain', $scQueryCmd, 5); // Add 5-second timeout
                
                    if ($result && !preg_match('/1060/', implode(' ', $result))) {
                        $this->writeLog('Memcached service still exists, using registry cleanup');
                        Batch::execStandalone('regDeleteMemcached',
                            'reg delete "HKLM\\SYSTEM\\CurrentControlSet\\Services\\' . $this->getName() . '" /f',
                            true, 10); // Add 10-second timeout
                    }
                }
            
                // Kill any remaining memcached processes regardless of service removal success
                $this->writeLog('Ensuring all Memcached processes are terminated');
                Batch::execStandalone('killMemcachedProc', 'taskkill /F /IM memcached.exe /T 2>nul', true, 5);
            
                // Final check to ensure service is gone
                sleep(1);
                $result = Batch::exec('scQueryFinal', $scQueryCmd, 5);
                if ($result && !preg_match('/1060/', implode(' ', $result))) {
                    $this->writeLog('WARNING: Could not completely remove Memcached service');
                } else {
                    $this->writeLog('Memcached service successfully removed');
                }
            }

            $this->writeLog('Forced removal of service ' . $this->getName() . ' complete');
            return true;
        }

        // For other services, use standard procedure but with better timeout handling
        $this->writeLog('Attempting to stop service ' . $this->getName() . ' before removal');

        // Try to stop the service with a short timeout
        $this->exec('stop ' . $this->getName(), 2);

        // Remove the service
        $this->writeLog('Deleting service ' . $this->getName());
        $exec = $this->exec('remove ' . $this->getName() . ' confirm', 3);

        if ($exec === false) {
            $this->writeLogError('Failed to execute service removal for ' . $this->getName());
            return false;
        }

        // If forcing removal, don't verify it's gone
        if ($forceRemoval) {
            return true;
        }

        // Quick check with minimal retries
        if ($this->isInstalled(false, 2, 2)) {
            $this->writeLogError('Service ' . $this->getName() . ' still appears to be installed after removal attempt');
            $this->latestError = 'Service removal failed - may need manual cleanup';
            return false;
        }

        $this->writeLog('Successfully removed service ' . $this->getName());
        return true;
    }

    /**
     * Starts the service.
     *
     * @return bool True if the service was started successfully, false otherwise.
     */
    public function start()
    {
        $this->writeLog('Start service ' . $this->getName());

        // Special handling for problematic services
        if ($this->getName() == 'bearsampppostgresql' || $this->getName() == 'bearsamppmemcached') {
            // Make sure no leftover processes are running before starting
            $this->killRelatedProcesses();
            
            // Longer delay for PostgreSQL to ensure clean state
            if ($this->getName() == 'bearsampppostgresql') {
                sleep(1); // Full second delay for PostgreSQL
            } else {
                usleep(200000); // Short delay for other services
            }
        }

        $exec = $this->exec('start ' . $this->getName(), 5);
        if ($exec === false) {
            return false;
        }

        // More generous check for PostgreSQL
        if ($this->getName() == 'bearsampppostgresql') {
            if (!$this->isRunning(true, 5, 5)) {
                $this->latestError = null;
                return false;
            }
        } else {
            // Quick check with minimal retries for other services
            if (!$this->isRunning(true, 3, 3)) {
                $this->latestError = null;
                return false;
            }
        }

        return true;
    }

    /**
     * Stops the service.
     *
     * @return bool True if the service was stopped successfully, false otherwise.
     */
    public function stop()
    {
        $this->writeLog('Stop service ' . $this->getName());

        $exec = $this->exec('stop ' . $this->getName(), 5);
        if ($exec === false) {
            return false;
        }

        // For problematic services, ensure processes are killed if service stop didn't work properly
        if (($this->getName() == 'bearsampppostgresql' || $this->getName() == 'bearsamppmemcached') &&
            !$this->isStopped(false, 2, 2)) {
            $this->writeLog('Service ' . $this->getName() . ' not stopping cleanly, killing processes');
            $this->killRelatedProcesses();
            usleep(300000); // Short delay
        }

        // Quick check with minimal retries
        if (!$this->isStopped(true, 3, 3)) {
            $this->latestError = null;
            return false;
        }

        return true;
    }

    /**
     * Restarts the service.
     *
     * @return bool True if the service was restarted successfully, false otherwise.
     */
    public function restart()
    {
        // For problematic services, use a more aggressive approach
        if ($this->getName() == 'bearsampppostgresql' || $this->getName() == 'bearsamppmemcached') {
            $this->writeLog('Using aggressive restart for service ' . $this->getName());

            // Force stop with process killing
            $this->stop();

            // Make sure processes are really gone
            $this->killRelatedProcesses();
            
            // Give it more time to fully clean up based on service type
            if ($this->getName() == 'bearsampppostgresql') {
                sleep(2); // Longer delay for PostgreSQL
            } else if ($this->getName() == 'bearsamppmemcached') {
                // For Memcached, verify processes are gone before continuing
                $command = 'tasklist /FI "IMAGENAME eq memcached.exe" /FO CSV';
                $result = Batch::exec('checkMemcachedProcess', $command, 3);
                if ($result && count($result) > 1) {
                    $this->writeLog('Memcached processes still running after initial kill, trying again');
                    Batch::execStandalone('finalKillMemcached', 'taskkill /F /IM memcached.exe /T', true, 5);
                    sleep(1); // Wait a full second after forced kill
                } else {
                    usleep(500000); // Standard delay if processes are gone
                }
            } else {
                usleep(500000); // Standard delay for other services
            }

            // Now start
            return $this->start();
        }

        // Standard approach for other services
        if ($this->stop()) {
            return $this->start();
        }

        return false;
    }

    /**
     * Retrieves information about the service.
     *
     * @return array|false The service information, or false on failure.
     */
    public function infos()
    {
        global $bearsamppRegistry;

        // For PostgreSQL, bypass the registry check to avoid hangs
        if ($this->getName() == 'bearsampppostgresql') {
            $this->writeLog('Using direct VBS service info for PostgreSQL to avoid registry hangs');
            return Vbs::getServiceInfos($this->getName());
        }

        try {
            $infos = Vbs::getServiceInfos($this->getName());
            if ($infos === false) {
                return false;
            }

            $infosNssm = array();
            $infosKeys = array(
                self::INFO_APPLICATION,
                self::INFO_APP_PARAMETERS,
            );

            foreach ($infosKeys as $infoKey) {
                $value = null;
                $exists = false;

                // Use try-catch to prevent PHP warnings
                try {
                    $exists = $bearsamppRegistry->exists(
                        Registry::HKEY_LOCAL_MACHINE,
                        'SYSTEM\CurrentControlSet\Services\\' . $this->getName() . '\Parameters',
                        $infoKey
                    );
                } catch (Exception $e) {
                    $this->writeLogError('Registry access exception: ' . $e->getMessage());
                    continue;
                }

                if ($exists) {
                    try {
                        $value = $bearsamppRegistry->getValue(
                            Registry::HKEY_LOCAL_MACHINE,
                            'SYSTEM\CurrentControlSet\Services\\' . $this->getName() . '\Parameters',
                            $infoKey
                        );
                    } catch (Exception $e) {
                        $this->writeLogError('Registry value exception: ' . $e->getMessage());
                    }
                }
                $infosNssm[$infoKey] = $value;
            }

            if (!isset($infosNssm[self::INFO_APPLICATION]) || $infosNssm[self::INFO_APPLICATION] === null) {
                // If registry access failed but we have service info from VBS, return what we have
                $this->writeLog('Registry access failed for service, using VBS info only');
                return $infos;
            }

            // Only merge pathnames if both parts exist
            if (!empty($infosNssm[self::INFO_APPLICATION])) {
                $appParams = '';
                // Fix: Check if APP_PARAMETERS is not null before using it
                if (isset($infosNssm[self::INFO_APP_PARAMETERS]) && $infosNssm[self::INFO_APP_PARAMETERS] !== null) {
                    $appParams = ' ' . $infosNssm[self::INFO_APP_PARAMETERS];
                }
                $infos[Win32Service::VBS_PATH_NAME] = $infosNssm[self::INFO_APPLICATION] . $appParams;
            }

            return $infos;
        } catch (Exception $e) {
            $this->writeLogError('Unhandled exception in infos(): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Checks if the service is installed.
     *
     * @param   bool  $logResult    Whether to log the result.
     * @param   int   $timeoutSecs  The timeout duration in seconds.
     * @param   int   $maxChecks    Maximum number of status checks to perform.
     *
     * @return bool True if the service is installed, false otherwise.
     */
    public function isInstalled($logResult = true, $timeoutSecs = self::PENDING_TIMEOUT, $maxChecks = self::MAX_STATUS_CHECKS)
    {
        $status = $this->status(true, $timeoutSecs, $maxChecks);

        if ($logResult) {
            $this->writeLog('isInstalled ' . $this->getName() . ': ' . ($status != self::STATUS_NA ? 'YES' : 'NO') . ' (status: ' . $status . ')');
        }

        return $status != self::STATUS_NA;
    }

    /**
     * Checks if the service is running.
     *
     * @param   bool  $logResult    Whether to log the result.
     * @param   int   $timeoutSecs  The timeout duration in seconds.
     * @param   int   $maxChecks    Maximum number of status checks to perform.
     *
     * @return bool True if the service is running, false otherwise.
     */
    public function isRunning($logResult = true, $timeoutSecs = self::PENDING_TIMEOUT, $maxChecks = self::MAX_STATUS_CHECKS)
    {
        $status = $this->status(true, $timeoutSecs, $maxChecks);

        if ($logResult) {
            $this->writeLog('isRunning ' . $this->getName() . ': ' . ($status == self::STATUS_RUNNING ? 'YES' : 'NO') . ' (status: ' . $status . ')');
        }

        return $status == self::STATUS_RUNNING;
    }

    /**
     * Checks if the service is stopped.
     *
     * @param   bool  $logResult    Whether to log the result.
     * @param   int   $timeoutSecs  The timeout duration in seconds.
     * @param   int   $maxChecks    Maximum number of status checks to perform.
     *
     * @return bool True if the service is stopped, false otherwise.
     */
    public function isStopped($logResult = true, $timeoutSecs = self::PENDING_TIMEOUT, $maxChecks = self::MAX_STATUS_CHECKS)
    {
        $status = $this->status(true, $timeoutSecs, $maxChecks);

        if ($logResult) {
            $this->writeLog('isStopped ' . $this->getName() . ': ' . ($status == self::STATUS_STOPPED ? 'YES' : 'NO') . ' (status: ' . $status . ')');
        }

        return $status == self::STATUS_STOPPED;
    }

    /**
     * Checks if the service is paused.
     *
     * @param   bool  $logResult    Whether to log the result.
     * @param   int   $timeoutSecs  The timeout duration in seconds.
     * @param   int   $maxChecks    Maximum number of status checks to perform.
     *
     * @return bool True if the service is paused, false otherwise.
     */
    public function isPaused($logResult = true, $timeoutSecs = self::PENDING_TIMEOUT, $maxChecks = self::MAX_STATUS_CHECKS)
    {
        $status = $this->status(true, $timeoutSecs, $maxChecks);

        if ($logResult) {
            $this->writeLog( 'isPaused ' . $this->getName() . ': ' . ($status == self::STATUS_PAUSED ? 'YES' : 'NO') . ' (status: ' . $status . ')' );
        }

        return $status == self::STATUS_PAUSED;
    }

    /**
     * Checks if the service status is pending.
     *
     * @param   string  $status  The status to check.
     *
     * @return bool True if the status is pending, false otherwise.
     */
    public function isPending($status)
    {
        return $status == self::STATUS_START_PENDING || $status == self::STATUS_STOP_PENDING
            || $status == self::STATUS_CONTINUE_PENDING || $status == self::STATUS_PAUSE_PENDING;
    }

    /**
     * Converts a decimal number to hexadecimal using Util::toHex.
     *
     * @param   int  $decimal  The decimal number to convert.
     *
     * @return string The hexadecimal representation of the number.
     */
    private function toHex($decimal)
    {
        return Util::toHex($decimal);
    }

    /**
     * Retrieves the description of the service status.
     *
     * @param   string  $status  The status to describe.
     *
     * @return string|null The description of the status, or null if not recognized.
     */
    private function getServiceStatusDesc($status)
    {
        switch ( $status ) {
            case self::STATUS_CONTINUE_PENDING:
                return 'The service continue is pending.';

            case self::STATUS_PAUSE_PENDING:
                return 'The service pause is pending.';

            case self::STATUS_PAUSED:
                return 'The service is paused.';

            case self::STATUS_RUNNING:
                return 'The service is running.';

            case self::STATUS_START_PENDING:
                return 'The service is starting.';

            case self::STATUS_STOP_PENDING:
                return 'The service is stopping.';

            case self::STATUS_STOPPED:
                return 'The service is not running.';

            case self::STATUS_NA:
                return 'Cannot retrieve service status.';

            default:
                return null;
        }
    }

    /**
     * Gets the name of the service.
     *
     * @return string The name of the service.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of the service.
     *
     * @param   string  $name  The name to set.
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the display name of the service.
     *
     * @return string The display name of the service.
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Sets the display name of the service.
     *
     * @param   string  $displayName  The display name to set.
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }

    /**
     * Gets the binary path of the service.
     *
     * @return string The binary path of the service.
     */
    public function getBinPath()
    {
        return $this->binPath;
    }

    /**
     * Sets the binary path of the service.
     *
     * @param   string  $binPath  The binary path to set.
     */
    public function setBinPath($binPath)
    {
        $this->binPath = str_replace( '"', '', Util::formatWindowsPath( $binPath ) );
    }

    /**
     * Gets the parameters of the service.
     *
     * @return string The parameters of the service.
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Sets the parameters of the service.
     *
     * @param   string  $params  The parameters to set.
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * Gets the start type of the service.
     *
     * @return string The start type of the service.
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Sets the start type of the service.
     *
     * @param   string  $start  The start type to set.
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * Gets the stdout path of the service.
     *
     * @return string The stdout path of the service.
     */
    public function getStdout()
    {
        return $this->stdout;
    }

    /**
     * Sets the stdout path of the service.
     *
     * @param   string  $stdout  The stdout path to set.
     */
    public function setStdout($stdout)
    {
        $this->stdout = $stdout;
    }

    /**
     * Gets the stderr path of the service.
     *
     * @return string The stderr path of the service.
     */
    public function getStderr()
    {
        return $this->stderr;
    }

    /**
     * Sets the stderr path of the service.
     *
     * @param   string  $stderr  The stderr path to set.
     */
    public function setStderr($stderr)
    {
        $this->stderr = $stderr;
    }

    /**
     * Gets the additional environment variables for the service.
     *
     * @return string The additional environment variables.
     */
    public function getEnvironmentExtra()
    {
        return $this->environmentExtra;
    }

    /**
     * Sets the additional environment variables for the service.
     *
     * @param   string  $environmentExtra  The additional environment variables to set.
     */
    public function setEnvironmentExtra($environmentExtra)
    {
        $this->environmentExtra = Util::formatWindowsPath( $environmentExtra );
    }

    /**
     * Gets the latest status of the service.
     *
     * @return string The latest status of the service.
     */
    public function getLatestStatus()
    {
        return $this->latestStatus;
    }

    /**
     * Gets the latest error message related to the service.
     *
     * @return string The latest error message.
     */
    public function getLatestError()
    {
        return $this->latestError;
    }

    /**
     * Retrieves the error message or status description of the service.
     *
     * @return string|null The error message or status description, or null if no error or status is available.
     */
    public function getError()
    {
        global $bearsamppLang;

        if ( !empty( $this->latestError ) ) {
            return $bearsamppLang->getValue( Lang::ERROR ) . ' ' . $this->latestError;
        }
        elseif ( $this->latestStatus != self::STATUS_NA ) {
            return $bearsamppLang->getValue( Lang::STATUS ) . ' ' . $this->latestStatus . ' : ' . $this->getServiceStatusDesc( $this->latestStatus );
        }

        return null;
    }
}
