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
 * Class Vbs
 *
 * This class provides various utility functions for interacting with the Windows operating system
 * using VBScript. It includes methods for counting files and folders, retrieving default and installed browsers,
 * managing processes, and creating shortcuts.
 */
class Vbs
{
    const END_PROCESS_STR = 'FINISHED!';
    const STR_SEPARATOR = ' || ';

    const DESKTOP_PATH = 'objShell.SpecialFolders("Desktop")';
    const ALL_DESKTOP_PATH = 'objShell.SpecialFolders("AllUsersDesktop")';
    const STARTUP_PATH = 'objShell.SpecialFolders("Startup")';
    const ALL_STARTUP_PATH = 'objShell.SpecialFolders("AllUsersStartup")';

    public function __construct()
    {
    }

    /**
     * Writes a log entry to the VBS log file.
     *
     * @param   string  $log  The log message to write.
     */
    private static function writeLog($log)
    {
        global $bearsamppRoot;
        Util::logDebug( $log, $bearsamppRoot->getVbsLogFilePath() );
    }

    /**
     * Counts the number of files and folders in the specified path.
     * Now uses native PHP instead of VBScript.
     *
     * @param   string  $path  The path to count files and folders in.
     *
     * @return int|false The count of files and folders, or false on failure.
     */
    public static function countFilesFolders($path)
    {
        // Use native PHP implementation (faster than VBS and COM)
        Util::logDebug('countFilesFolders: Using Native PHP');

        return Win32Native::countFilesFolders($path);
    }

    /**
     * Retrieves the default browser's executable path.
     * Now uses COM registry access instead of VBScript.
     *
     * @return string|false The path to the default browser executable, or false on failure.
     */
    public static function getDefaultBrowser()
    {
        // Use COM implementation
        Util::logDebug('getDefaultBrowser: Using COM');

        return Win32Native::getDefaultBrowser();
    }

    /**
     * Retrieves a list of installed browsers' executable paths.
     * Now uses hybrid approach with COM registry access.
     *
     * @return array|false An array of paths to installed browser executables, or false on failure.
     */
    public static function getInstalledBrowsers()
    {
        // Use hybrid COM implementation
        Util::logDebug('getInstalledBrowsers: Using Hybrid COM');

        return Win32Native::getInstalledBrowsers();
    }

    /**
     * Retrieves a list of running processes with specified keys.
     * Now uses COM/WMI directly instead of VBScript.
     *
     * @param   array  $vbsKeys  The keys to retrieve for each process.
     *
     * @return array|false An array of process information, or false on failure.
     */
    public static function getListProcs($vbsKeys)
    {
        // Use COM/WMI implementation
        Util::logDebug('getListProcs: Using COM/WMI');

        // Get processes from COM
        $processes = Win32Native::getProcessList($vbsKeys);

        if (empty($processes)) {
            return false;
        }

        // Filter out processes without ExecutablePath (to match VBS behavior)
        $rebuildResult = [];
        foreach ($processes as $proc) {
            if (!empty($proc[Win32Ps::EXECUTABLE_PATH])) {
                $rebuildResult[] = $proc;
            }
        }

        return !empty($rebuildResult) ? $rebuildResult : false;
    }

    /**
     * Terminates a process by its PID.
     * Now uses COM/WMI directly instead of VBScript.
     *
     * @param   int  $pid  The process ID to terminate.
     *
     * @return bool True on success, false on failure.
     */
    public static function killProc($pid)
    {
        // Use COM/WMI implementation
        Util::logDebug('killProc: Using COM/WMI');

        return Win32Native::killProcess($pid);
    }

    /**
     * Retrieves a special folder path.
     * Now uses COM instead of VBScript.
     *
     * @param   string  $path  The VBScript path constant for the special folder.
     *
     * @return string|null The path to the special folder, or null on failure.
     */
    private static function getSpecialPath($path)
    {
        // Use COM implementation
        Util::logDebug('getSpecialPath: Using COM');

        // Extract folder name from VBScript constant
        // Format: objShell.SpecialFolders("Desktop")
        if (preg_match('/"([^"]+)"/', $path, $matches)) {
            $folderName = $matches[1];
            $result = Win32Native::getSpecialFolderPath($folderName);
            return $result !== false ? $result : null;
        }

        return null;
    }

