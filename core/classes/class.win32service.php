<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class Win32Service
 *
 * This class provides an interface to manage Windows services. It includes methods to create, delete, start, stop, and query the status of services.
 * It also handles logging and error reporting for service operations.
 */
class Win32Service
{
    // Win32Service Service Status Constants
    const WIN32_SERVICE_CONTINUE_PENDING = '5';
    const WIN32_SERVICE_PAUSE_PENDING = '6';
    const WIN32_SERVICE_PAUSED = '7';
    const WIN32_SERVICE_RUNNING = '4';
    const WIN32_SERVICE_START_PENDING = '2';
    const WIN32_SERVICE_STOP_PENDING = '3';
    const WIN32_SERVICE_STOPPED = '1';
    const WIN32_SERVICE_NA = '0';

    // Win32 Error Codes
    const WIN32_ERROR_ACCESS_DENIED = '5';
    const WIN32_ERROR_CIRCULAR_DEPENDENCY = '423';
    const WIN32_ERROR_DATABASE_DOES_NOT_EXIST = '429';
    const WIN32_ERROR_DEPENDENT_SERVICES_RUNNING = '41B';
    const WIN32_ERROR_DUPLICATE_SERVICE_NAME = '436';
    const WIN32_ERROR_FAILED_SERVICE_CONTROLLER_CONNECT = '427';
    const WIN32_ERROR_INSUFFICIENT_BUFFER = '7A';
    const WIN32_ERROR_INVALID_DATA = 'D';
    const WIN32_ERROR_INVALID_HANDLE = '6';
    const WIN32_ERROR_INVALID_LEVEL = '7C';
    const WIN32_ERROR_INVALID_NAME = '7B';
    const WIN32_ERROR_INVALID_PARAMETER = '57';
    const WIN32_ERROR_INVALID_SERVICE_ACCOUNT = '421';
    const WIN32_ERROR_INVALID_SERVICE_CONTROL = '41C';
    const WIN32_ERROR_PATH_NOT_FOUND = '3';
    const WIN32_ERROR_SERVICE_ALREADY_RUNNING = '420';
    const WIN32_ERROR_SERVICE_CANNOT_ACCEPT_CTRL = '425';
    const WIN32_ERROR_SERVICE_DATABASE_LOCKED = '41F';
    const WIN32_ERROR_SERVICE_DEPENDENCY_DELETED = '433';
    const WIN32_ERROR_SERVICE_DEPENDENCY_FAIL = '42C';
    const WIN32_ERROR_SERVICE_DISABLED = '422';
    const WIN32_ERROR_SERVICE_DOES_NOT_EXIST = '424';
    const WIN32_ERROR_SERVICE_EXISTS = '431';
    const WIN32_ERROR_SERVICE_LOGON_FAILED = '42D';
    const WIN32_ERROR_SERVICE_MARKED_FOR_DELETE = '430';
    const WIN32_ERROR_SERVICE_NO_THREAD = '41E';
    const WIN32_ERROR_SERVICE_NOT_ACTIVE = '426';
    const WIN32_ERROR_SERVICE_REQUEST_TIMEOUT = '41D';
    const WIN32_ERROR_SHUTDOWN_IN_PROGRESS = '45B';
    const WIN32_NO_ERROR = '0';

    const SERVER_ERROR_IGNORE = '0';
    const SERVER_ERROR_NORMAL = '1';

    const SERVICE_AUTO_START = '2';
    const SERVICE_DEMAND_START = '3';
    const SERVICE_DISABLED = '4';

    const PENDING_TIMEOUT = 20;
    const SLEEP_TIME = 500000;

    const VBS_NAME = 'Name';
    const VBS_DISPLAY_NAME = 'DisplayName';
    const VBS_DESCRIPTION = 'Description';
    const VBS_PATH_NAME = 'PathName';
    const VBS_STATE = 'State';

    private $name;
    private $displayName;
    private $binPath;
    private $params;
    private $startType;
    private $errorControl;
    private $nssm;

    private $latestStatus;
    private $latestError;

    /**
     * Constructor for the Win32Service class.
     *
     * @param   string  $name  The name of the service.
     */
    public function __construct($name)
    {
        Util::logInitClass($this);
        Util::logTrace('Win32Service constructor initialized for service: ' . $name);
        $this->name = $name;
    }

    /**
     * Writes a log entry.
     *
     * @param   string  $log  The log message.
     */
    private function writeLog($log)
    {
        global $bearsamppRoot;
        Util::logDebug($log, $bearsamppRoot->getServicesLogFilePath());
        // Also send to trace log for debugging
        Util::logTrace($log);
    }

    /**
     * Returns an array of VBS keys used for service information.
     *
     * @return array The array of VBS keys.
     */
    public static function getVbsKeys()
    {
        return array(
            self::VBS_NAME,
            self::VBS_DISPLAY_NAME,
            self::VBS_DESCRIPTION,
            self::VBS_PATH_NAME,
            self::VBS_STATE
        );
    }

