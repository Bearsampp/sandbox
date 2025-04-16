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
 * Class Batch
 *
 * This class provides various utility functions for managing processes, services, and environment variables
 * within the Bearsampp application. It includes methods for finding executables by process ID, checking which
 * process is using a specific port, exiting and restarting the application, managing services, and executing
 * batch scripts.
 *
 * Key functionalities include:
 * - Finding executables by process ID.
 * - Checking which process is using a specific port.
 * - Exiting and restarting the application.
 * - Managing services (installing, uninstalling, setting descriptions, etc.).
 * - Executing batch scripts with optional output capture and timeout.
 * - Refreshing environment variables.
 * - Creating and removing symbolic links.
 * - Retrieving operating system information.
 *
 * The class utilizes global variables to access application settings and paths, and logs operations for debugging purposes.
 */
class Batch
{
    const END_PROCESS_STR = 'FINISHED!';
    const CATCH_OUTPUT_FALSE = 'bearsamppCatchOutputFalse';

    /**
     * Constructor for the Batch class.
     */
    public function __construct()
    {
    }

    /**
     * Writes a log entry to the batch log file.
     *
     * @param string $log The log message to write.
     */
    private static function writeLog($log)
    {
        global $bearsamppRoot;
        Util::logDebug($log, $bearsamppRoot->getBatchLogFilePath());
    }

    /**
     * Finds the executable name by its process ID (PID).
     *
     * @param int $pid The process ID to search for.
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
     * Gets the process using a specific port.
     *
     * @param int $port The port number to check.
     * @return string|int|null The executable name and PID if found, the PID if executable not found, or null if no process is using the port.
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
     * Exits the application, optionally restarting it.
     *
     * @param bool $restart Whether to restart the application after exiting.
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
     * 
     * This method logs detailed information about the restart process
     * to help track and debug the restart flow.
     */
    public static function restartApp()
    {
        global $bearsamppRoot, $bearsamppCore;
        
        Util::logTrace('Batch::restartApp: [RESTART_FLOW] Starting app restart - ' . microtime(true));
        Util::logInfo('Restart App');
        
        // Create batch script content
        $content = 'PING 1.1.1.1 -n 1 -w 2000 > nul' . PHP_EOL;
        $content .= '"' . $bearsamppRoot->getExeFilePath() . '" -quit -id={bearsampp}' . PHP_EOL;
        $content .= '"' . $bearsamppCore->getPhpExe() . '" "' . Core::isRoot_FILE . '" "' . Action::RESTART . '"' . PHP_EOL;
        
        Util::logTrace('Batch::restartApp: [RESTART_FLOW] Prepared restart script content - ' . microtime(true));
        
        // Kill any running binaries before restart
        Util::logTrace('Batch::restartApp: [RESTART_FLOW] Killing running binaries - ' . microtime(true));
        Win32Ps::killBins();
        Util::logTrace('Batch::restartApp: [RESTART_FLOW] Binaries killed - ' . microtime(true));
        
        // Execute the restart script
        Util::logTrace('Batch::restartApp: [RESTART_FLOW] Executing restart script - ' . microtime(true));
        self::execStandalone('restartApp', $content);
        Util::logTrace('Batch::restartApp: [RESTART_FLOW] Restart script execution initiated - ' . microtime(true));
    }

