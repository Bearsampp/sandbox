<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Handles batch operations for system-level commands and service management.
 *
 * This class provides functionality to execute system commands, manage services,
 * and perform operations related to processes and environment variables. It is
 * tailored for operations within a Windows environment using command line utilities
 * like `netstat`, `tasklist`, and Windows Service Controller (`sc`).
 */
class Batch
{
    const END_PROCESS_STR = 'FINISHED!';
    const CATCH_OUTPUT_FALSE = 'bearsamppCatchOutputFalse';

    /**
     * Constructs the Batch object.
     */
    public function __construct()
    {
    }

    /**
     * Writes a log entry with a specific message.
     *
     * @param string $log The log message to write.
     */
    private static function writeLog($log)
    {
        global $bearsamppRoot;
        Util::logDebug($log, $bearsamppRoot->getBatchLogFilePath());
    }

    /**
     * Finds the executable name associated with a given process ID.
     *
     * @param int $pid The process ID.
     * @return string|false The executable name if found, false otherwise.
     */
    public static function findExeByPid($pid)
    {
        $result = self::exec('findExeByPid', 'TASKLIST /FO CSV /NH /FI "PID eq ' . $pid . '"', 5);
        if ($result !== false) {
            $expResult = explode('","', $result[0]);
            if (is_array($expResult) && count($expResult) > 2 && isset($expResult[0]) && !empty($expResult[0])) {
                return substr($expResult[0], 1);
            }
        }

        return false;
    }

    /**
     * Identifies the process using a specific port.
     *
     * @param int $port The port number.
     * @return string|null The process details or null if no process is found.
     */
    public static function getProcessUsingPort($port)
    {
        $result = self::exec('getProcessUsingPort', 'NETSTAT -aon', 4);
        if ($result !== false) {
            foreach ($result as $row) {
                if (!Util::startWith($row, 'TCP')) {
                    continue;
                }
                $rowExp = explode(' ', preg_replace('/\s+/', ' ', $row));
                if (count($rowExp) == 5 && Util::endWith($rowExp[1], ':' . $port) && $rowExp[3] == 'LISTENING') {
                    $pid = intval($rowExp[4]);
                    $exe = self::findExeByPid($pid);
                    if ($exe !== false) {
                        return $exe . ' (' . $pid . ')';
                    }
                    return $pid;
                }
            }
        }

        return null;
    }

    /**
     * Exits the application and optionally restarts it.
     *
     * @param bool $restart Whether to restart the application after exit.
     */
    public static function exitApp($restart = false)
    {
        global $bearsamppRoot, $bearsamppCore;

        $content = 'PING 1.1.1.1 -n 1 -w 2000 > nul' . PHP_EOL;
        $content .= '"' . $bearsamppRoot->getExeFilePath() . '" -quit -id={bearsampp}' . PHP_EOL;
        if ($restart) {
            $basename = 'restartApp';
            Util::logInfo('Restart App');
            $content .= '"' . $bearsamppCore->getPhpExe() . '" "' . Core::isRoot_FILE . '" "' . Action::RESTART . '"' . PHP_EOL;
        } else {
            $basename = 'exitApp';
            Util::logInfo('Exit App');
        }

        Win32Ps::killBins();
        self::execStandalone($basename, $content);
    }

    /**
     * Restarts the application.
     */
    public static function restartApp()
    {
        self::exitApp(true);
    }