    /**
     * Safely converts a value to hexadecimal, handling null values.
     *
     * @param   mixed  $value  The value to convert to hexadecimal
     *
     * @return string The hexadecimal representation or '0' for null values
     */
    private function safeHex($value)
    {
        if ($value === null) {
            Util::logTrace('safeHex: Null value received, returning WIN32_NO_ERROR (0)');

            return self::WIN32_NO_ERROR; // Return '0' for null values
        }
        $result = dechex((int)$value);
        Util::logTrace('safeHex: Converted ' . $value . ' to hex: ' . $result);

        return $result;
    }

    /**
     * Calls a Win32 service function.
     *
     * @param   string  $function    The function name.
     * @param   mixed   $param       The parameter to pass to the function.
     * @param   bool    $checkError  Whether to check for errors.
     *
     * @return mixed The result of the function call.
     */
    private function callWin32Service($function, $param, $checkError = false)
    {
        $startTime   = Util::getMicrotime();
        $serviceName = is_array($param) ? ($param['service'] ?? $this->name) : $param;

        Util::logTrace("CALL START: Win32 function: {$function} for service: {$serviceName}");

        $result = false;
        if (function_exists($function)) {
            Util::logTrace("Function {$function} exists, executing now");
            $result = call_user_func($function, $param);

            if ($checkError && $result !== null) {
                $hexResult = $this->safeHex($result);
                if ($hexResult != self::WIN32_NO_ERROR) {
                    $this->latestError = $hexResult;
                    Util::logTrace('Error detected: ' . $hexResult . ' (decimal: ' . hexdec($hexResult) . ')');
                }
            }
        } else {
            Util::logTrace("Function {$function} does not exist");
        }

        $elapsedTime = round(Util::getMicrotime() - $startTime, 3);
        $resultStr   = is_array($result) ? json_encode($result) : (is_bool($result) ? ($result ? 'true' : 'false') : $result);
        Util::logTrace("CALL END: {$function} completed in {$elapsedTime}s with result: {$resultStr}");

        return $result;
    }

    /**
     * Queries the status of the service.
     *
     * @param   bool  $timeout  Whether to use a timeout.
     *
     * @return string The status of the service.
     */
    public function status($timeout = true)
    {
        $startTime = Util::getMicrotime();
        Util::logTrace('STATUS START: Checking status for service: ' . $this->getName() . ', timeout: ' . ($timeout ? 'yes' : 'no'));

        usleep(self::SLEEP_TIME);

        $this->latestStatus = self::WIN32_SERVICE_NA;
        $maxtime            = time() + self::PENDING_TIMEOUT;
        $iterations         = 0;

        while ($this->latestStatus == self::WIN32_SERVICE_NA || $this->isPending($this->latestStatus)) {
            $iterations++;
            $iterStartTime = Util::getMicrotime();
            Util::logTrace("Status check iteration #{$iterations} for " . $this->getName());

            $statusResult       = $this->callWin32Service('win32_query_service_status', $this->getName());
            $this->latestStatus = $statusResult;

            if (is_array($statusResult) && isset($statusResult['CurrentState'])) {
                $this->latestStatus = $this->safeHex($statusResult['CurrentState']);
                Util::logTrace('Service ' . $this->getName() . ' status array with CurrentState: ' . $this->latestStatus);

                // Log additional status details for debugging
                if (isset($statusResult['ControlsAccepted'])) {
                    Util::logTrace('Service ControlsAccepted: ' . $statusResult['ControlsAccepted']);
                }
                if (isset($statusResult['Win32ExitCode'])) {
                    Util::logTrace('Service Win32ExitCode: ' . $statusResult['Win32ExitCode']);
                }
            } elseif ($statusResult !== null) {
                $hexResult = $this->safeHex($statusResult);
                if ($hexResult == self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST) {
                    $this->latestStatus = $hexResult;
                    Util::logTrace('Service ' . $this->getName() . ' does not exist (error code: ' . $hexResult . ')');
                }
            }

            $iterElapsedTime = round(Util::getMicrotime() - $iterStartTime, 3);
            Util::logTrace("Status iteration #{$iterations} completed in {$iterElapsedTime}s with status: " . $this->latestStatus);

            if ($timeout && $maxtime < time()) {
                Util::logTrace("Status check timeout reached after {$iterations} iterations");
                break;
            }

            // If pending state, add a brief sleep to avoid excessive CPU usage
            if ($this->isPending($this->latestStatus)) {
                Util::logTrace('Service ' . $this->getName() . ' in pending state, waiting 100ms before next check');
                usleep(100000); // 100ms
            }
        }

        if ($this->latestStatus == self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST) {
            $this->latestError  = $this->latestStatus;
            $this->latestStatus = self::WIN32_SERVICE_NA;
            Util::logTrace('Service ' . $this->getName() . ' final status set to NA due to SERVICE_DOES_NOT_EXIST error');
        }

        $totalElapsedTime = round(Util::getMicrotime() - $startTime, 3);
        $statusDesc       = $this->getWin32ServiceStatusDesc($this->latestStatus) ?? 'Unknown status';
        Util::logTrace('STATUS END: Check for ' . $this->getName() . " completed in {$totalElapsedTime}s with final status: {$this->latestStatus} ({$statusDesc})");

        return $this->latestStatus;
    }