    /**
     * Gets the version of PEAR installed.
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
     * Initializes MySQL using a specified path.
     *
     * @param string $path The path to the MySQL initialization script.
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
     * @return bool True if the service was installed successfully, false otherwise.
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
     * @return bool True if the service was uninstalled successfully, false otherwise.
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
     * Initializes PostgreSQL using a specified path.
     *
     * @param string $path The path to the PostgreSQL initialization script.
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
     * @param string $src The source path.
     * @param string $dest The destination path.
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
     * @param string $link The path to the symbolic link.
     * @return bool True if the symlink was removed successfully, false otherwise.
     */
    public static function removeSymlink($link)
    {
        if (!file_exists($link)) {
            self::writeLog('-> removeSymlink: Link does not exist: ' . $link);
            return true; // If the link doesn't exist, nothing to do
        }
        
        // Check if it's a directory symlink
        $isDirectory = is_dir($link);
        $formattedLink = Util::formatWindowsPath($link);
        
        try {
            // Use different commands based on whether it's a directory or file symlink
            if ($isDirectory) {
                // For directory symlinks
                self::exec('removeSymlink', 'rmdir /Q "' . $formattedLink . '"', true, false);
            } else {
                // For file symlinks
                self::exec('removeSymlink', 'del /F /Q "' . $formattedLink . '"', true, false);
            }
            
            // Check if removal was successful
            if (file_exists($link)) {
                self::writeLog('-> removeSymlink: Failed to remove symlink: ' . $link);
                return false;
            }
            
            self::writeLog('-> removeSymlink: Successfully removed symlink: ' . $link);
            return true;
        } catch (Exception $e) {
            self::writeLog('-> removeSymlink: Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets the operating system information.
     *
     * @return string The operating system information.
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
     * @param string $displayName The display name to set.
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
     * @param string $desc The description to set.
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
     * @param string $startType The start type to set (e.g., "auto", "demand").
     */
    public static function setServiceStartType($serviceName, $startType)
    {
        $cmd = 'sc config ' . $serviceName . ' start= ' . $startType;
        self::exec('setServiceStartType', $cmd, true, false);
    }

    /**
     * Executes a standalone batch script.
     *
     * @param string $basename The base name for the script and result files.
     * @param string $content The content of the batch script.
     * @param bool $silent Whether to execute the script silently.
     * @return array|false The result of the execution, or false on failure.
     */
    public static function execStandalone($basename, $content, $silent = true)
    {
        return self::exec($basename, $content, false, false, true, $silent);
    }

    /**
     * Executes a batch script.
     *
     * @param string $basename The base name for the script and result files.
     * @param string $content The content of the batch script.
     * @param int|bool $timeout The timeout for the script execution in seconds, or true for default timeout, or false for no timeout.
     * @param bool $catchOutput Whether to capture the output of the script.
     * @param bool $standalone Whether the script is standalone.
     * @param bool $silent Whether to execute the script silently.
     * @param bool $rebuild Whether to rebuild the result array.
     * @return array|false The result of the execution, or false on failure.
     */
    public static function exec($basename, $content, $timeout = true, $catchOutput = true, $standalone = false, $silent = true, $rebuild = true)
    {
        global $bearsamppConfig, $bearsamppWinbinder, $bearsamppCore;
        $result = false;
        $isRestartFlow = ($basename == 'setExec' || $basename == 'restartApp' || $basename == 'exitApp');

        if ($isRestartFlow) {
            Util::logTrace('Batch:exec: [RESTART_FLOW] Starting execution for ' . $basename . ' - ' . microtime(true));
        }

        $resultFile = self::getTmpFile('.tmp', $basename);
        $scriptPath = self::getTmpFile('.bat', $basename);
        $checkFile = self::getTmpFile('.tmp', $basename);

        if ($isRestartFlow) {
            Util::logTrace('Batch:exec: [RESTART_FLOW] Files prepared:');
            Util::logTrace('Batch:exec: [RESTART_FLOW] -> resultFile: ' . $resultFile);
            Util::logTrace('Batch:exec: [RESTART_FLOW] -> scriptPath: ' . $scriptPath);
            Util::logTrace('Batch:exec: [RESTART_FLOW] -> checkFile: ' . $checkFile);
        }

        // Redirect output
        if ($catchOutput) {
            $content .= '> "' . $resultFile . '"' . (!Util::endWith($content, '2') ? ' 2>&1' : '');
        }

        // Header
        $header = '@ECHO OFF' . PHP_EOL . PHP_EOL;

        // Footer
        $footer = PHP_EOL . (!$standalone ? PHP_EOL . 'ECHO ' . self::END_PROCESS_STR . ' > "' . $checkFile . '"' : '');

        // Process
        $fullScript = $header . $content . $footer;
        file_put_contents($scriptPath, $fullScript);
        
        if ($isRestartFlow) {
            Util::logTrace('Batch:exec: [RESTART_FLOW] Script content written to file - ' . microtime(true));
            Util::logTrace('Batch:exec: [RESTART_FLOW] Script content: ' . str_replace(PHP_EOL, ' \\\\ ', $fullScript));
        }

        // Execute the script
        if ($isRestartFlow) {
            Util::logTrace('Batch:exec: [RESTART_FLOW] Executing script via WinBinder - ' . microtime(true));
        }
        $bearsamppWinbinder->exec($scriptPath, null, $silent);
        
        if ($isRestartFlow) {
            Util::logTrace('Batch:exec: [RESTART_FLOW] WinBinder execution initiated - ' . microtime(true));
        }

        if (!$standalone) {
            $timeout = is_numeric($timeout) ? $timeout : ($timeout === true ? $bearsamppConfig->getScriptsTimeout() : false);
            $maxtime = time() + $timeout;
            $noTimeout = $timeout === false;
            
            if ($isRestartFlow) {
                Util::logTrace('Batch:exec: [RESTART_FLOW] Waiting for completion with ' . 
                    ($noTimeout ? 'no timeout' : 'timeout of ' . $timeout . ' seconds') . 
                    ' - ' . microtime(true));
            }
            
            $startTime = microtime(true);
            $checkCount = 0;
            
            while ($result === false || empty($result)) {
                $checkCount++;
                
                if (file_exists($checkFile)) {
                    $check = file($checkFile);
                    if (!empty($check) && trim($check[0]) == self::END_PROCESS_STR) {
                        if ($catchOutput && file_exists($resultFile)) {
                            $result = file($resultFile);
                            if ($isRestartFlow) {
                                Util::logTrace('Batch:exec: [RESTART_FLOW] Process completed successfully, result file read - ' . microtime(true));
                            }
                        } else {
                            $result = self::CATCH_OUTPUT_FALSE;
                            if ($isRestartFlow) {
                                Util::logTrace('Batch:exec: [RESTART_FLOW] Process completed but no output captured - ' . microtime(true));
                            }
                        }
                        break;
                    }
                }
                
                if ($maxtime < time() && !$noTimeout) {
                    if ($isRestartFlow) {
                        Util::logTrace('Batch:exec: [RESTART_FLOW] Process TIMED OUT after ' . 
                            round(microtime(true) - $startTime, 2) . ' seconds and ' . 
                            $checkCount . ' checks - ' . microtime(true));
                    }
                    break;
                }
                
                // Add a small sleep to prevent CPU hogging
                usleep(50000); // 50ms
                
                // Log progress for restart flow every 2 seconds
                if ($isRestartFlow && $checkCount % 40 === 0) {
                    $elapsedTime = round(microtime(true) - $startTime, 2);
                    Util::logTrace('Batch:exec: [RESTART_FLOW] Still waiting after ' . $elapsedTime . 
                        ' seconds, check count: ' . $checkCount . ' - ' . microtime(true));
                    
                    // Check if files exist
                    Util::logTrace('Batch:exec: [RESTART_FLOW] Check file exists: ' . 
                        (file_exists($checkFile) ? 'Yes' : 'No'));
                    Util::logTrace('Batch:exec: [RESTART_FLOW] Result file exists: ' . 
                        (file_exists($resultFile) ? 'Yes' : 'No'));
                }
            }
            
            if ($isRestartFlow) {
                $elapsedTime = round(microtime(true) - $startTime, 2);
                Util::logTrace('Batch:exec: [RESTART_FLOW] Wait loop completed after ' . 
                    $elapsedTime . ' seconds and ' . $checkCount . ' checks - ' . microtime(true));
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
            
            $resultSummary = substr(implode(' \\\\ ', $result), 0, 2048);
            self::writeLog('-> result: ' . $resultSummary);
            
            if ($isRestartFlow) {
                Util::logTrace('Batch:exec: [RESTART_FLOW] Result summary: ' . $resultSummary);
                
                // Log detailed results for restart flow
                if (count($result) > 0) {
                    Util::logTrace('Batch:exec: [RESTART_FLOW] Detailed results (' . count($result) . ' lines):');
                    foreach ($result as $index => $line) {
                        if ($index < 20) { // Limit to first 20 lines to avoid excessive logging
                            Util::logTrace('Batch:exec: [RESTART_FLOW] -> Line ' . $index . ': ' . $line);
                        }
                    }
                    if (count($result) > 20) {
                        Util::logTrace('Batch:exec: [RESTART_FLOW] -> ... and ' . (count($result) - 20) . ' more lines');
                    }
                }
            }
        } else {
            self::writeLog('-> result: N/A');
            
            if ($isRestartFlow) {
                Util::logTrace('Batch:exec: [RESTART_FLOW] No results obtained - ' . microtime(true));
                
                // Try to determine why no results were obtained
                if (!file_exists($checkFile)) {
                    Util::logTrace('Batch:exec: [RESTART_FLOW] Check file was never created');
                }
                if (!file_exists($resultFile) && $catchOutput) {
                    Util::logTrace('Batch:exec: [RESTART_FLOW] Result file was never created');
                }
                
                // Check temp directory permissions
                $tmpDir = $bearsamppCore->getTmpPath();
                if (!is_dir($tmpDir)) {
                    Util::logTrace('Batch:exec: [RESTART_FLOW] ERROR: Temp directory does not exist: ' . $tmpDir);
                } else if (!is_writable($tmpDir)) {
                    Util::logTrace('Batch:exec: [RESTART_FLOW] ERROR: Temp directory is not writable: ' . $tmpDir);
                }
            }
        }
        
        if ($isRestartFlow) {
            Util::logTrace('Batch:exec: [RESTART_FLOW] Execution completed for ' . $basename . ' - ' . microtime(true));
        }

        return $result;
    }

    /**
     * Gets a temporary file path with a specified extension and optional custom name.
     *
     * @param string $ext The file extension.
     * @param string|null $customName An optional custom name for the file.
     * @return string The temporary file path.
     */
    private static function getTmpFile($ext, $customName = null)
    {
        global $bearsamppCore;
        return Util::formatWindowsPath($bearsamppCore->getTmpPath() . '/' . (!empty($customName) ? $customName . '-' : '') . Util::random() . $ext);
    }
}
