<?php declare(strict_types=1);
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
 * This class provides an interface to manage Windows services. It includes methods to create, delete, start, stop,
 * and query the status of services. It also handles logging and error reporting for service operations.
 */
class Win32Service
{
    // Win32Service Service Status Constants
    const WIN32_SERVICE_CONTINUE_PENDING = '5';
    const WIN32_SERVICE_PAUSE_PENDING      = '6';
    const WIN32_SERVICE_PAUSED             = '7';
    const WIN32_SERVICE_RUNNING            = '4';
    const WIN32_SERVICE_START_PENDING      = '2';
    const WIN32_SERVICE_STOP_PENDING       = '3';
    const WIN32_SERVICE_STOPPED            = '1';
    const WIN32_SERVICE_NA                 = '0';

    // Win32 Error Codes
    const WIN32_ERROR_ACCESS_DENIED              = '5';
    const WIN32_ERROR_CIRCULAR_DEPENDENCY        = '423';
    const WIN32_ERROR_DATABASE_DOES_NOT_EXIST     = '429';
    const WIN32_ERROR_DEPENDENT_SERVICES_RUNNING  = '41B';
    const WIN32_ERROR_DUPLICATE_SERVICE_NAME      = '436';
    const WIN32_ERROR_FAILED_SERVICE_CONTROLLER_CONNECT = '427';
    const WIN32_ERROR_INSUFFICIENT_BUFFER         = '7A';
    const WIN32_ERROR_INVALID_DATA                = 'D';
    const WIN32_ERROR_INVALID_HANDLE              = '6';
    const WIN32_ERROR_INVALID_LEVEL               = '7C';
    const WIN32_ERROR_INVALID_NAME                = '7B';
    const WIN32_ERROR_INVALID_PARAMETER           = '57';
    const WIN32_ERROR_INVALID_SERVICE_ACCOUNT     = '421';
    const WIN32_ERROR_INVALID_SERVICE_CONTROL     = '41C';
    const WIN32_ERROR_PATH_NOT_FOUND              = '3';
    const WIN32_ERROR_SERVICE_ALREADY_RUNNING     = '420';
    const WIN32_ERROR_SERVICE_CANNOT_ACCEPT_CTRL  = '425';
    const WIN32_ERROR_SERVICE_DATABASE_LOCKED     = '41F';
    const WIN32_ERROR_SERVICE_DEPENDENCY_DELETED   = '433';
    const WIN32_ERROR_SERVICE_DEPENDENCY_FAIL      = '42C';
    const WIN32_ERROR_SERVICE_DISABLED            = '422';
    const WIN32_ERROR_SERVICE_DOES_NOT_EXIST      = '424';
    const WIN32_ERROR_SERVICE_EXISTS              = '431';
    const WIN32_ERROR_SERVICE_LOGON_FAILED        = '42D';
    const WIN32_ERROR_SERVICE_MARKED_FOR_DELETE     = '430';
    const WIN32_ERROR_SERVICE_NO_THREAD           = '41E';
    const WIN32_ERROR_SERVICE_NOT_ACTIVE          = '426';
    const WIN32_ERROR_SERVICE_REQUEST_TIMEOUT     = '41D';
    const WIN32_ERROR_SHUTDOWN_IN_PROGRESS        = '45B';
    const WIN32_NO_ERROR                         = '0';

    const SERVER_ERROR_IGNORE  = '0';
    const SERVER_ERROR_NORMAL  = '1';

    const SERVICE_AUTO_START   = '2';
    const SERVICE_DEMAND_START = '3';
    const SERVICE_DISABLED     = '4';

    const PENDING_TIMEOUT      = 20;
    const SLEEP_TIME           = 500000;

    const VBS_NAME             = 'Name';
    const VBS_DISPLAY_NAME     = 'DisplayName';
    const VBS_DESCRIPTION      = 'Description';
    const VBS_PATH_NAME        = 'PathName';
    const VBS_STATE            = 'State';