    /**
     * Creates the service.
     *
     * @return bool True if the service was created successfully, false otherwise.
     */
    public function create()
    {
        global $bearsamppBins;

        if ($this->getName() == BinPostgresql::SERVICE_NAME) {
            $bearsamppBins->getPostgresql()->rebuildConf();
            $bearsamppBins->getPostgresql()->initData();

            return Batch::installPostgresqlService();
        }
        if ($this->getNssm() instanceof Nssm) {
            $nssmEnvPath = Util::getAppBinsRegKey(false);
            $nssmEnvPath .= Util::getNssmEnvPaths();
            $nssmEnvPath .= '%SystemRoot%/system32;';
            $nssmEnvPath .= '%SystemRoot%;';
            $nssmEnvPath .= '%SystemRoot%/system32/Wbem;';
            $nssmEnvPath .= '%SystemRoot%/system32/WindowsPowerShell/v1.0';
            $this->getNssm()->setEnvironmentExtra('PATH=' . $nssmEnvPath);

            return $this->getNssm()->create();
        }

        $createResult = $this->callWin32Service('win32_create_service', array(
            'service'       => $this->getName(),
            'display'       => $this->getDisplayName(),
            'description'   => $this->getDisplayName(),
            'path'          => $this->getBinPath(),
            'params'        => $this->getParams(),
            'start_type'    => $this->getStartType() != null ? $this->getStartType() : self::SERVICE_DEMAND_START,
            'error_control' => $this->getErrorControl() != null ? $this->getErrorControl() : self::SERVER_ERROR_NORMAL,
        ), true);

        $create = $createResult !== null ? $this->safeHex($createResult) : self::WIN32_NO_ERROR;

        $this->writeLog('Create service: ' . $create . ' (status: ' . $this->status() . ')');
        $this->writeLog('-> service: ' . $this->getName());
        $this->writeLog('-> display: ' . $this->getDisplayName());
        $this->writeLog('-> description: ' . $this->getDisplayName());
        $this->writeLog('-> path: ' . $this->getBinPath());
        $this->writeLog('-> params: ' . $this->getParams());
        $this->writeLog('-> start_type: ' . ($this->getStartType() != null ? $this->getStartType() : self::SERVICE_DEMAND_START));
        $this->writeLog('-> service: ' . ($this->getErrorControl() != null ? $this->getErrorControl() : self::SERVER_ERROR_NORMAL));

        if ($create != self::WIN32_NO_ERROR) {
            return false;
        } elseif (!$this->isInstalled()) {
            $this->latestError = self::WIN32_NO_ERROR;

            return false;
        }

        return true;
    }

    /**
     * Deletes the service.
     *
     * @return bool True if the service was deleted successfully, false otherwise.
     */
    public function delete()
    {
        if (!$this->isInstalled()) {
            return true;
        }

        $this->stop();

        if ($this->getName() == BinPostgresql::SERVICE_NAME) {
            return Batch::uninstallPostgresqlService();
        }

        $deleteResult = $this->callWin32Service('win32_delete_service', $this->getName(), true);
        $delete       = $deleteResult !== null ? $this->safeHex($deleteResult) : self::WIN32_NO_ERROR;
        Util::logTrace('Delete service ' . $this->getName() . ': ' . $delete . ' (status: ' . $this->status() . ')');

        if ($delete != self::WIN32_NO_ERROR && $delete != self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST) {
            return false;
        } elseif ($this->isInstalled()) {
            $this->latestError = self::WIN32_NO_ERROR;

            return false;
        }

        return true;
    }

