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
            $result = call_user_func( $function, $param );
            if ( $checkError && Util::toHex( $result ) != self::WIN32_NO_ERROR ) {
                $this->latestError = Util::toHex( $result );
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
        // Special handling for Memcached service to prevent freezing
        if ($this->getName() == BinMemcached::SERVICE_NAME) {
            $this->writeLog('Using direct status check for Memcached service');
            
            // First check if the service exists using SC command
            $output = [];
            exec('sc query ' . $this->getName() . ' 2>nul', $output);
            
            $serviceExists = false;
            $serviceRunning = false;
            
            foreach ($output as $line) {
                if (strpos($line, 'DOES NOT EXIST') !== false) {
                    $this->latestStatus = self::WIN32_SERVICE_NA;
                    $this->writeLog('Memcached service does not exist');
                    return $this->latestStatus;
                }
                
                if (strpos($line, 'STATE') !== false && strpos($line, 'RUNNING') !== false) {
                    $serviceExists = true;
                    $serviceRunning = true;
                    $this->latestStatus = self::WIN32_SERVICE_RUNNING;
                    break;
                }
                
                if (strpos($line, 'STATE') !== false && strpos($line, 'STOPPED') !== false) {
                    $serviceExists = true;
                    $this->latestStatus = self::WIN32_SERVICE_STOPPED;
                    break;
                }
            }
            
            // If service exists but not determined from SC, use port check as fallback
            if ($serviceExists && !isset($this->latestStatus)) {
                global $bearsamppBins;
                if (method_exists($bearsamppBins, 'getMemcached')) {
                    $port = $bearsamppBins->getMemcached()->getPort();
                    if (Util::isPortInUse($port)) {
                        $this->latestStatus = self::WIN32_SERVICE_RUNNING;
                        $this->writeLog('Memcached service is running (port check)');
                    } else {
                        $this->latestStatus = self::WIN32_SERVICE_STOPPED;
                        $this->writeLog('Memcached service is stopped (port check)');
                    }
                } else {
                    $this->latestStatus = self::WIN32_SERVICE_STOPPED;
                    $this->writeLog('Memcached service status unknown, assuming stopped');
                }
            }
            
            // If service doesn't exist according to SC
            if (!$serviceExists) {
                $this->latestStatus = self::WIN32_SERVICE_NA;
                $this->writeLog('Memcached service not found');
            }
            
            return $this->latestStatus;
        }
        
        // Standard status check for other services
        usleep(self::SLEEP_TIME);

        $this->latestStatus = self::WIN32_SERVICE_NA;
        $maxtime = time() + self::PENDING_TIMEOUT;

        while ($this->latestStatus == self::WIN32_SERVICE_NA || $this->isPending($this->latestStatus)) {
            $this->latestStatus = $this->callWin32Service('win32_query_service_status', $this->getName());
            if (is_array($this->latestStatus) && isset($this->latestStatus['CurrentState'])) {
                $this->latestStatus = Util::toHex($this->latestStatus['CurrentState']);
            }
            elseif (is_numeric($this->latestStatus)) {
                $this->latestStatus = Util::toHex($this->latestStatus);
            }
            if ($timeout && $maxtime < time()) {
                break;
            }
        }

        if ($this->latestStatus == self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST) {
            $this->latestError = $this->latestStatus;
            $this->latestStatus = self::WIN32_SERVICE_NA;
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

        if ( $this->getName() == BinPostgresql::SERVICE_NAME ) {
            $bearsamppBins->getPostgresql()->rebuildConf();
            $bearsamppBins->getPostgresql()->initData();

            return Batch::installPostgresqlService();
        }
        if ( $this->getNssm() instanceof Nssm ) {
            $nssmEnvPath = Util::getAppBinsRegKey( false );
            $nssmEnvPath .= Util::getNssmEnvPaths();
            $nssmEnvPath .= '%SystemRoot%/system32;';
            $nssmEnvPath .= '%SystemRoot%;';
            $nssmEnvPath .= '%SystemRoot%/system32/Wbem;';
            $nssmEnvPath .= '%SystemRoot%/system32/WindowsPowerShell/v1.0';
            $this->getNssm()->setEnvironmentExtra( 'PATH=' . $nssmEnvPath );

            return $this->getNssm()->create();
        }

        $create = Util::toHex( $this->callWin32Service( 'win32_create_service', array(
            'service'       => $this->getName(),
            'display'       => $this->getDisplayName(),
            'description'   => $this->getDisplayName(),
            'path'          => $this->getBinPath(),
            'params'        => $this->getParams(),
            'start_type'    => $this->getStartType() != null ? $this->getStartType() : self::SERVICE_DEMAND_START,
            'error_control' => $this->getErrorControl() != null ? $this->getErrorControl() : self::SERVER_ERROR_NORMAL,
        ),                                         true ) );

        $this->writeLog( 'Create service: ' . $create . ' (status: ' . $this->status() . ')' );
        $this->writeLog( '-> service: ' . $this->getName() );
        $this->writeLog( '-> display: ' . $this->getDisplayName() );
        $this->writeLog( '-> description: ' . $this->getDisplayName() );
        $this->writeLog( '-> path: ' . $this->getBinPath() );
        $this->writeLog( '-> params: ' . $this->getParams() );
        $this->writeLog( '-> start_type: ' . ($this->getStartType() != null ? $this->getStartType() : self::SERVICE_DEMAND_START) );
        $this->writeLog( '-> service: ' . ($this->getErrorControl() != null ? $this->getErrorControl() : self::SERVER_ERROR_NORMAL) );

        if ( $create != self::WIN32_NO_ERROR ) {
            return false;
        }
        elseif ( !$this->isInstalled() ) {
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
        global $bearsamppCore;
        
        $this->writeLog('Attempting to delete service: ' . $this->getName());
        
        // Check if service exists
        if (!$this->isInstalled()) {
            $this->writeLog('Service does not exist, nothing to delete');
            return true;
        }

        // Special handling for PostgreSQL
        if ($this->getName() == BinPostgresql::SERVICE_NAME) {
            $this->writeLog('Using specialized method for PostgreSQL service');
            return Batch::uninstallPostgresqlService();
        }

        // Try to stop the service first
        if ($this->isRunning()) {
            $this->writeLog('Stopping service before deletion');
            $this->stop();
            
            // Give it a moment to stop
            usleep(self::SLEEP_TIME * 2);
        }

        // First attempt: Use win32_delete_service
        $delete = Util::toHex($this->callWin32Service('win32_delete_service', $this->getName(), true));
        $this->writeLog('Delete service ' . $this->getName() . ' (win32_delete_service): ' . $delete);
        
        // Check if first attempt was successful
        if (($delete == self::WIN32_NO_ERROR || $delete == self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST) && !$this->isInstalled()) {
            $this->writeLog('Service deleted successfully with win32_delete_service');
            return true;
        }
        
        // Second attempt: Use SC command
        $this->writeLog('First attempt failed, trying SC command');
        $deleteCmd = 'sc delete ' . $this->getName();
        $output = [];
        exec($deleteCmd, $output, $returnCode);
        
        $success = false;
        foreach ($output as $line) {
            $this->writeLog('SC output: ' . $line);
            if (strpos($line, 'SUCCESS') !== false) {
                $success = true;
                break;
            }
        }
        
        // Check if service is gone
        usleep(self::SLEEP_TIME * 2);
        if (!$this->isInstalled()) {
            $this->writeLog('Service deleted successfully with SC command');
            return true;
        }
        
        // Third attempt: Try NSSM as fallback
        if ($bearsamppCore && method_exists($bearsamppCore, 'getNssmExe')) {
            $this->writeLog('Second attempt failed, trying NSSM');
            $nssmPath = Util::formatWindowsPath($bearsamppCore->getNssmExe());
            if (file_exists($nssmPath)) {
                $nssmCmd = '"' . $nssmPath . '" remove ' . $this->getName() . ' confirm';
                Batch::execStandalone('removeService_' . $this->getName(), $nssmCmd, true, 10);
                
                // Verify service is gone
                usleep(self::SLEEP_TIME * 2);
                if (!$this->isInstalled()) {
                    $this->writeLog('Service deleted successfully with NSSM');
                    return true;
                }
            }
        }
        
        // Special handling for problematic services
        if ($this->getName() == BinMemcached::SERVICE_NAME) {
            $this->writeLog('Using aggressive approach for memcached');
            exec('taskkill /F /IM memcached.exe /T 2>nul');
        } elseif ($this->getName() == BinPostgresql::SERVICE_NAME) {
            $this->writeLog('Using aggressive approach for postgresql');
            exec('taskkill /F /IM postgres.exe /T 2>nul');
            exec('taskkill /F /IM pg_ctl.exe /T 2>nul');
        }
        
        // Last resort: Try registry cleanup
        $this->writeLog('Attempting registry cleanup as last resort');
        $regCmd = 'reg delete "HKLM\\SYSTEM\\CurrentControlSet\\Services\\' . $this->getName() . '" /f';
        exec($regCmd);
        
        // Final check
        usleep(self::SLEEP_TIME * 2);
        if (!$this->isInstalled()) {
            $this->writeLog('Service deleted successfully after aggressive cleanup');
            return true;
        }
        
        // If we get here, all attempts failed
        $this->latestError = self::WIN32_NO_ERROR;
        $this->writeLog('All deletion attempts failed for service: ' . $this->getName());
        return false;
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

        // Special initialization for different services
        if ($this->getName() == BinMysql::SERVICE_NAME) {
            $bearsamppBins->getMysql()->initData();
        }
        elseif ($this->getName() == BinMailpit::SERVICE_NAME) {
            $bearsamppBins->getMailpit()->rebuildConf();
        }
        elseif ($this->getName() == BinMemcached::SERVICE_NAME) {
            // Special handling for Memcached
            $this->writeLog('Using special handling for Memcached service start');

            // Make sure Memcached is properly configured
            $bearsamppBins->getMemcached()->rebuildConf();

            // Check if any memcached processes are already running
            $this->writeLog('Checking for existing Memcached processes');
            $output = [];
            exec('tasklist /FI "IMAGENAME eq memcached.exe" /FO CSV', $output);
            if (count($output) > 1) {
                $this->writeLog('Found existing Memcached processes, killing them');
                exec('taskkill /F /IM memcached.exe /T 2>nul');
                sleep(1); // Give it a moment to terminate
            }

            // Try standard service start first
            $start = Util::toHex($this->callWin32Service('win32_start_service', $this->getName(), true));
            $this->writeLog('Start service ' . $this->getName() . ': ' . $start . ' (status: ' . $this->status() . ')');

            // If standard start fails, try direct command
            if ($start != self::WIN32_NO_ERROR && $start != self::WIN32_ERROR_SERVICE_ALREADY_RUNNING) {
                if (file_exists($bearsamppBins->getMemcached()->getExe())) {
                    $this->writeLog('Starting Memcached using direct command');
                    $port = $bearsamppBins->getMemcached()->getPort();
                    $memory = $bearsamppBins->getMemcached()->getMemory();
                    $exe = $bearsamppBins->getMemcached()->getExe();

                    // Start memcached as a background process
                    $cmd = 'start /b "" "' . $exe . '" -m ' . $memory . ' -p ' . $port . ' -l 127.0.0.1';
                    $this->writeLog('Executing: ' . $cmd);
                    pclose(popen($cmd, 'r'));

                    // Wait a moment for it to start
                    sleep(2);

                    // Check if it's running
                    if (Util::isPortInUse($port)) {
                        $this->writeLog('Memcached started successfully on port ' . $port);
                        return true;
                    }
                }

                return false;
            }
        }
        elseif ($this->getName() == BinPostgresql::SERVICE_NAME) {
            $bearsamppBins->getPostgresql()->rebuildConf();
            $bearsamppBins->getPostgresql()->initData();
        }
        elseif ($this->getName() == BinXlight::SERVICE_NAME) {
            $bearsamppBins->getXlight()->rebuildConf();
        }

        // For services other than Memcached or if Memcached was started normally
        if ($this->getName() != BinMemcached::SERVICE_NAME) {
            // Standard service start
            $start = Util::toHex($this->callWin32Service('win32_start_service', $this->getName(), true));
            $this->writeLog('Start service ' . $this->getName() . ': ' . $start . ' (status: ' . $this->status() . ')');

            if ($start != self::WIN32_NO_ERROR && $start != self::WIN32_ERROR_SERVICE_ALREADY_RUNNING) {
                // Write error to log
                Util::logError('Failed to start service: ' . $this->getName() . ' with error code: ' . $start);

                if ($this->getName() == BinApache::SERVICE_NAME) {
                    $cmdOutput = $bearsamppBins->getApache()->getCmdLineOutput(BinApache::CMD_SYNTAX_CHECK);
                    if (!$cmdOutput['syntaxOk']) {
                        file_put_contents(
                            $bearsamppBins->getApache()->getErrorLog(),
                            '[' . date('Y-m-d H:i:s', time()) . '] [error] ' . $cmdOutput['content'] . PHP_EOL,
                            FILE_APPEND
                        );
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

                return false;
            }
            elseif (!$this->isRunning()) {
                $this->latestError = self::WIN32_NO_ERROR;
                Util::logError('Service ' . $this->getName() . ' is not running after start attempt.');
                $this->latestError = null;
                return false;
            }
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
        $stop = Util::toHex( $this->callWin32Service( 'win32_stop_service', $this->getName(), true ) );
        $this->writeLog( 'Stop service ' . $this->getName() . ': ' . $stop . ' (status: ' . $this->status() . ')' );

        if ( $stop != self::WIN32_NO_ERROR ) {
            return false;
        }
        elseif ( !$this->isStopped() ) {
            $this->latestError = self::WIN32_NO_ERROR;

            return false;
        }

        return true;
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
                $this->latestError . ' (' . Util::fromHex( $this->latestError ) . ' : ' . $this->getWin32ErrorCodeDesc( $this->latestError ) . ')';
        }
        elseif ( $this->latestStatus != self::WIN32_SERVICE_NA ) {
            return $bearsamppLang->getValue( Lang::STATUS ) . ' ' .
                $this->latestStatus . ' (' . Util::fromHex( $this->latestStatus ) . ' : ' . $this->getWin32ServiceStatusDesc( $this->latestStatus ) . ')';
        }

        return null;
    }
}