    // Properties
    private string $name;
    private ?string $displayName = null;
    private ?string $binPath     = null;
    private ?string $params      = null;
    private ?string $startType   = null;
    private ?string $errorControl= null;
    private $nssm; // Instance of Nssm or null

    private ?string $latestStatus = null;
    private ?string $latestError  = null;

    /**
     * Constructor for the Win32Service class.
     *
     * @param string $name The name of the service.
     */
    public function __construct(string $name)
    {
        Util::logInitClass($this);
        $this->name = $name;
    }

    /**
     * Writes a log entry.
     *
     * @param string $log The log message.
     */
    private function writeLog(string $log): void
    {
        global $bearsamppRoot;
        Util::logDebug($log, $bearsamppRoot->getServicesLogFilePath());
    }

    /**
     * Returns an array of VBS keys used for service information.
     *
     * @return array The array of VBS keys.
     */
    public static function getVbsKeys(): array
    {
        return [
            self::VBS_NAME,
            self::VBS_DISPLAY_NAME,
            self::VBS_DESCRIPTION,
            self::VBS_PATH_NAME,
            self::VBS_STATE
        ];
    }

    /**
     * Calls a Win32 service function and returns the result.
     *
     * @param string $function   The Win32 service function to call.
     * @param mixed  $serviceName The name of the service or service parameters.
     * @param bool   $start      Whether to start the service.
     * @return string The result of the service call as a hexadecimal string.
     */
    public function callWin32Service(string $function, $serviceName, bool $start = false): string
    {
        if (!function_exists($function)) {
            error_log("Function $function does not exist");
            return '0';
        }

        $result = null;
        try {
            // Use call_user_func to call the service function
            $result = call_user_func($function, $serviceName, $start);
        } catch (Exception $e) {
            error_log("Exception in service call: " . $e->getMessage());
            return '0';
        }

        if ($result === null) {
            error_log("Service call returned null for function $function and service " . json_encode($serviceName));
            return '0';
        }

        if (is_array($result) && isset($result['CurrentState'])) {
            return dechex((int)$result['CurrentState']);
        } elseif (is_numeric($result)) {
            return dechex((int)$result);
        } else {
            return '0';
        }
    }

    /**
     * Queries the status of the service.
     *
     * @param bool $timeout Whether to use a timeout.
     * @return string The status of the service.
     */
    public function status(bool $timeout = true): string
    {
        usleep(self::SLEEP_TIME);
        $this->latestStatus = self::WIN32_SERVICE_NA;
        $maxtime = time() + self::PENDING_TIMEOUT;

        while ($this->latestStatus === self::WIN32_SERVICE_NA || $this->isPending($this->latestStatus)) {
            $this->latestStatus = $this->callWin32Service('win32_query_service_status', $this->getName());
            if (is_array($this->latestStatus) && isset($this->latestStatus['CurrentState'])) {
                $this->latestStatus = dechex((int)$this->latestStatus['CurrentState']);
            } elseif (is_numeric($this->latestStatus) &&
                dechex((int)$this->latestStatus) === self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST) {
                $this->latestStatus = self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST;
            } elseif ($this->latestStatus === self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST) {
                // Already a string; no conversion needed.
            }
            if ($timeout && $maxtime < time()) {
                break;
            }
        }

        if ($this->latestStatus === self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST) {
            $this->latestError  = $this->latestStatus;
            $this->latestStatus = self::WIN32_SERVICE_NA;
        }

        return $this->latestStatus ?? self::WIN32_SERVICE_NA;
    }