    /**
     * Resets the service by deleting and recreating it.
     *
     * @return bool True if the service was reset successfully, false otherwise.
     */
    public function reset()
    {
        if ($this->delete()) {
            usleep(self::SLEEP_TIME);

            return $this->create();
        }

        return false;
    }
    /**
     * Starts the service.
     *
     * @return bool True if the service was started successfully, false otherwise.
     */
    public function start()
    {
        global $bearsamppBins;

        $startTime = Util::getMicrotime();
        Util::logTrace('START SERVICE BEGIN: ' . $this->getName() . ' at ' . date('Y-m-d H:i:s'));
        Util::logInfo('Attempting to start service: ' . $this->getName());

        // Special handling for Apache to debug freezing issues
        if ($this->getName() == BinApache::SERVICE_NAME) {
            Util::logTrace('APACHE SERVICE: Preparing to start Apache service');
            Util::logTrace('APACHE SERVICE: Checking Apache configuration before startup');
            
            // Check if Apache config is valid before starting
            $cmdStartTime = Util::getMicrotime();
            $cmdOutput = $bearsamppBins->getApache()->getCmdLineOutput(BinApache::CMD_SYNTAX_CHECK);
            $cmdElapsed = round(Util::getMicrotime() - $cmdStartTime, 3);
            
            Util::logTrace('APACHE SERVICE: Config check completed in ' . $cmdElapsed . 's');
            
            if (!$cmdOutput['syntaxOk']) {
                Util::logTrace('APACHE SERVICE: Config check FAILED with output: ' . $cmdOutput['content']);
            } else {
                Util::logTrace('APACHE SERVICE: Config check PASSED. Proceeding to start service.');
            }
            
            // Log Apache module list to help diagnose potential module conflicts
            Util::logTrace('APACHE SERVICE: Listing loaded modules:');
            $modulesOutput = $bearsamppBins->getApache()->getCmdLineOutput('-M');
            if (isset($modulesOutput['content'])) {
                $moduleLines = explode("\n", $modulesOutput['content']);
                foreach ($moduleLines as $line) {
                    if (!empty(trim($line))) {
                        Util::logTrace('APACHE MODULE: ' . trim($line));
                    }
                }
            }
        } 
        // Handle other service initialization
        elseif ($this->getName() == BinMysql::SERVICE_NAME) {
            Util::logTrace('MYSQL SERVICE: Initializing data directory');
            $bearsamppBins->getMysql()->initData();
        }
        elseif ($this->getName() == BinMailpit::SERVICE_NAME) {
            Util::logTrace('MAILPIT SERVICE: Rebuilding configuration');
            $bearsamppBins->getMailpit()->rebuildConf();
        }
        elseif ($this->getName() == BinMemcached::SERVICE_NAME) {
            Util::logTrace('MEMCACHED SERVICE: Rebuilding configuration');
            $bearsamppBins->getMemcached()->rebuildConf();
        }
        elseif ($this->getName() == BinPostgresql::SERVICE_NAME) {
            Util::logTrace('POSTGRESQL SERVICE: Rebuilding configuration and initializing data directory');
            $bearsamppBins->getPostgresql()->rebuildConf();
            $bearsamppBins->getPostgresql()->initData();
        }
        elseif ($this->getName() == BinXlight::SERVICE_NAME) {
            Util::logTrace('XLIGHT SERVICE: Rebuilding configuration');
            $bearsamppBins->getXlight()->rebuildConf();
        }

        // Make the actual service start call
        $startCallTime = Util::getMicrotime();
        Util::logTrace('SERVICE CALL: Executing win32_start_service for ' . $this->getName());
        $startResult = $this->callWin32Service('win32_start_service', $this->getName(), true);
        $startCallElapsed = round(Util::getMicrotime() - $startCallTime, 3);
        $start = $startResult !== null ? $this->safeHex($startResult) : self::WIN32_NO_ERROR;
        
        Util::logTrace('SERVICE CALL: win32_start_service completed in ' . $startCallElapsed . 's with result: ' . $start);
        Util::logDebug('Start service ' . $this->getName() . ': ' . $start . ' (status: ' . $this->status() . ')');

        // Special handling for Apache status checks - potential freezing point
        if ($this->getName() == BinApache::SERVICE_NAME) {
            Util::logTrace('APACHE SERVICE: Checking running status after start call');
            $statusCheckTime = Util::getMicrotime();
            $isRunning = $this->isRunning();
            $statusCheckElapsed = round(Util::getMicrotime() - $statusCheckTime, 3);
            Util::logTrace('APACHE SERVICE: Status check completed in ' . $statusCheckElapsed . 's, result: ' . ($isRunning ? 'RUNNING' : 'NOT RUNNING'));
        }

        // Handle errors
        if ($start != self::WIN32_NO_ERROR && $start != self::WIN32_ERROR_SERVICE_ALREADY_RUNNING) {
            // Write error to log
            Util::logError('Failed to start service: ' . $this->getName() . ' with error code: ' . $start);
            Util::logTrace('SERVICE ERROR: Failed to start ' . $this->getName() . ' with error code: ' . $start);

            // Additional error handling for specific services
            if ($this->getName() == BinApache::SERVICE_NAME) {
                Util::logTrace('APACHE SERVICE: Start failed, checking syntax again...');
                $cmdOutput = $bearsamppBins->getApache()->getCmdLineOutput(BinApache::CMD_SYNTAX_CHECK);
                    
                if (!$cmdOutput['syntaxOk']) {
                    $errorMsg = '[' . date('Y-m-d H:i:s', time()) . '] [error] ' . $cmdOutput['content'] . PHP_EOL;
                    Util::logTrace('APACHE SERVICE: Syntax error detected: ' . $cmdOutput['content']);
                    file_put_contents(
                        $bearsamppBins->getApache()->getErrorLog(),
                        $errorMsg,
                        FILE_APPEND
                    );
                } else {
                    Util::logTrace('APACHE SERVICE: Syntax is valid despite start failure');
                }
                
                // Check Apache error log for recent errors
                Util::logTrace('APACHE SERVICE: Checking recent error log entries');
                $errorLogPath = $bearsamppBins->getApache()->getErrorLog();
                if (file_exists($errorLogPath)) {
                    $logContent = file_get_contents($errorLogPath);
                    if ($logContent) {
                        $logLines = explode("\n", $logContent);
                        $lastLines = array_slice($logLines, -20); // Get last 20 lines
                        foreach ($lastLines as $line) {
                            if (!empty(trim($line))) {
                                Util::logTrace('APACHE ERROR LOG: ' . trim($line));
                            }
                        }
                    } else {
                        Util::logTrace('APACHE SERVICE: Error log exists but is empty');
                    }
                } else {
                    Util::logTrace('APACHE SERVICE: Error log file not found at: ' . $errorLogPath);
                }
            }
            elseif ($this->getName() == BinMysql::SERVICE_NAME) {
                $cmdOutput = $bearsamppBins->getMysql()->getCmdLineOutput(BinMysql::CMD_SYNTAX_CHECK);
                if (!$cmdOutput['syntaxOk']) {
                    file_put_contents(
                        $bearsamppBins->getMysql()->getErrorLog(),
                        '[' . date('Y-m-d H:i:s', time()) . '] [error] ' . $cmdOutput['content'] . PHP_EOL,
                        FILE_APPEND
                    );
                }
            }
            elseif ($this->getName() == BinMariadb::SERVICE_NAME) {
                $cmdOutput = $bearsamppBins->getMariadb()->getCmdLineOutput(BinMariadb::CMD_SYNTAX_CHECK);
                if (!$cmdOutput['syntaxOk']) {
                    file_put_contents(
                        $bearsamppBins->getMariadb()->getErrorLog(),
                        '[' . date('Y-m-d H:i:s', time()) . '] [error] ' . $cmdOutput['content'] . PHP_EOL,
                        FILE_APPEND
                    );
                }
            }

            $totalElapsed = round(Util::getMicrotime() - $startTime, 3);
            Util::logTrace('START SERVICE END: ' . $this->getName() . ' FAILED after ' . $totalElapsed . 's');
            return false;
        }
        elseif (!$this->isRunning()) {
            $this->latestError = self::WIN32_NO_ERROR;
            Util::logError('Service ' . $this->getName() . ' is not running after start attempt.');
            Util::logTrace('SERVICE WARNING: ' . $this->getName() . ' is not in running state after successful start call');
            $this->latestError = null;
            
            $totalElapsed = round(Util::getMicrotime() - $startTime, 3);
            Util::logTrace('START SERVICE END: ' . $this->getName() . ' NOT RUNNING after ' . $totalElapsed . 's');
            return false;
        }

        $totalElapsed = round(Util::getMicrotime() - $startTime, 3);
        Util::logTrace('START SERVICE END: ' . $this->getName() . ' started successfully in ' . $totalElapsed . 's');
        Util::logInfo('Service ' . $this->getName() . ' started successfully.');
        return true;
    }

