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

    // Track which functions have been logged to avoid duplicate log entries
    private static $loggedFunctions = array();

    // Cache of services that are known to not exist
    private static $nonExistentServices = array();

    /**
     * Constructor for the Win32Service class.
     *
     * @param   string  $name  The name of the service.
     */
    public function __construct($name)
    {
        Util::logInitClass( $this );
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
        Util::logDebug( $log, $bearsamppRoot->getServicesLogFilePath() );
    }

    /**
     * Marks a service as non-existent in the static cache.
     * This prevents redundant status checks for services that don't exist.
     *
     * @param   string  $serviceName  The name of the service to mark as non-existent.
     */
    public static function markServiceAsNonExistent($serviceName)
    {
        self::$nonExistentServices[$serviceName] = true;
        Util::logTrace('Service marked as non-existent in cache: ' . $serviceName);
    }

    /**
     * Checks if a service is marked as non-existent in the static cache.
     *
     * @param   string  $serviceName  The name of the service to check.
     * @return  bool    True if the service is marked as non-existent, false otherwise.
     */
    public static function isServiceNonExistent($serviceName)
    {
        return isset(self::$nonExistentServices[$serviceName]);
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
        $result = false;
        if ( function_exists( $function ) ) {
            if (!isset(self::$loggedFunctions[$function])) {
                Util::logTrace('Win32 function: ' . $function . ' exists');
                self::$loggedFunctions[$function] = true;
            }

            // Add timeout protection for Win32 service calls
            // This prevents hanging if the service call doesn't respond
            $timeout = 5; // 5 seconds timeout
            $startTime = time();

            // Use set_time_limit to prevent script timeout
            $originalTimeLimit = ini_get('max_execution_time');
            set_time_limit($timeout + 10);

            // Use a separate process for potentially hanging calls
            if (in_array($function, array('win32_query_service_status', 'win32_delete_service', 'win32_stop_service'))) {
                Util::logTrace('Using protected call for ' . $function);

                // Create a unique temporary file for this call
                $tempFile = sys_get_temp_dir() . '\\bearsampp_' . uniqid() . '.tmp';

                // Create a PHP script that will execute the function and save the result
                $script = '<?php
                $result = ' . $function . '("' . addslashes($param) . '");
                file_put_contents("' . addslashes($tempFile) . '", serialize($result));
                // Create a signal file to indicate completion
                file_put_contents("' . addslashes($tempFile . '.done') . '", "done");
                ?>';

                $scriptFile = sys_get_temp_dir() . '\\bearsampp_script_' . uniqid() . '.php';
                file_put_contents($scriptFile, $script);

                // Create a VBS script to run the PHP script completely hidden
                $vbsFile = sys_get_temp_dir() . '\\bearsampp_runner_' . uniqid() . '.vbs';
                $phpPath = str_replace('\\', '\\\\', dirname(PHP_BINARY) . '\\php.exe');
                $scriptPath = str_replace('\\', '\\\\', $scriptFile);
                $currentDir = str_replace('\\', '\\\\', getcwd());

                // Create a simple VBS script that runs the command hidden
                $vbsContent = 'Set WshShell = CreateObject("WScript.Shell")' . PHP_EOL;
                $vbsContent .= 'WshShell.Run """' . $phpPath . '" "' . $scriptPath . '""", 0, True' . PHP_EOL;
                file_put_contents($vbsFile, $vbsContent);

                // Execute the VBS script which will run PHP hidden
                // Use wscript instead of cscript to hide the console window completely
                $cmd = 'wscript "' . $vbsFile . '"';
                Util::logTrace('Executing hidden command via VBS: ' . $cmd);

                // Execute with timeout
                $descriptorspec = array(
                    0 => array("pipe", "r"),
                    1 => array("pipe", "w"),
                    2 => array("pipe", "w")
                );

                $process = proc_open($cmd, $descriptorspec, $pipes, getcwd());

                if (is_resource($process)) {
                    // Wait for completion or timeout
                    $waitTime = 0;
                    $interval = 0.1; // Check every 100ms

                    while ($waitTime < $timeout) {
                        // Check if the process has completed
                        $status = proc_get_status($process);
                        if (!$status['running']) {
                            break;
                        }

                        // Check if either the result file or the signal file exists
                        if (file_exists($tempFile) || file_exists($tempFile . '.done')) {
                            break;
                        }

                        usleep(100000); // 100ms
                        $waitTime += $interval;
                    }

                    // If we timed out, terminate the process
                    if ($waitTime >= $timeout) {
                        Util::logTrace('Function call timed out after ' . $timeout . ' seconds');

                        // On Windows, we need to use taskkill to terminate the process
                        $pid = $status['pid'];
                        exec('taskkill /F /T /PID ' . $pid . ' 2>&1');

                        proc_close($process);
                    } else {
                        proc_close($process);
                    }
                }

                // Check if we have a result
                if (file_exists($tempFile)) {
                    $result = unserialize(file_get_contents($tempFile));
                    unlink($tempFile);
                    Util::logTrace('Function call completed successfully with result');
                } elseif (file_exists($tempFile . '.done')) {
                    // The signal file exists but the result file doesn't
                    // This means the PHP script completed but didn't create a result file
                    Util::logTrace('Function call completed but no result file was created');
                    if ($function == 'win32_query_service_status') {
                        $result = self::WIN32_SERVICE_NA;
                    } else {
                        $result = null;
                    }
                } else {
                    Util::logTrace('Function call failed or timed out');
                    // Set a default result for timeout
                    if ($function == 'win32_query_service_status') {
                        $result = self::WIN32_SERVICE_NA;
                    } else {
                        $result = null;
                    }
                }

                // Clean up the script files and signal file
                if (file_exists($scriptFile)) {
                    unlink($scriptFile);
                }
                if (file_exists($vbsFile)) {
                    unlink($vbsFile);
                }
                if (file_exists($tempFile . '.done')) {
                    unlink($tempFile . '.done');
                }
            } else {
                // For other functions, call directly
                $result = call_user_func($function, $param);
            }

            // Restore original time limit
            set_time_limit($originalTimeLimit);

            if ($checkError && $result !== null && dechex((int)$result) != self::WIN32_NO_ERROR) {
                $this->latestError = $result !== null ? dechex((int)$result) : '0';
            }
        } else {
            if (!isset(self::$loggedFunctions[$function])) {
                Util::logTrace('Win32 function: ' . $function . ' missing');
                self::$loggedFunctions[$function] = true;
            }

            // Handle missing functions gracefully
            if ($function == 'win32_query_service_status') {
                Util::logTrace('Handling missing win32_query_service_status function');
                $this->latestStatus = self::WIN32_SERVICE_NA;
                $this->latestError = self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST;
            }
        }
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
        // First check if the service is already known to not exist
        // This is the fastest check and avoids any external calls
        if (self::isServiceNonExistent($this->getName())) {
            Util::logTrace('Service already known to not exist in status: ' . $this->getName());
            $this->latestError = self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST;
            $this->latestStatus = self::WIN32_SERVICE_NA;
            return self::WIN32_SERVICE_NA;
        }

        usleep( self::SLEEP_TIME );

        $this->latestStatus = self::WIN32_SERVICE_NA;
        $maxtime = time() + self::PENDING_TIMEOUT;

        // Check if function exists before entering the loop
        if (!function_exists('win32_query_service_status')) {
            Util::logTrace('win32_query_service_status function missing, returning NA status');
            $this->latestError = self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST;
            return self::WIN32_SERVICE_NA;
        }

        // Set a maximum number of attempts to prevent infinite loops
        $maxAttempts = 5;
        $attempts = 0;

        // First attempt - if it fails with "service does not exist", don't try again
        $attempts++;
        Util::logTrace('Checking service status, attempt ' . $attempts . ' of ' . $maxAttempts);

        $this->latestStatus = $this->callWin32Service('win32_query_service_status', $this->getName());

        if (is_array($this->latestStatus) && isset($this->latestStatus['CurrentState'])) {
            $this->latestStatus = dechex((int)$this->latestStatus['CurrentState']);
            Util::logTrace('Service status retrieved: ' . $this->latestStatus);
        }
        elseif ($this->latestStatus !== null && dechex((int)$this->latestStatus) == self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST) {
            // Service doesn't exist - cache this result and return immediately
            $this->latestStatus = dechex((int)$this->latestStatus);
            Util::logTrace('Service does not exist: ' . $this->latestStatus);
            self::$nonExistentServices[$this->getName()] = true;
            $this->latestError = $this->latestStatus;
            $this->latestStatus = self::WIN32_SERVICE_NA;
            Util::logTrace('Final status: Service does not exist');
            return self::WIN32_SERVICE_NA;
        }
        elseif ($this->latestStatus === null || $this->latestStatus === false) {
            // Function call failed or timed out - assume service doesn't exist
            Util::logTrace('Status check failed or timed out - assuming service does not exist');
            self::$nonExistentServices[$this->getName()] = true;
            $this->latestError = self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST;
            $this->latestStatus = self::WIN32_SERVICE_NA;
            Util::logTrace('Final status: Service does not exist (assumed)');
            return self::WIN32_SERVICE_NA;
        }

        // Only continue with additional attempts if the service exists and is in a pending state
        while (($this->isPending($this->latestStatus)) && $attempts < $maxAttempts) {
            $attempts++;
            Util::logTrace('Checking service status, attempt ' . $attempts . ' of ' . $maxAttempts);

            $this->latestStatus = $this->callWin32Service('win32_query_service_status', $this->getName());

            if (is_array($this->latestStatus) && isset($this->latestStatus['CurrentState'])) {
                $this->latestStatus = dechex((int)$this->latestStatus['CurrentState']);
                Util::logTrace('Service status retrieved: ' . $this->latestStatus);
            }
            elseif ($this->latestStatus !== null && dechex((int)$this->latestStatus) == self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST) {
                $this->latestStatus = dechex((int)$this->latestStatus);
                Util::logTrace('Service does not exist: ' . $this->latestStatus);
                break;
            }

            // Check for timeout
            if ($timeout && $maxtime < time()) {
                Util::logTrace('Status check timed out after ' . self::PENDING_TIMEOUT . ' seconds');
                break;
            }

            // Add a small delay between attempts
            if ($attempts < $maxAttempts && $this->isPending($this->latestStatus)) {
                usleep(self::SLEEP_TIME);
            }
        }

        if ($this->latestStatus == self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST) {
            self::$nonExistentServices[$this->getName()] = true;
            $this->latestError = $this->latestStatus;
            $this->latestStatus = self::WIN32_SERVICE_NA;
            Util::logTrace('Final status: Service does not exist');
        } else {
            Util::logTrace('Final status: ' . $this->latestStatus);
        }

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

        Util::logTrace("Starting Win32Service::create for service: " . $this->getName());

        if ( $this->getName() == BinPostgresql::SERVICE_NAME ) {
            Util::logTrace("PostgreSQL service detected - using specialized installation");
            $bearsamppBins->getPostgresql()->rebuildConf();
            Util::logTrace("PostgreSQL configuration rebuilt");

            $bearsamppBins->getPostgresql()->initData();
            Util::logTrace("PostgreSQL data initialized");

            $result = Batch::installPostgresqlService();
            Util::logTrace("PostgreSQL service installation " . ($result ? "succeeded" : "failed"));
            return $result;
        }

        if ( $this->getNssm() instanceof Nssm ) {
            Util::logTrace("Using NSSM for service installation");

            $nssmEnvPath = Util::getAppBinsRegKey( false );
            Util::logTrace("NSSM environment path (bins): " . $nssmEnvPath);

            $nssmEnvPath .= Util::getNssmEnvPaths();
            Util::logTrace("NSSM environment path (with additional paths): " . $nssmEnvPath);

            $nssmEnvPath .= '%SystemRoot%/system32;';
            $nssmEnvPath .= '%SystemRoot%;';
            $nssmEnvPath .= '%SystemRoot%/system32/Wbem;';
            $nssmEnvPath .= '%SystemRoot%/system32/WindowsPowerShell/v1.0';
            Util::logTrace("NSSM final environment PATH: " . $nssmEnvPath);

            $this->getNssm()->setEnvironmentExtra( 'PATH=' . $nssmEnvPath );
            Util::logTrace("NSSM service parameters:");
            Util::logTrace("-> Name: " . $this->getNssm()->getName());
            Util::logTrace("-> DisplayName: " . $this->getNssm()->getDisplayName());
            Util::logTrace("-> BinPath: " . $this->getNssm()->getBinPath());
            Util::logTrace("-> Params: " . $this->getNssm()->getParams());
            Util::logTrace("-> Start: " . $this->getNssm()->getStart());
            Util::logTrace("-> Stdout: " . $this->getNssm()->getStdout());
            Util::logTrace("-> Stderr: " . $this->getNssm()->getStderr());

            $result = $this->getNssm()->create();
            Util::logTrace("NSSM service creation " . ($result ? "succeeded" : "failed"));
            if (!$result) {
                Util::logTrace("NSSM error: " . $this->getNssm()->getLatestError());
            }
            return $result;
        }

        Util::logTrace("Using win32_create_service for service installation");
        $serviceParams = array(
            'service'       => $this->getName(),
            'display'       => $this->getDisplayName(),
            'description'   => $this->getDisplayName(),
            'path'          => $this->getBinPath(),
            'params'        => $this->getParams(),
            'start_type'    => $this->getStartType() != null ? $this->getStartType() : self::SERVICE_DEMAND_START,
            'error_control' => $this->getErrorControl() != null ? $this->getErrorControl() : self::SERVER_ERROR_NORMAL,
        );

        Util::logTrace("win32_create_service parameters:");
        foreach ($serviceParams as $key => $value) {
            Util::logTrace("-> $key: $value");
        }

        $result = $this->callWin32Service( 'win32_create_service', $serviceParams, true );
        $create = $result !== null ? dechex( (int)$result ) : '0';
        Util::logTrace("win32_create_service result code: " . $create);

        $this->writeLog( 'Create service: ' . $create . ' (status: ' . $this->status() . ')' );
        $this->writeLog( '-> service: ' . $this->getName() );
        $this->writeLog( '-> display: ' . $this->getDisplayName() );
        $this->writeLog( '-> description: ' . $this->getDisplayName() );
        $this->writeLog( '-> path: ' . $this->getBinPath() );
        $this->writeLog( '-> params: ' . $this->getParams() );
        $this->writeLog( '-> start_type: ' . ($this->getStartType() != null ? $this->getStartType() : self::SERVICE_DEMAND_START) );
        $this->writeLog( '-> service: ' . ($this->getErrorControl() != null ? $this->getErrorControl() : self::SERVER_ERROR_NORMAL) );

        if ( $create != self::WIN32_NO_ERROR ) {
            Util::logTrace("Service creation failed with error code: " . $create);
            return false;
        }
        elseif ( !$this->isInstalled() ) {
            Util::logTrace("Service created but not found as installed");
            $this->latestError = self::WIN32_NO_ERROR;
            return false;
        }

        Util::logTrace("Service created successfully: " . $this->getName());
        return true;
    }

    /**
     * Deletes the service.
     *
     * @return bool True if the service was deleted successfully, false otherwise.
     */
    public function delete()
    {
        Util::logTrace('Starting delete for service: ' . $this->getName());

        // First check if the service is already known to not exist
        if (self::isServiceNonExistent($this->getName())) {
            Util::logTrace('Service already known to not exist, skipping deletion: ' . $this->getName());
            return true;
        }

        // Use a direct status check instead of isInstalled() to avoid multiple checks
        $status = $this->status();
        if ($status == self::WIN32_SERVICE_NA) {
            Util::logTrace('Service not installed, skipping deletion: ' . $this->getName());
            return true;
        }

        // Only stop the service if it's running
        if ($status == self::WIN32_SERVICE_RUNNING) {
            Util::logTrace('Stopping running service before deletion: ' . $this->getName());
            $this->stop();
        } else {
            Util::logTrace('Service not running, no need to stop before deletion');
        }

        if ($this->getName() == BinPostgresql::SERVICE_NAME) {
            Util::logTrace('Using specialized uninstall for PostgreSQL service');
            return Batch::uninstallPostgresqlService();
        }

        // Check if function exists before calling
        if (!function_exists('win32_delete_service')) {
            Util::logTrace('win32_delete_service function missing, using alternative approach');
            // If the function doesn't exist, we'll consider the service as deleted
            // This prevents freezing when the Win32 service functions are not available
            $this->latestStatus = self::WIN32_SERVICE_NA;
            $this->latestError = self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST;
            return true;
        }

        Util::logTrace('Calling win32_delete_service for: ' . $this->getName());
        $result = $this->callWin32Service('win32_delete_service', $this->getName(), true);
        $delete = $result !== null ? dechex((int)$result) : '0';

        // Log the deletion result without checking status
        $this->writeLog('Delete service ' . $this->getName() . ': ' . $delete);

        // If the delete operation returned success or "service does not exist", consider it successful
        if ($delete == self::WIN32_NO_ERROR || $delete == self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST) {
            Util::logTrace('Service deletion reported success with code: ' . $delete);

            // Set the status directly instead of calling status() again
            $this->latestStatus = self::WIN32_SERVICE_NA;

            // We can't directly access the static cache in status(), but we can
            // set a flag that will be used by future status checks
            self::markServiceAsNonExistent($this->getName());

            Util::logTrace('Service deleted successfully: ' . $this->getName());
            return true;
        } else {
            Util::logTrace('Service deletion failed with error code: ' . $delete);

            // For unexpected errors, we'll do a single status check
            // but we won't use isInstalled() to avoid multiple checks
            $finalStatus = $this->status(false); // Use false to avoid timeout loops
            if ($finalStatus != self::WIN32_SERVICE_NA) {
                Util::logTrace('Service still appears to be installed after deletion attempt');
                $this->latestError = self::WIN32_NO_ERROR;
                return false;
            }

            // If we get here, the service doesn't exist despite the error
            $this->latestStatus = self::WIN32_SERVICE_NA;
            return true;
        }
    }

    /**
     * Resets the service by deleting and recreating it.
     *
     * @return bool True if the service was reset successfully, false otherwise.
     */
    public function reset()
    {
        if ( $this->delete() ) {
            usleep( self::SLEEP_TIME );

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

        Util::logInfo('Attempting to start service: ' . $this->getName());

        if ( $this->getName() == BinMysql::SERVICE_NAME ) {
            $bearsamppBins->getMysql()->initData();
        }
        elseif ( $this->getName() == BinMailpit::SERVICE_NAME ) {
            $bearsamppBins->getMailpit()->rebuildConf();
        }
        elseif ( $this->getName() == BinMemcached::SERVICE_NAME ) {
            $bearsamppBins->getMemcached()->rebuildConf();
        }
        elseif ( $this->getName() == BinPostgresql::SERVICE_NAME ) {
            $bearsamppBins->getPostgresql()->rebuildConf();
            $bearsamppBins->getPostgresql()->initData();
        }
        elseif ( $this->getName() == BinXlight::SERVICE_NAME ) {
            $bearsamppBins->getXlight()->rebuildConf();
        }


        $result = $this->callWin32Service( 'win32_start_service', $this->getName(), true );
        $start = $result !== null ? dechex( (int)$result ) : '0';
        Util::logDebug( 'Start service ' . $this->getName() . ': ' . $start . ' (status: ' . $this->status() . ')' );

        if ( $start != self::WIN32_NO_ERROR && $start != self::WIN32_ERROR_SERVICE_ALREADY_RUNNING ) {

            // Write error to log
            Util::logError('Failed to start service: ' . $this->getName() . ' with error code: ' . $start);

            if ( $this->getName() == BinApache::SERVICE_NAME ) {
                $cmdOutput = $bearsamppBins->getApache()->getCmdLineOutput( BinApache::CMD_SYNTAX_CHECK );
                if ( !$cmdOutput['syntaxOk'] ) {
                    file_put_contents(
                        $bearsamppBins->getApache()->getErrorLog(),
                        '[' . date( 'Y-m-d H:i:s', time() ) . '] [error] ' . $cmdOutput['content'] . PHP_EOL,
                        FILE_APPEND
                    );
                }
            }
            elseif ( $this->getName() == BinMysql::SERVICE_NAME ) {
                $cmdOutput = $bearsamppBins->getMysql()->getCmdLineOutput( BinMysql::CMD_SYNTAX_CHECK );
                if ( !$cmdOutput['syntaxOk'] ) {
                    file_put_contents(
                        $bearsamppBins->getMysql()->getErrorLog(),
                        '[' . date( 'Y-m-d H:i:s', time() ) . '] [error] ' . $cmdOutput['content'] . PHP_EOL,
                        FILE_APPEND
                    );
                }
            }
            elseif ( $this->getName() == BinMariadb::SERVICE_NAME ) {
                $cmdOutput = $bearsamppBins->getMariadb()->getCmdLineOutput( BinMariadb::CMD_SYNTAX_CHECK );
                if ( !$cmdOutput['syntaxOk'] ) {
                    file_put_contents(
                        $bearsamppBins->getMariadb()->getErrorLog(),
                        '[' . date( 'Y-m-d H:i:s', time() ) . '] [error] ' . $cmdOutput['content'] . PHP_EOL,
                        FILE_APPEND
                    );
                }
            }

            return false;
        }
        elseif ( !$this->isRunning() ) {
            $this->latestError = self::WIN32_NO_ERROR;
            Util::logError('Service ' . $this->getName() . ' is not running after start attempt.');
            $this->latestError = null;
            return false;
        }

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
        Util::logTrace('Attempting to stop service: ' . $this->getName());

        // Check if function exists before calling
        if (!function_exists('win32_stop_service')) {
            Util::logTrace('win32_stop_service function missing, using alternative approach');
            // If the function doesn't exist, we'll consider the service as stopped
            // This prevents freezing when the Win32 service functions are not available
            $this->latestStatus = self::WIN32_SERVICE_STOPPED;
            return true;
        }

        // Check if the service is already stopped before attempting to stop it
        $initialStatus = $this->status();
        if ($initialStatus == self::WIN32_SERVICE_STOPPED) {
            Util::logTrace('Service already stopped: ' . $this->getName());
            return true;
        }

        // If the service doesn't exist, consider it stopped
        if ($initialStatus == self::WIN32_SERVICE_NA) {
            Util::logTrace('Service does not exist, considering it stopped: ' . $this->getName());
            return true;
        }

        $result = $this->callWin32Service('win32_stop_service', $this->getName(), true);
        $stop = $result !== null ? dechex((int)$result) : '0';

        // Avoid calling status() directly after stop to prevent potential freezing
        // Instead, log the stop result without checking status
        $this->writeLog('Stop service ' . $this->getName() . ': ' . $stop);

        // If the stop operation returned success, consider it successful
        if ($stop == self::WIN32_NO_ERROR) {
            Util::logTrace('Service stop reported success with code: ' . $stop);

            // Set the status directly instead of calling status() again
            $this->latestStatus = self::WIN32_SERVICE_STOPPED;

            Util::logTrace('Service stopped successfully: ' . $this->getName());
            return true;
        } else {
            Util::logTrace('Failed to stop service: ' . $this->getName() . ' with error code: ' . $stop);

            // Only check if still running if stop failed with an unexpected error
            // This reduces the risk of freezing on status check
            if ($stop != self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST) {
                // Use a single status check with a short timeout
                $finalStatus = $this->status(false);
                if ($finalStatus != self::WIN32_SERVICE_STOPPED) {
                    Util::logTrace('Service not stopped after stop attempt: ' . $this->getName());
                    $this->latestError = self::WIN32_NO_ERROR;
                    return false;
                }
            }

            // If we get here, either the service doesn't exist or it's stopped
            $this->latestStatus = self::WIN32_SERVICE_STOPPED;
            return true;
        }
    }

    /**
     * Restarts the service by stopping and then starting it.
     *
     * @return bool True if the service was restarted successfully, false otherwise.
     */
    public function restart()
    {
        if ( $this->stop() ) {
            return $this->start();
        }

        return false;
    }

    /**
     * Retrieves information about the service.
     *
     * @return array The service information.
     */
    public function infos()
    {
        if ( $this->getNssm() instanceof Nssm ) {
            return $this->getNssm()->infos();
        }

        return Vbs::getServiceInfos( $this->getName() );
    }

    /**
     * Checks if the service is installed.
     *
     * @return bool True if the service is installed, false otherwise.
     */
    public function isInstalled()
    {
        // First check if the service is already known to not exist
        if (self::isServiceNonExistent($this->getName())) {
            Util::logTrace('Service already known to not exist in isInstalled: ' . $this->getName());
            return false;
        }

        $status = $this->status();
        $this->writeLog( 'isInstalled ' . $this->getName() . ': ' . ($status != self::WIN32_SERVICE_NA ? 'YES' : 'NO') . ' (status: ' . $status . ')' );

        return $status != self::WIN32_SERVICE_NA;
    }

    /**
     * Checks if the service is running.
     *
     * @return bool True if the service is running, false otherwise.
     */
    public function isRunning()
    {
        $status = $this->status();
        $this->writeLog( 'isRunning ' . $this->getName() . ': ' . ($status == self::WIN32_SERVICE_RUNNING ? 'YES' : 'NO') . ' (status: ' . $status . ')' );

        return $status == self::WIN32_SERVICE_RUNNING;
    }

    /**
     * Checks if the service is stopped.
     *
     * @return bool True if the service is stopped, false otherwise.
     */
    public function isStopped()
    {
        $status = $this->status();
        $this->writeLog( 'isStopped ' . $this->getName() . ': ' . ($status == self::WIN32_SERVICE_STOPPED ? 'YES' : 'NO') . ' (status: ' . $status . ')' );

        return $status == self::WIN32_SERVICE_STOPPED;
    }

    /**
     * Checks if the service is paused.
     *
     * @return bool True if the service is paused, false otherwise.
     */
    public function isPaused()
    {
        $status = $this->status();
        $this->writeLog( 'isPaused ' . $this->getName() . ': ' . ($status == self::WIN32_SERVICE_PAUSED ? 'YES' : 'NO') . ' (status: ' . $status . ')' );

        return $status == self::WIN32_SERVICE_PAUSED;
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
        return $status == self::WIN32_SERVICE_START_PENDING || $status == self::WIN32_SERVICE_STOP_PENDING
            || $status == self::WIN32_SERVICE_CONTINUE_PENDING || $status == self::WIN32_SERVICE_PAUSE_PENDING;
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
            return $bearsamppLang->getValue( Lang::ERROR ) . ' ' .
                $this->latestError . ' (' . hexdec( $this->latestError ) . ' : ' . $this->getWin32ErrorCodeDesc( $this->latestError ) . ')';
        }
        elseif ( $this->latestStatus != self::WIN32_SERVICE_NA ) {
            return $bearsamppLang->getValue( Lang::STATUS ) . ' ' .
                $this->latestStatus . ' (' . hexdec( $this->latestStatus ) . ' : ' . $this->getWin32ServiceStatusDesc( $this->latestStatus ) . ')';
        }

        return null;
    }
}