    /**
     * Creates the service.
     *
     * @return bool True if the service was created successfully, false otherwise.
     */
    public function create(): bool
    {
        global $bearsamppBins;

        if ($this->getName() === BinPostgresql::SERVICE_NAME) {
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

        $serviceParams = [
            'service'       => $this->getName(),
            'display'       => $this->getDisplayName(),
            'description'   => $this->getDisplayName(),
            'path'          => $this->getBinPath(),
            'params'        => $this->getParams(),
            'start_type'    => $this->getStartType() ?? self::SERVICE_DEMAND_START,
            'error_control' => $this->getErrorControl() ?? self::SERVER_ERROR_NORMAL,
        ];

        $create = dechex((int)$this->callWin32Service('win32_create_service', $serviceParams, true));
        $this->writeLog('Create service: ' . $create . ' (status: ' . $this->status() . ')');
        $this->writeLog('-> service: ' . $this->getName());
        $this->writeLog('-> display: ' . $this->getDisplayName());
        $this->writeLog('-> description: ' . $this->getDisplayName());
        $this->writeLog('-> path: ' . $this->getBinPath());
        $this->writeLog('-> params: ' . $this->getParams());
        $this->writeLog('-> start_type: ' . ($this->getStartType() ?? self::SERVICE_DEMAND_START));
        $this->writeLog('-> error_control: ' . ($this->getErrorControl() ?? self::SERVER_ERROR_NORMAL));

        if ($create !== self::WIN32_NO_ERROR) {
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
    public function delete(): bool
    {
        if (!$this->isInstalled()) {
            return true;
        }

        $this->stop();

        if ($this->getName() === BinPostgresql::SERVICE_NAME) {
            return Batch::uninstallPostgresqlService();
        }

        $delete = dechex((int)$this->callWin32Service('win32_delete_service', $this->getName(), true));
        $this->writeLog('Delete service ' . $this->getName() . ': ' . $delete . ' (status: ' . $this->status() . ')');

        if ($delete !== self::WIN32_NO_ERROR && $delete !== self::WIN32_ERROR_SERVICE_DOES_NOT_EXIST) {
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
    public function reset(): bool
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
    public function start(): bool
    {
        global $bearsamppBins;
        Util::logInfo('Attempting to start service: ' . $this->getName());

        if ($this->getName() === BinMysql::SERVICE_NAME) {
            $bearsamppBins->getMysql()->initData();
        } elseif ($this->getName() === BinMailpit::SERVICE_NAME) {
            $bearsamppBins->getMailpit()->rebuildConf();
        } elseif ($this->getName() === BinMemcached::SERVICE_NAME) {
            $bearsamppBins->getMemcached()->rebuildConf();
        } elseif ($this->getName() === BinPostgresql::SERVICE_NAME) {
            $bearsamppBins->getPostgresql()->rebuildConf();
            $bearsamppBins->getPostgresql()->initData();
        } elseif ($this->getName() === BinXlight::SERVICE_NAME) {
            $bearsamppBins->getXlight()->rebuildConf();
        }

        $start = dechex((int)$this->callWin32Service('win32_start_service', $this->getName(), true));
        Util::logDebug('Start service ' . $this->getName() . ': ' . $start . ' (status: ' . $this->status() . ')');

        if ($start !== self::WIN32_NO_ERROR && $start !== self::WIN32_ERROR_SERVICE_ALREADY_RUNNING) {
            Util::logError('Failed to start service: ' . $this->getName() . ' with error code: ' . $start);
            return false;
        } elseif (!$this->isRunning()) {
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
    public function stop(): bool
    {
        $stop = dechex((int)$this->callWin32Service('win32_stop_service', $this->getName(), true));
        $this->writeLog('Stop service ' . $this->getName() . ': ' . $stop . ' (status: ' . $this->status() . ')');

        if ($stop !== self::WIN32_NO_ERROR) {
            return false;
        } elseif (!$this->isStopped()) {
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
    public function restart(): bool
    {
        if ($this->stop()) {
            return $this->start();
        }
        return false;
    }

    /**
     * Retrieves information about the service.
     *
     * @return array The service information.
     */
    public function infos(): array
    {
        if ($this->getNssm() instanceof Nssm) {
            return $this->getNssm()->infos();
        }
        return Vbs::getServiceInfos($this->getName()) ?: [];
    }

    /**
     * Checks if the service is installed.
     *
     * @return bool True if the service is installed, false otherwise.
     */
    public function isInstalled(): bool
    {
        $status = $this->status();
        $this->writeLog('isInstalled ' . $this->getName() . ': ' . ($status !== self::WIN32_SERVICE_NA ? 'YES' : 'NO') . ' (status: ' . $status . ')');
        return $status !== self::WIN32_SERVICE_NA;
    }

    /**
     * Checks if the service is running.
     *
     * @return bool True if the service is running, false otherwise.
     */
    public function isRunning(): bool
    {
        $status = $this->status();
        $this->writeLog('isRunning ' . $this->getName() . ': ' . ($status === self::WIN32_SERVICE_RUNNING ? 'YES' : 'NO') . ' (status: ' . $status . ')');
        return $status === self::WIN32_SERVICE_RUNNING;
    }

    /**
     * Checks if the service is stopped.
     *
     * @return bool True if the service is stopped, false otherwise.
     */
    public function isStopped(): bool
    {
        $status = $this->status();
        $this->writeLog('isStopped ' . $this->getName() . ': ' . ($status === self::WIN32_SERVICE_STOPPED ? 'YES' : 'NO') . ' (status: ' . $status . ')');
        return $status === self::WIN32_SERVICE_STOPPED;
    }

    /**
     * Checks if the service is paused.
     *
     * @return bool True if the service is paused, false otherwise.
     */
    public function isPaused(): bool
    {
        $status = $this->status();
        $this->writeLog('isPaused ' . $this->getName() . ': ' . ($status === self::WIN32_SERVICE_PAUSED ? 'YES' : 'NO') . ' (status: ' . $status . ')');
        return $status === self::WIN32_SERVICE_PAUSED;
    }

    /**
     * Checks if the service is in a pending state.
     *
     * @param string $status The status to check.
     * @return bool True if the service is pending, false otherwise.
     */
    public function isPending(string $status): bool
    {
        return $status === self::WIN32_SERVICE_START_PENDING ||
               $status === self::WIN32_SERVICE_STOP_PENDING  ||
               $status === self::WIN32_SERVICE_CONTINUE_PENDING ||
               $status === self::WIN32_SERVICE_PAUSE_PENDING;
    }

    /**
     * Returns a description of the Win32 service status.
     *
     * @param string $status The status code.
     * @return string|null The status description.
     */
    private function getWin32ServiceStatusDesc(string $status): ?string
    {
        switch ($status) {
            case self::WIN32_SERVICE_CONTINUE_PENDING:
                return 'The service continue is pending.';
            case self::WIN32_SERVICE_PAUSE_PENDING:
                return 'The service pause is pending.';
            case self::WIN32_SERVICE_PAUSED:
                return 'The service is paused.';
            case self::WIN32_SERVICE_RUNNING:
                return 'The service is running.';
            case self::WIN32_SERVICE_START_PENDING:
                return 'The service is starting.';
            case self::WIN32_SERVICE_STOP_PENDING:
                return 'The service is stopping.';
            case self::WIN32_SERVICE_STOPPED:
                return 'The service is not running.';
            case self::WIN32_SERVICE_NA:
                return 'Cannot retrieve service status.';
            default:
                return null;
        }
    }

    /**
     * Returns a description of the Win32 error code.
     *
     * @param string $code The error code.
     * @return string|null The description of the error code.
     */
    private function getWin32ErrorCodeDesc(string $code): ?string
    {
        switch ($code) {
            case self::WIN32_ERROR_ACCESS_DENIED:
                return 'The handle to the SCM database does not have the appropriate access rights.';
            // Other cases can be added here.
            default:
                return null;
        }
    }

    /**
     * Gets the name of the service.
     *
     * @return string The service name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name of the service.
     *
     * @param string $name The name to set.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Gets the display name of the service.
     *
     * @return string|null The display name.
     */
    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    /**
     * Sets the display name of the service.
     *
     * @param string $displayName The display name.
     */
    public function setDisplayName(string $displayName): void
    {
        $this->displayName = $displayName;
    }

    /**
     * Gets the binary path of the service.
     *
     * @return string|null The binary path.
     */
    public function getBinPath(): ?string
    {
        return $this->binPath;
    }

    /**
     * Sets the binary path of the service.
     *
     * @param string $binPath The binary path.
     */
    public function setBinPath(string $binPath): void
    {
        $this->binPath = str_replace('"', '', Util::formatWindowsPath($binPath));
    }

    /**
     * Gets the parameters for the service.
     *
     * @return string|null The service parameters.
     */
    public function getParams(): ?string
    {
        return $this->params;
    }

    /**
     * Sets the parameters for the service.
     *
     * @param string $params The parameters to set.
     */
    public function setParams($params): void
    {
        if (is_string($params) || $params === null) {
            $this->params = $params !== null ? $params : '';
        } else {
            throw new TypeError('Params must be a string or null');
        }
    }

    /**
     * Gets the start type of the service.
     *
     * @return string|null The service start type.
     */
    public function getStartType(): ?string
    {
        return $this->startType;
    }

    /**
     * Sets the start type of the service.
     *
     * @param string $startType The start type.
     */
    public function setStartType(string $startType): void
    {
        $this->startType = $startType;
    }

    /**
     * Gets the error control setting of the service.
     *
     * @return string|null The error control setting.
     */
    public function getErrorControl(): ?string
    {
        return $this->errorControl;
    }

    /**
     * Sets the error control setting of the service.
     *
     * @param string $errorControl The error control setting.
     */
    public function setErrorControl(string $errorControl): void
    {
        $this->errorControl = $errorControl;
    }

    /**
     * Gets the NSSM instance associated with the service.
     *
     * @return mixed The NSSM instance.
     */
    public function getNssm()
    {
        return $this->nssm;
    }

    /**
     * Sets the NSSM instance associated with the service.
     *
     * @param mixed $nssm The NSSM instance.
     */
    public function setNssm($nssm): void
    {
        if ($nssm instanceof Nssm) {
            $this->setDisplayName($nssm->getDisplayName());
            $this->setBinPath($nssm->getBinPath());
            $params = $nssm->getParams();
            // Ensure params is a string
            $this->setParams($params !== null ? $params : '');
            $this->setStartType($nssm->getStart());
            $this->nssm = $nssm;
        }
    }

    /**
     * Gets the latest service status.
     *
     * @return string|null The latest status.
     */
    public function getLatestStatus(): ?string
    {
        return $this->latestStatus;
    }

    /**
     * Gets the latest error encountered by the service.
     *
     * @return string|null The latest error.
     */
    public function getLatestError(): ?string
    {
        return $this->latestError;
    }

    /**
     * Gets a detailed error message for the latest error encountered.
     *
     * @return string|null The detailed error message.
     */
    public function getError(): ?string
    {
        global $bearsamppLang;
        if ($this->latestError !== self::WIN32_NO_ERROR) {
            return $bearsamppLang->getValue(Lang::ERROR) . ' ' .
                $this->latestError . ' (' . hexdec($this->latestError) . ' : ' . $this->getWin32ErrorCodeDesc($this->latestError) . ')';
        } elseif ($this->latestStatus !== self::WIN32_SERVICE_NA) {
            return $bearsamppLang->getValue(Lang::STATUS) . ' ' .
                $this->latestStatus . ' (' . hexdec($this->latestStatus) . ' : ' . $this->getWin32ServiceStatusDesc($this->latestStatus) . ')';
        }
        return null;
    }
}