    /**
     * Stops the service.
     *
     * @return bool True if the service was stopped successfully, false otherwise.
     */
    public function stop()
    {
        $startTime = Util::getMicrotime();
        Util::logTrace('STOP SERVICE BEGIN: ' . $this->getName() . ' at ' . date('Y-m-d H:i:s'));

        // Special handling for Apache to debug freezing issues
        if ($this->getName() == BinApache::SERVICE_NAME) {
            Util::logTrace('APACHE SERVICE STOP: Current status: ' . $this->status(false));
        }

        $stopCallTime = Util::getMicrotime();
        Util::logTrace('SERVICE CALL: Executing win32_stop_service for ' . $this->getName());
        $stopResult = $this->callWin32Service('win32_stop_service', $this->getName(), true);
        $stopCallElapsed = round(Util::getMicrotime() - $stopCallTime, 3);
        $stop = $stopResult !== null ? $this->safeHex($stopResult) : self::WIN32_NO_ERROR;
        
        Util::logTrace('SERVICE CALL: win32_stop_service completed in ' . $stopCallElapsed . 's with result: ' . $stop);
        $this->writeLog('Stop service ' . $this->getName() . ': ' . $stop . ' (status: ' . $this->status() . ')');

        if ($stop != self::WIN32_NO_ERROR) {
            Util::logTrace('SERVICE ERROR: Failed to stop ' . $this->getName() . ' with error code: ' . $stop);
            
            $totalElapsed = round(Util::getMicrotime() - $startTime, 3);
            Util::logTrace('STOP SERVICE END: ' . $this->getName() . ' FAILED after ' . $totalElapsed . 's');
            return false;
        }
        elseif (!$this->isStopped()) {
            $this->latestError = self::WIN32_NO_ERROR;
            Util::logError('Service ' . $this->getName() . ' is not stopped after stop attempt.');
            Util::logTrace('SERVICE WARNING: ' . $this->getName() . ' is not in stopped state after successful stop call');
            
            // For Apache, try to diagnose why it's not stopping
            if ($this->getName() == BinApache::SERVICE_NAME) {
                Util::logTrace('APACHE SERVICE STOP: Service not stopping, checking processes');
                global $bearsamppCore;
                // Check if Apache processes are still running
                $cmd = 'tasklist /FI "IMAGENAME eq httpd.exe" /FO CSV';
                $result = Batch::exec('checkApacheProcesses', $cmd, 2);
                if ($result && count($result) > 1) {
                    Util::logTrace('APACHE SERVICE STOP: Apache processes still running');
                    foreach ($result as $line) {
                        Util::logTrace('APACHE PROCESS: ' . $line);
                    }
                    
                    // Try to force kill Apache processes
                    Util::logTrace('APACHE SERVICE STOP: Attempting to force kill Apache processes');
                    Batch::execStandalone('killApacheProcesses', 'taskkill /F /IM httpd.exe /T', true, 5);
                }
            }
            
            $totalElapsed = round(Util::getMicrotime() - $startTime, 3);
            Util::logTrace('STOP SERVICE END: ' . $this->getName() . ' NOT STOPPED after ' . $totalElapsed . 's');
            return false;
        }

        $totalElapsed = round(Util::getMicrotime() - $startTime, 3);
        Util::logTrace('STOP SERVICE END: ' . $this->getName() . ' stopped successfully in ' . $totalElapsed . 's');
        return true;
    }