    /**
     * Retrieves the startup path, optionally appending a file name.
     *
     * @param   string|null  $file  The file name to append to the startup path.
     *
     * @return string The startup path.
     */
    public static function getStartupPath($file = null)
    {
        return self::getSpecialPath( self::STARTUP_PATH ) . ($file != null ? '/' . $file : '');
    }

    /**
     * Creates a shortcut to the Bearsampp executable.
     * Now uses COM instead of VBScript.
     *
     * @param   string  $savePath  The path to save the shortcut.
     *
     * @return bool True on success, false on failure.
     */
    public static function createShortcut($savePath)
    {
        global $bearsamppRoot, $bearsamppCore;

        // Use COM implementation
        Util::logDebug('createShortcut: Using COM');

        $targetPath = $bearsamppRoot->getExeFilePath();
        $workingDir = $bearsamppRoot->getRootPath();
        $description = APP_TITLE . ' ' . $bearsamppCore->getAppVersion();
        $iconPath = $bearsamppCore->getIconsPath() . '/app.ico';

        return Win32Native::createShortcut($savePath, $targetPath, $workingDir, $description, $iconPath);
    }

    /**
     * Retrieves information about a Windows service.
     * Now uses COM/WMI instead of VBScript.
     *
     * @param   string  $serviceName  The name of the service to retrieve information about.
     *
     * @return array|false An array of service information, or false on failure.
     */
    public static function getServiceInfos($serviceName)
    {
        // Use COM/WMI implementation
        Util::logDebug('getServiceInfos: Using COM/WMI');

        // Get the VBS keys that are expected
        $vbsKeys = Win32Service::getVbsKeys();

        // Get service info from COM
        $serviceInfo = Win32Native::getServiceInfo($serviceName, $vbsKeys);

        return $serviceInfo;
    }

    /**
     * Generates a temporary file path with a given extension and optional custom name.
     *
     * @param   string       $ext         The file extension for the temporary file.
     * @param   string|null  $customName  An optional custom name to include in the file name.
     *
     * @return string The formatted path to the temporary file.
     */
    public static function getTmpFile($ext, $customName = null)
    {
        global $bearsamppCore;

        return Util::formatWindowsPath( $bearsamppCore->getTmpPath() . '/' . (!empty( $customName ) ? $customName . '-' : '') . Util::random() . $ext );
    }

    /**
     * Retrieves the path for a result file based on a given basename.
     *
     * @param   string  $basename  The base name to use for the result file.
     *
     * @return string The path to the result file.
     */
    public static function getResultFile($basename)
    {
        return self::getTmpFile( '.vbs', $basename );
    }