    /**
     * Retrieves the PEAR version from the system.
     *
     * @return string|null The PEAR version if found, null otherwise.
     */
    public static function getPearVersion()
    {
        global $bearsamppBins;

        $result = self::exec('getPearVersion', 'CMD /C "' . $bearsamppBins->getPhp()->getPearExe() . '" -V', 5);
        if (is_array($result)) {
            foreach ($result as $row) {
                if (Util::startWith($row, 'PEAR Version:')) {
                    $expResult = explode(' ', $row);
                    if (count($expResult) == 3) {
                        return trim($expResult[2]);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Refreshes the environment variables.
     */
    public static function refreshEnvVars()
    {
        global $bearsamppRoot, $bearsamppCore;
        self::execStandalone('refreshEnvVars', '"' . $bearsamppCore->getSetEnvExe() . '" -a ' . Registry::APP_PATH_REG_ENTRY . ' "' . Util::formatWindowsPath($bearsamppRoot->getRootPath()) . '"');
    }

    /**
     * Installs the Filezilla service.
     *
     * @return bool True if the service is successfully installed, false otherwise.
     */
    public static function installFilezillaService()
    {
        global $bearsamppBins;

        self::exec('installFilezillaService', '"' . $bearsamppBins->getFilezilla()->getExe() . '" /install', true, false);

        if (!$bearsamppBins->getFilezilla()->getService()->isInstalled()) {
            return false;
        }

        self::setServiceDescription(BinFilezilla::SERVICE_NAME, $bearsamppBins->getFilezilla()->getService()->getDisplayName());

        return true;
    }

    /**
     * Uninstalls the Filezilla service.
     *
     * @return bool True if the service is successfully uninstalled, false otherwise.
     */
    public static function uninstallFilezillaService()
    {
        global $bearsamppBins;

        self::exec('uninstallFilezillaService', '"' . $bearsamppBins->getFilezilla()->getExe() . '" /uninstall', true, false);
        return !$bearsamppBins->getFilezilla()->getService()->isInstalled();
    }

    /**
     * Initializes MySQL using a specified batch file.
     *
     * @param string $path The path to the batch file.
     */
    public static function initializeMysql($path)
    {
        if (!file_exists($path . '/init.bat')) {
            Util::logWarning($path . '/init.bat does not exist');
            return;
        }
        self::exec('initializeMysql', 'CMD /C "' . $path . '/init.bat"', 60);
    }
    /**
     * Installs the PostgreSQL service.
     *
     * @return bool Returns true if the service is successfully installed and configured, false otherwise.
     */
    public static function installPostgresqlService()
    {
        global $bearsamppBins;

        $cmd = '"' . Util::formatWindowsPath($bearsamppBins->getPostgresql()->getCtlExe()) . '" register -N "' . BinPostgresql::SERVICE_NAME . '"';
        $cmd .= ' -U "LocalSystem" -D "' . Util::formatWindowsPath($bearsamppBins->getPostgresql()->getSymlinkPath()) . '\\data"';
        $cmd .= ' -l "' . Util::formatWindowsPath($bearsamppBins->getPostgresql()->getErrorLog()) . '" -w';
        self::exec('installPostgresqlService', $cmd, true, false);

        if (!$bearsamppBins->getPostgresql()->getService()->isInstalled()) {
            return false;
        }

        self::setServiceDisplayName(BinPostgresql::SERVICE_NAME, $bearsamppBins->getPostgresql()->getService()->getDisplayName());
        self::setServiceDescription(BinPostgresql::SERVICE_NAME, $bearsamppBins->getPostgresql()->getService()->getDisplayName());
        self::setServiceStartType(BinPostgresql::SERVICE_NAME, "demand");

        return true;
    }

    /**
     * Uninstalls the PostgreSQL service.
     *
     * @return bool Returns true if the service is successfully uninstalled, false if it is still installed.
     */
    public static function uninstallPostgresqlService()
    {
        global $bearsamppBins;

        $cmd = '"' . Util::formatWindowsPath($bearsamppBins->getPostgresql()->getCtlExe()) . '" unregister -N "' . BinPostgresql::SERVICE_NAME . '"';
        $cmd .= ' -l "' . Util::formatWindowsPath($bearsamppBins->getPostgresql()->getErrorLog()) . '" -w';
        self::exec('uninstallPostgresqlService', $cmd, true, false);
        return !$bearsamppBins->getPostgresql()->getService()->isInstalled();
    }

    /**
     * Initializes PostgreSQL using a batch file.
     *
     * @param string $path The path where the init.bat file is located.
     */
    public static function initializePostgresql($path)
    {
        if (!file_exists($path . '/init.bat')) {
            Util::logWarning($path . '/init.bat does not exist');
            return;
        }
        self::exec('initializePostgresql', 'CMD /C "' . $path . '/init.bat"', 15);
    }

    /**
     * Creates a symbolic link.
     *
     * @param string $src The source path of the file or directory.
     * @param string $dest The destination path where the symlink will be created.
     */
    public static function createSymlink($src, $dest)
    {
        global $bearsamppCore;
        $src = Util::formatWindowsPath($src);
        $dest = Util::formatWindowsPath($dest);
        self::exec('createSymlink', '"' . $bearsamppCore->getLnExe() . '" --absolute --symbolic --traditional --1023safe "' . $src . '" ' . '"' . $dest . '"', true, false);
    }

    /**
     * Removes a symbolic link.
     *
     * @param string $link The path to the symbolic link to be removed.
     */
    public static function removeSymlink($link)
    {
        self::exec('removeSymlink', 'rmdir /Q "' . Util::formatWindowsPath($link) . '"', true, false);
    }

    /**
     * Retrieves the operating system information.
     *
     * @return string Returns the OS information if found, empty string otherwise.
     */
    public static function getOsInfo()
    {
        $result = self::exec('getOsInfo', 'ver', 5);
        if (is_array($result)) {
            foreach ($result as $row) {
                if (Util::startWith($row, 'Microsoft')) {
                    return trim($row);
                }
            }
        }
        return '';
    }

    /**
     * Sets the display name of a service.
     *
     * @param string $serviceName The name of the service.
     * @param string $displayName The display name to be set for the service.
     */
    public static function setServiceDisplayName($serviceName, $displayName)
    {
        $cmd = 'sc config ' . $serviceName . ' DisplayName= "' . $displayName . '"';
        self::exec('setServiceDisplayName', $cmd, true, false);
    }

    /**
     * Sets the description of a service.
     *
     * @param string $serviceName The name of the service.
     * @param string $desc The description to be set for the service.
     */
    public static function setServiceDescription($serviceName, $desc)
    {
        $cmd = 'sc description ' . $serviceName . ' "' . $desc . '"';
        self::exec('setServiceDescription', $cmd, true, false);
    }

    /**
     * Sets the start type of a service.
     *
     * @param string $serviceName The name of the service.
     * @param string $startType The start type to be set (e.g., 'auto', 'manual', 'disabled').
     */
    public static function setServiceStartType($serviceName, $startType)
    {
        $cmd = 'sc config ' . $serviceName . ' start= ' . $startType;
        self::exec('setServiceStartType', $cmd, true, false);
    }

    /**
     * Executes a standalone command or script without catching output and without a timeout.
     *
     * This method is a simplified wrapper around the `exec` method specifically tailored for
     * standalone execution where output is not captured and there is no execution timeout.
     *
     * @param string $basename The base name for temporary files, used to generate paths for execution scripts.
     * @param string $content The command or script content to be executed.
     * @param bool $silent Whether the execution should be silent, suppressing any command line output.
     * @return mixed Returns the execution result, which could be an array of output lines or a status indicator.
     */
    public static function execStandalone($basename, $content, $silent = true)
    {
        return self::exec($basename, $content, false, false, true, $silent);
    }

    /**
     * Executes a command or script with optional parameters.
     *
     * @param string $basename The base name for temporary files.
     * @param string $content The command or script content to execute.
     * @param mixed $timeout The timeout for execution in seconds or true for default timeout.
     * @param bool $catchOutput Whether to catch the output of the execution.
     * @param bool $standalone Whether the execution is standalone.
     * @param bool $silent Whether the execution should be silent.
     * @param bool $rebuild Whether to rebuild the output array.
     * @return mixed Returns the execution result, which could be an array of output lines or a status indicator.
     */
    public static function exec($basename, $content, $timeout = true, $catchOutput = true, $standalone = false, $silent = true, $rebuild = true)
    {
        global $bearsamppConfig, $bearsamppWinbinder;
        $result = false;

        $resultFile = self::getTmpFile('.tmp', $basename);
        $scriptPath = self::getTmpFile('.bat', $basename);
        $checkFile = self::getTmpFile('.tmp', $basename);

        // Redirect output
        if ($catchOutput) {
            $content .= '> "' . $resultFile . '"' . (!Util::endWith($content, '2') ? ' 2>&1' : '');
        }

        // Header
        $header = '@ECHO OFF' . PHP_EOL . PHP_EOL;

        // Footer
        $footer = PHP_EOL . (!$standalone ? PHP_EOL . 'ECHO ' . self::END_PROCESS_STR . ' > "' . $checkFile . '"' : '');

        // Process
        file_put_contents($scriptPath, $header . $content . $footer);
        $bearsamppWinbinder->exec($scriptPath, null, $silent);

        if (!$standalone) {
            $timeout = is_numeric($timeout) ? $timeout : ($timeout === true ? $bearsamppConfig->getScriptsTimeout() : false);
            $maxtime = time() + $timeout;
            $noTimeout = $timeout === false;
            while ($result === false || empty($result)) {
                if (file_exists($checkFile)) {
                    $check = file($checkFile);
                    if (!empty($check) && trim($check[0]) == self::END_PROCESS_STR) {
                        if ($catchOutput && file_exists($resultFile)) {
                            $result = file($resultFile);
                        } else {
                            $result = self::CATCH_OUTPUT_FALSE;
                        }
                    }
                }
                if ($maxtime < time() && !$noTimeout) {
                    break;
                }
            }
        }

        self::writeLog('Exec:');
        self::writeLog('-> basename: ' . $basename);
        self::writeLog('-> content: ' . str_replace(PHP_EOL, ' \\\\ ', $content));
        self::writeLog('-> checkFile: ' . $checkFile);
        self::writeLog('-> resultFile: ' . $resultFile);
        self::writeLog('-> scriptPath: ' . $scriptPath);

        if ($result !== false && !empty($result) && is_array($result)) {
            if ($rebuild) {
                $rebuildResult = array();
                foreach ($result as $row) {
                    $row = trim($row);
                    if (!empty($row)) {
                        $rebuildResult[] = $row;
                    }
                }
                $result = $rebuildResult;
            }
            self::writeLog('-> result: ' . substr(implode(' \\\\ ', $result), 0, 2048));
        } else {
            self::writeLog('-> result: N/A');
        }

        return $result;
    }

    /**
     * Generates a temporary file path with a specified extension and optional custom name.
     *
     * @param string $ext The file extension.
     * @param string|null $customName Optional custom name to prefix the file.
     * @return string Returns the formatted temporary file path.
     */
    private static function getTmpFile($ext, $customName = null)
    {
        global $bearsamppCore;
        return Util::formatWindowsPath($bearsamppCore->getTmpPath() . '/' . (!empty($customName) ? $customName . '-' : '') . Util::random() . $ext);
    }
}