    /**
     * Restarts the service by stopping and then starting it.
     *
     * @return bool True if the service was restarted successfully, false otherwise.
     */
    public function restart()
    {
        $startTime = Util::getMicrotime();
        Util::logTrace('RESTART SERVICE BEGIN: ' . $this->getName() . ' at ' . date('Y-m-d H:i:s'));
        
        // Special handling for Apache
        if ($this->getName() == BinApache::SERVICE_NAME) {
            Util::logTrace('APACHE SERVICE RESTART: Preparing to restart Apache service');
            Util::logTrace('APACHE SERVICE RESTART: Current status before stop: ' . $this->status(false));
        }
        
        $stopResult = $this->stop();
        Util::logTrace('SERVICE RESTART: Stop operation result: ' . ($stopResult ? 'SUCCESS' : 'FAILED'));
        
        if ($stopResult) {
            // Add a small delay between stop and start to ensure service has time to fully stop
            Util::logTrace('SERVICE RESTART: Adding 1 second delay between stop and start');
            usleep(1000000); // 1 second
            
            $startResult = $this->start();
            Util::logTrace('SERVICE RESTART: Start operation result: ' . ($startResult ? 'SUCCESS' : 'FAILED'));
            
            $totalElapsed = round(Util::getMicrotime() - $startTime, 3);
            if ($startResult) {
                Util::logTrace('RESTART SERVICE END: ' . $this->getName() . ' restarted successfully in ' . $totalElapsed . 's');
            } else {
                Util::logTrace('RESTART SERVICE END: ' . $this->getName() . ' restart FAILED (start failed) after ' . $totalElapsed . 's');
            }
            
            return $startResult;
        }
        
        $totalElapsed = round(Util::getMicrotime() - $startTime, 3);
        Util::logTrace('RESTART SERVICE END: ' . $this->getName() . ' restart FAILED (stop failed) after ' . $totalElapsed . 's');
        return false;
    }

/**
 * Retrieves information about the service.
 *
 * @return array|false The service information.
 */
public function infos()
{
    $startTime = Util::getMicrotime();
    Util::logTrace("INFOS BEGIN: Getting service info for " . $this->getName() . " at " . date('Y-m-d H:i:s'));

    try {
        // Special handling for Apache
        if ($this->getName() == BinApache::SERVICE_NAME) {
            Util::logTrace('APACHE SERVICE INFOS: Current status: ' . $this->status(false));
        }
        
        if ($this->getNssm() instanceof Nssm) {
            Util::logTrace("INFOS: Using NSSM to get service info");
            $nssmStartTime = Util::getMicrotime();
            $result = $this->getNssm()->infos();
            $nssmElapsed = round(Util::getMicrotime() - $nssmStartTime, 3);
            Util::logTrace("INFOS: NSSM service info completed in " . $nssmElapsed . "s");
            
            // Log the result structure
            if (is_array($result)) {
                Util::logTrace("INFOS: NSSM returned " . count($result) . " info items");
                foreach ($result as $key => $value) {
                    if (is_array($value)) {
                        Util::logTrace("INFOS: NSSM info[$key] = [array with " . count($value) . " items]");
                    } else {
                        Util::logTrace("INFOS: NSSM info[$key] = " . $value);
                    }
                }
            } else {
                Util::logTrace("INFOS: NSSM returned non-array result: " . (is_bool($result) ? ($result ? 'true' : 'false') : $result));
            }
            
            $totalElapsed = round(Util::getMicrotime() - $startTime, 3);
            Util::logTrace("INFOS END: Completed in " . $totalElapsed . "s for " . $this->getName());
            return $result;
        }

        // Use VBS for non-NSSM services
        Util::logTrace("INFOS: Using VBS to get service info");
        $vbsStartTime = Util::getMicrotime();
        $result = Vbs::getServiceInfos($this->getName());
        $vbsElapsed = round(Util::getMicrotime() - $vbsStartTime, 3);
        Util::logTrace("INFOS: VBS getServiceInfos completed in " . $vbsElapsed . "s");
        
        // Log the result structure
        if (is_array($result)) {
            Util::logTrace("INFOS: VBS returned " . count($result) . " info items");
            foreach ($result as $key => $value) {
                Util::logTrace("INFOS: VBS info[$key] = " . $value);
            }
        } else {
            Util::logTrace("INFOS: VBS returned non-array result: " . (is_bool($result) ? ($result ? 'true' : 'false') : $result));
        }
        
        $totalElapsed = round(Util::getMicrotime() - $startTime, 3);
        Util::logTrace("INFOS END: Completed in " . $totalElapsed . "s for " . $this->getName());
        return $result;
    } catch (Exception $e) {
        $totalElapsed = round(Util::getMicrotime() - $startTime, 3);
        Util::logTrace("INFOS ERROR: Exception after " . $totalElapsed . "s: " . $e->getMessage());
        return false;
    }
}