    /**
     * Executes a VBScript file and retrieves the result.
     *
     * @param   string    $basename    The base name for the script and result files.
     * @param   string    $resultFile  The path to the result file.
     * @param   string    $content     The VBScript content to execute.
     * @param   int|bool  $timeout     The timeout duration in seconds, or true for default timeout, or false for no timeout.
     *
     * @return array|false The result of the script execution as an array of lines, or false on failure.
     */
    public static function exec($basename, $resultFile, $content, $timeout = true)
    {
        global $bearsamppConfig, $bearsamppWinbinder;
        $result = false;

        $scriptPath       = self::getTmpFile( '.vbs', $basename );
        $checkFile        = self::getTmpFile( '.tmp', $basename );
        $errFile          = self::getTmpFile( '.tmp', $basename );
        $randomVarName    = Util::random( 15, false );
        $randomObjErrFile = Util::random( 15, false );
        $randomObjFile    = Util::random( 15, false );
        $randomObjFso     = Util::random( 15, false );

        // Add a timeout to the VBScript itself
        $timeoutSeconds = 10; // 10 seconds timeout for the VBScript

        // Header with timeout
        $header = 'On Error Resume Next' . PHP_EOL .
            'Dim ' . $randomVarName . ', ' . $randomObjFso . ', ' . $randomObjErrFile . ', ' . $randomObjFile . PHP_EOL .
            'Set ' . $randomObjFso . ' = CreateObject("scripting.filesystemobject")' . PHP_EOL .
            'Set ' . $randomObjErrFile . ' = ' . $randomObjFso . '.CreateTextFile("' . $errFile . '", True)' . PHP_EOL .
            'Set ' . $randomObjFile . ' = ' . $randomObjFso . '.CreateTextFile("' . $checkFile . '", True)' . PHP_EOL .
            // Add timeout mechanism to VBScript
            'startTime = Timer' . PHP_EOL .
            'timeoutSeconds = ' . $timeoutSeconds . PHP_EOL . PHP_EOL;

        // Footer with timeout check
        $footer = PHP_EOL . PHP_EOL .
            // Add timeout check before ending
            'If Timer - startTime > timeoutSeconds Then' . PHP_EOL .
            $randomObjErrFile . '.Write "VBScript execution timed out after " & timeoutSeconds & " seconds"' . PHP_EOL .
            'End If' . PHP_EOL .
            'If Err.Number <> 0 Then' . PHP_EOL .
            $randomObjErrFile . '.Write Err.Description' . PHP_EOL .
            'End If' . PHP_EOL .
            $randomObjFile . '.Write "' . self::END_PROCESS_STR . '"' . PHP_EOL .
            $randomObjFile . '.Close' . PHP_EOL .
            $randomObjErrFile . '.Close' . PHP_EOL;

        // Process
        file_put_contents( $scriptPath, $header . $content . $footer );

        // Use set_time_limit to prevent PHP script timeout
        $originalTimeout = ini_get('max_execution_time');
        set_time_limit(30); // 30 seconds timeout for PHP

        Util::logTrace("Starting VBS execution for: " . $basename);
        $startTime = microtime(true);

        try {
            $bearsamppWinbinder->exec( 'wscript.exe', '"' . $scriptPath . '"' );

            $timeout   = is_numeric( $timeout ) ? $timeout : ($timeout === true ? $bearsamppConfig->getScriptsTimeout() : false);
            // Use a shorter timeout for VBS execution
            $timeout = min($timeout, 15); // Maximum 15 seconds
            $maxtime   = time() + $timeout;
            $noTimeout = $timeout === false;

            // Add a microtime-based timeout as well
            $microTimeStart = microtime(true);
            $microTimeMax = 15; // 15 seconds maximum

            $loopCount = 0;
            $maxLoops = 30; // Maximum number of attempts

            while ( ($result === false || empty( $result )) && $loopCount < $maxLoops ) {
                $loopCount++;

                if ( file_exists( $checkFile ) ) {
                    $check = file( $checkFile );
                    if ( !empty( $check ) && trim( $check[0] ) == self::END_PROCESS_STR ) {
                        $result = file( $resultFile );
                        Util::logTrace("VBS execution completed successfully after " . $loopCount . " attempts");
                        break;
                    }
                }

                // Check both timeouts
                if (($maxtime < time() && !$noTimeout) || (microtime(true) - $microTimeStart > $microTimeMax)) {
                    Util::logTrace("VBS execution timed out after " . round(microtime(true) - $startTime, 2) . " seconds");
                    break;
                }

                // Sleep a short time to prevent CPU hogging
                usleep(100000); // 100ms
            }

            if ($loopCount >= $maxLoops) {
                Util::logTrace("VBS execution reached maximum loop count (" . $maxLoops . ")");
            }
        } catch (\Exception $e) {
            Util::logTrace("Exception during VBS execution: " . $e->getMessage());
        } catch (\Throwable $e) {
            Util::logTrace("Throwable during VBS execution: " . $e->getMessage());
        } finally {
            // Reset the timeout
            set_time_limit($originalTimeout);
        }

        $executionTime = round(microtime(true) - $startTime, 2);
        Util::logTrace("VBS execution for " . $basename . " took " . $executionTime . " seconds");

        $err = file_get_contents( $errFile );
        if ( !empty( $err ) ) {
            Util::logError( 'VBS error on ' . $basename . ': ' . $err );
        }

        self::writeLog( 'Exec ' . $basename . ':' );
        self::writeLog( '-> content: ' . str_replace( PHP_EOL, ' \\\\ ', $content ) );
        self::writeLog( '-> errFile: ' . $errFile );
        self::writeLog( '-> checkFile: ' . $checkFile );
        self::writeLog( '-> resultFile: ' . $resultFile );
        self::writeLog( '-> scriptPath: ' . $scriptPath );

        if ( $result !== false && !empty( $result ) ) {
            $rebuildResult = array();
            foreach ( $result as $row ) {
                $row = trim( $row );
                if ( !empty( $row ) ) {
                    $rebuildResult[] = $row;
                }
            }
            $result = $rebuildResult;
            self::writeLog( '-> result: ' . substr( implode( ' \\\\ ', $result ), 0, 2048 ) );
        }
        else {
            self::writeLog( '-> result: N/A' );
        }

        return $result;
    }
}