    /**
     * Checks if the service is installed.
     *
     * @return bool True if the service is installed, false otherwise.
     */
    public function isInstalled()
    {
        $startTime = Util::getMicrotime();
        Util::logTrace('IS_INSTALLED BEGIN: Checking if service ' . $this->getName() . ' is installed');
        
        $status = $this->status();
        $result = $status != self::WIN32_SERVICE_NA;
        
        $elapsed = round(Util::getMicrotime() - $startTime, 3);
        Util::logTrace('IS_INSTALLED END: Service ' . $this->getName() . ' is ' . ($result ? 'INSTALLED' : 'NOT INSTALLED') . ' (status: ' . $status . ') - completed in ' . $elapsed . 's');
        $this->writeLog('isInstalled ' . $this->getName() . ': ' . ($result ? 'YES' : 'NO') . ' (status: ' . $status . ')');

        return $result;
    }

    /**
     * Checks if the service is running.
     *
     * @return bool True if the service is running, false otherwise.
     */
    public function isRunning()
    {
        $startTime = Util::getMicrotime();
        Util::logTrace('IS_RUNNING BEGIN: Checking if service ' . $this->getName() . ' is running');
        
        $status = $this->status();
        $result = $status == self::WIN32_SERVICE_RUNNING;
        
        $elapsed = round(Util::getMicrotime() - $startTime, 3);
        Util::logTrace('IS_RUNNING END: Service ' . $this->getName() . ' is ' . ($result ? 'RUNNING' : 'NOT RUNNING') . ' (status: ' . $status . ') - completed in ' . $elapsed . 's');
        $this->writeLog('isRunning ' . $this->getName() . ': ' . ($result ? 'YES' : 'NO') . ' (status: ' . $status . ')');

        return $result;
    }

    /**
     * Checks if the service is stopped.
     *
     * @return bool True if the service is stopped, false otherwise.
     */
    public function isStopped()
    {
        $startTime = Util::getMicrotime();
        Util::logTrace('IS_STOPPED BEGIN: Checking if service ' . $this->getName() . ' is stopped');
        
        $status = $this->status();
        $result = $status == self::WIN32_SERVICE_STOPPED;
        
        $elapsed = round(Util::getMicrotime() - $startTime, 3);
        Util::logTrace('IS_STOPPED END: Service ' . $this->getName() . ' is ' . ($result ? 'STOPPED' : 'NOT STOPPED') . ' (status: ' . $status . ') - completed in ' . $elapsed . 's');
        $this->writeLog('isStopped ' . $this->getName() . ': ' . ($result ? 'YES' : 'NO') . ' (status: ' . $status . ')');

        return $result;
    }

    /**
     * Checks if the service is paused.
     *
     * @return bool True if the service is paused, false otherwise.
     */
    public function isPaused()
    {
        $startTime = Util::getMicrotime();
        Util::logTrace('IS_PAUSED BEGIN: Checking if service ' . $this->getName() . ' is paused');
        
        $status = $this->status();
        $result = $status == self::WIN32_SERVICE_PAUSED;
        
        $elapsed = round(Util::getMicrotime() - $startTime, 3);
        Util::logTrace('IS_PAUSED END: Service ' . $this->getName() . ' is ' . ($result ? 'PAUSED' : 'NOT PAUSED') . ' (status: ' . $status . ') - completed in ' . $elapsed . 's');
        $this->writeLog('isPaused ' . $this->getName() . ': ' . ($result ? 'YES' : 'NO') . ' (status: ' . $status . ')');

        return $result;
    }

    /**
     * Checks if the service is in a pending state.
     *
     * @param   string  $status  The status to check.
     *
     * @return bool True if the service is in a pending state, false otherwise.
     */
    public function isPending($status)
    {
        $isPending = $status == self::WIN32_SERVICE_START_PENDING || 
                     $status == self::WIN32_SERVICE_STOP_PENDING ||
                     $status == self::WIN32_SERVICE_CONTINUE_PENDING || 
                     $status == self::WIN32_SERVICE_PAUSE_PENDING;
        
        if ($isPending) {
            $pendingType = '';
            switch ($status) {
                case self::WIN32_SERVICE_START_PENDING:
                    $pendingType = 'START_PENDING';
                    break;
                case self::WIN32_SERVICE_STOP_PENDING:
                    $pendingType = 'STOP_PENDING';
                    break;
                case self::WIN32_SERVICE_CONTINUE_PENDING:
                    $pendingType = 'CONTINUE_PENDING';
                    break;
                case self::WIN32_SERVICE_PAUSE_PENDING:
                    $pendingType = 'PAUSE_PENDING';
                    break;
            }
            Util::logTrace('Service ' . $this->getName() . ' is in PENDING state: ' . $pendingType . ' (status: ' . $status . ')');
        }
        
        return $isPending;
    }

    /**
     * Returns a description of the Win32 service status.
     *
     * @param   string  $status  The status code.
     *
     * @return string|null The status description.
     */
    private function getWin32ServiceStatusDesc($status)
    {
        switch ( $status ) {
            case self::WIN32_SERVICE_CONTINUE_PENDING:
                return 'The service continue is pending.';
                break;

            case self::WIN32_SERVICE_PAUSE_PENDING:
                return 'The service pause is pending.';
                break;

            case self::WIN32_SERVICE_PAUSED:
                return 'The service is paused.';
                break;

            case self::WIN32_SERVICE_RUNNING:
                return 'The service is running.';
                break;

            case self::WIN32_SERVICE_START_PENDING:
                return 'The service is starting.';
                break;

            case self::WIN32_SERVICE_STOP_PENDING:
                return 'The service is stopping.';
                break;

            case self::WIN32_SERVICE_STOPPED:
                return 'The service is not running.';
                break;

            case self::WIN32_SERVICE_NA:
                return 'Cannot retrieve service status.';
                break;

            default:
                return null;
                break;
        }
    }

    /**
     * Returns a description of the Win32 error code.
     *
     * @param   string  $code  The error code.
     *
     * @return string|null The description of the error code, or null if the code is not recognized.
     */
    private function getWin32ErrorCodeDesc($code)
    {
        switch ( $code ) {
            case self::WIN32_ERROR_ACCESS_DENIED:
                return 'The handle to the SCM database does not have the appropriate access rights.';
            // ... other cases ...
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
     * Gets the parameters for the service.
     *
     * @return string The parameters for the service.
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Sets the parameters for the service.
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
    public function getStartType()
    {
        return $this->startType;
    }

    /**
     * Sets the start type of the service.
     *
     * @param   string  $startType  The start type to set.
     */
    public function setStartType($startType)
    {
        $this->startType = $startType;
    }

    /**
     * Gets the error control setting of the service.
     *
     * @return string The error control setting of the service.
     */
    public function getErrorControl()
    {
        return $this->errorControl;
    }

    /**
     * Sets the error control setting of the service.
     *
     * @param   string  $errorControl  The error control setting to set.
     */
    public function setErrorControl($errorControl)
    {
        $this->errorControl = $errorControl;
    }

    /**
     * Gets the NSSM instance associated with the service.
     *
     * @return Nssm The NSSM instance.
     */
    public function getNssm()
    {
        return $this->nssm;
    }

    /**
     * Sets the NSSM instance associated with the service.
     *
     * @param   Nssm  $nssm  The NSSM instance to set.
     */
    public function setNssm($nssm)
    {
        if ( $nssm instanceof Nssm ) {
            $this->setDisplayName( $nssm->getDisplayName() );
            $this->setBinPath( $nssm->getBinPath() );
            $this->setParams( $nssm->getParams() );
            $this->setStartType( $nssm->getStart() );
            $this->nssm = $nssm;
        }
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
     * Gets the latest error encountered by the service.
     *
     * @return string The latest error encountered by the service.
     */
    public function getLatestError()
    {
        return $this->latestError;
    }

    /**
     * Gets a detailed error message for the latest error encountered by the service.
     *
     * @return string|null The detailed error message, or null if no error.
     */
    public function getError()
    {
        global $bearsamppLang;
        if ( $this->latestError != self::WIN32_NO_ERROR ) {
            $decError = $this->latestError !== null ? hexdec($this->latestError) : 0;
            return $bearsamppLang->getValue( Lang::ERROR ) . ' ' .
                $this->latestError . ' (' . $decError . ' : ' . $this->getWin32ErrorCodeDesc( $this->latestError ) . ')';
        }
        elseif ( $this->latestStatus != self::WIN32_SERVICE_NA ) {
            $decStatus = $this->latestStatus !== null ? hexdec($this->latestStatus) : 0;
            return $bearsamppLang->getValue( Lang::STATUS ) . ' ' .
                $this->latestStatus . ' (' . $decStatus . ' : ' . $this->getWin32ServiceStatusDesc( $this->latestStatus ) . ')';
        }

        return null;
    }
}
