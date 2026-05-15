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
 * Utility class providing a wide range of static methods for various purposes including:
 * - Input cleaning and sanitization have been moved to UtilInput. @see UtilInput
 * - String manipulation methods have been moved to UtilString. @see UtilString
 * - File and directory management functions for deleting, clearing, or finding files and directories.
 * - System utilities for handling registry operations, managing environment variables, and executing system commands.
 * - Network utilities to validate IPs, domains, and manage HTTP requests.
 * - Helper functions for encoding, decoding, and file operations.
 *
 * Path formatting (formatWindowsPath / formatUnixPath) has been moved to Path. @see Path
 * Logging is handled by the Log class. @see Log
 *
 * This class is designed to be used as a helper or utility class where methods are accessed statically.
 * This means you do not need to instantiate it to use the methods, but can simply call them using the Util::methodName() syntax.
 *
 * Usage Example:
 * ```
 * $cleanedData = UtilInput::cleanGetVar('data', 'text');
 * $isAvailable = Util::isValidIp('192.168.1.1');
 * ```
 *
 * Each method is self-contained and provides specific functionality, making this class a central point for
 * common utility operations needed across a PHP application, especially in environments like web servers or command-line interfaces.
 */
class Util
{

    /**
     * Recursively deletes files from a specified directory while excluding certain files.
     *
     * @param   string  $path     The path to the directory to clear.
     * @param   array   $exclude  An array of filenames to exclude from deletion.
     *
     * @return array Returns an array with the status of the operation and the number of files deleted.
     */
    public static function clearFolders($paths, $exclude = array())
    {
        $result = array();
        foreach ($paths as $path) {
            $result[$path] = self::clearFolder($path, $exclude);
        }

        return $result;
    }

    /**
     * Recursively clears all files and directories within a specified directory, excluding specified items.
     *
     * @param   string  $path     The path of the directory to clear.
     * @param   array   $exclude  An array of filenames to exclude from deletion.
     *
     * @return array|null Returns an array with the operation status and count of files deleted, or null if the directory cannot be opened.
     */
    public static function clearFolder($path, $exclude = array())
    {
        $result             = array();
        $result['return']   = true;
        $result['nb_files'] = 0;

        $handle = @opendir($path);
        if (!$handle) {
            return null;
        }

        while (false !== ($file = readdir($handle))) {
            if ($file == '.' || $file == '..' || in_array($file, $exclude)) {
                continue;
            }
            if (is_dir($path . '/' . $file)) {
                $r = self::clearFolder($path . '/' . $file);
                if (!$r) {
                    $result['return'] = false;

                    return $result;
                }
            } else {
                $r = @unlink($path . '/' . $file);
                if ($r) {
                    $result['nb_files']++;
                } else {
                    $result['return'] = false;

                    return $result;
                }
            }
        }

        closedir($handle);

        return $result;
    }

    /**
     * Recursively deletes a directory and all its contents.
     *
     * @param   string  $path  The path of the directory to delete.
     */
    public static function deleteFolder($path)
    {
        if (is_dir($path)) {
            if (substr($path, strlen($path) - 1, 1) != '/') {
                $path .= '/';
            }
            $files = glob($path . '*', GLOB_MARK);
            foreach ($files as $file) {
                if (is_dir($file)) {
                    self::deleteFolder($file);
                } else {
                    unlink($file);
                }
            }
            rmdir($path);
        }
    }


    /**
     * Validates an IP address.
     *
     * @param   string  $ip  The IP address to validate.
     *
     * @return bool Returns true if the IP address is valid, otherwise false.
     */
    public static function isValidIp($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    /**
     * Validates a port number.
     *
     * @param   int  $port  The port number to validate.
     *
     * @return bool Returns true if the port number is valid and within the range of 1 to 65535, otherwise false.
     */
    public static function isValidPort($port)
    {
        return is_numeric($port) && ($port > 0 && $port <= 65535);
    }

    /**
     * Checks if the current process is running with administrator/elevated privileges.
     * This is essential for operations that require admin rights, such as installing Windows services.
     *
     * @return bool True if running as administrator, false otherwise.
     */
    public static function isAdmin()
    {
        // Only applicable on Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            // On non-Windows systems, check if running as root
            if (function_exists('posix_geteuid')) {
                return posix_geteuid() === 0;
            }
            // If we can't determine on non-Windows, assume true to avoid blocking
            return true;
        }

        // Method 1: Try using shell_exec with 'net session' command
        // This command only succeeds when run with admin privileges
        $output = CommandRunner::shellExec('net session 2>&1');
        if ($output !== null) {
            // Check for access denied errors
            if (stripos($output, 'Access is denied') !== false ||
                stripos($output, 'System error 5') !== false ||
                stripos($output, 'Zugriff verweigert') !== false) { // German
                // Explicitly denied - not admin
                return false;
            }

            // If we got output without errors, we likely have admin rights
            if (stripos($output, 'There are no entries') !== false ||
                stripos($output, 'These workstations') !== false ||
                preg_match('/\\\\\\\\/', $output)) {
                return true;
            }
        }

        // Method 2: Check using whoami command (Windows Vista and later)
        $output = CommandRunner::shellExec('whoami /groups 2>&1');
        if ($output !== null && !empty($output)) {
            // Look for the Administrators group or High Mandatory Level
            if (stripos($output, 'S-1-16-12288') !== false || // High Mandatory Level
                stripos($output, 'S-1-5-32-544') !== false) {  // Administrators group
                return true;
            }

            // If we got output but no admin indicators, we're not admin
            if (stripos($output, 'S-1-16-8192') !== false) { // Medium Mandatory Level (not admin)
                return false;
            }
        }

        // Method 3: Try to write to a system directory
        // This is a fallback method that checks if we can write to Windows directory
        $testFile = getenv('SystemRoot') . '\\Temp\\bearsampp_admin_test_' . uniqid() . '.tmp';
        $result = @file_put_contents($testFile, 'test');
        if ($result !== false) {
            @unlink($testFile);
            return true;
        }

        // If all methods fail or indicate no admin, return false
        return false;
    }

    /**
     * Replaces a defined constant in a file with a new value.
     *
     * @param   string  $path   The file path where the constant is defined.
     * @param   string  $var    The name of the constant.
     * @param   mixed   $value  The new value for the constant.
     */
    public static function replaceDefine($path, $var, $value)
    {
        self::replaceInFile($path, array(
            '/^define\((.*?)' . $var . '(.*?),/' => 'define(\'' . $var . '\', ' . (is_int($value) ? $value : '\'' . $value . '\'') . ');'
        ));
    }

    /**
     * Performs replacements in a file based on a list of regular expression patterns.
     *
     * @param   string  $path         The path to the file where replacements are to be made.
     * @param   array   $replaceList  An associative array where keys are regex patterns and values are replacement strings.
     */
    public static function replaceInFile($path, $replaceList)
    {
        if (file_exists($path)) {
            $lines = file($path);
            $fp    = fopen($path, 'w');
            foreach ($lines as $nb => $line) {
                $replaceDone = false;
                foreach ($replaceList as $regex => $replace) {
                    if (preg_match($regex, $line, $matches)) {
                        $countParams = preg_match_all('/{{(\d+)}}/', $replace, $paramsMatches);
                        if ($countParams > 0 && $countParams <= count($matches)) {
                            foreach ($paramsMatches[1] as $paramsMatch) {
                                $replace = str_replace('{{' . $paramsMatch . '}}', $matches[$paramsMatch], $replace);
                            }
                        }
                        Log::trace('Replace in file ' . $path . ' :');
                        Log::trace('## line_num: ' . trim($nb));
                        Log::trace('## old: ' . trim($line));
                        Log::trace('## new: ' . trim($replace));
                        fwrite($fp, $replace . PHP_EOL);

                        $replaceDone = true;
                        break;
                    }
                }
                if (!$replaceDone) {
                    fwrite($fp, $line);
                }
            }
            fclose($fp);
        }
    }


    /**
     * Gets the current Unix timestamp with microseconds.
     *
     * @return float Returns the current Unix timestamp combined with microseconds.
     */
    public static function getMicrotime()
    {
        list($usec, $sec) = explode(' ', microtime());

        return ((float)$usec + (float)$sec);
    }


    /**
     * Checks if the application is set to launch at startup.
     *
     * @return bool True if the startup link exists, false otherwise.
     */
    public static function isLaunchStartup()
    {
        $lnk = Path::getStartupLnkPath();
        return $lnk ? file_exists($lnk) : false;
    }

    /**
     * Enables launching the application at startup by creating a shortcut in the startup folder.
     *
     * @return bool True on success, false on failure.
     */
    public static function enableLaunchStartup()
    {
        global $bearsamppRoot, $bearsamppCore;

        $shortcutPath = Path::getStartupLnkPath();
        if (!$shortcutPath) {
            return false;
        }

        $targetPath = Path::getExeFilePath();
        $workingDir = Path::getRootPath();
        $description = APP_TITLE . ' ' . $bearsamppCore->getAppVersion();
        $iconPath = Path::getIconsPath() . '/app.ico';

        return Win32Native::createShortcut($shortcutPath, $targetPath, $workingDir, $description, $iconPath);
    }

    /**
     * Disables launching the application at startup by removing the shortcut from the startup folder.
     *
     * @return bool True on success, false on failure.
     */
    public static function disableLaunchStartup()
    {
        $startupLnkPath = Path::getStartupLnkPath();

        // Check if file exists before attempting to delete
        if (file_exists($startupLnkPath)) {
            return @unlink($startupLnkPath);
        }

        // Return true if the file doesn't exist (already disabled)
        return true;
    }



    /**
     * Converts data between UTF-8 and Windows-1252 encodings.
     *
     * @param   string  $data      The data to convert.
     * @param   string  $direction The conversion direction: 'to_cp1252' or 'to_utf8'. Defaults to 'to_cp1252'.
     *
     * @return string The converted data.
     */
    public static function convertEncoding($data, $direction = 'to_cp1252')
    {
        if ($direction === 'to_utf8') {
            return self::cp1252ToUtf8($data);
        } else {
            return self::utf8ToCp1252($data);
        }
    }

    /**
     * Converts UTF-8 encoded data to Windows-1252 encoding.
     *
     * @param   string  $data  The UTF-8 encoded data.
     *
     * @return string Returns the data encoded in Windows-1252.
     */
    public static function utf8ToCp1252($data)
    {
        return iconv('UTF-8', 'WINDOWS-1252//IGNORE', $data);
    }

    /**
     * Converts Windows-1252 encoded data to UTF-8 encoding.
     *
     * @param   string  $data  The Windows-1252 encoded data.
     *
     * @return string Returns the data encoded in UTF-8.
     */
    public static function cp1252ToUtf8($data)
    {
        return iconv('WINDOWS-1252', 'UTF-8//IGNORE', $data);
    }

    /**
     * Initiates a loading process using external components.
     */
    public static function startLoading()
    {
        global $bearsamppCore, $bearsamppWinbinder;

        Log::trace('startLoading() called');
        Log::trace('PHP executable: ' . self::getPhpExe());
        Log::trace('Root file: ' . Core::isRoot_FILE);
        Log::trace('Action: ' . Action::LOADING);

        $command = Core::isRoot_FILE . ' ' . Action::LOADING;
        Log::trace('Executing command: ' . self::getPhpExe() . ' ' . $command);

        $result = $bearsamppWinbinder->exec(self::getPhpExe(), $command);
        Log::trace('exec() returned: ' . var_export($result, true));

        Log::trace('startLoading() completed');
    }

    /**
     * Stops a previously started loading process and cleans up related resources.
     */
    public static function stopLoading()
    {
        global $bearsamppCore;
        if (file_exists($bearsamppCore->getLoadingPid())) {
            $pids = file($bearsamppCore->getLoadingPid());
            foreach ($pids as $pid) {
                Win32Ps::kill($pid);
            }
            @unlink($bearsamppCore->getLoadingPid());
        }

        // Clean up status file
        self::clearLoadingText();
    }

    /**
     * Updates the loading screen text (if loading screen is active)
     * This allows dynamic updates to show which service is being processed
     *
     * @param string $text The text to display on the loading screen
     */
    public static function updateLoadingText($text)
    {
        global $bearsamppCore;

        $statusFile = Path::getTmpPath() . '/loading_status.txt';
        file_put_contents($statusFile, json_encode(['text' => $text]));
    }

    /**
     * Clears the loading status file
     */
    public static function clearLoadingText()
    {
        global $bearsamppCore;

        $statusFile = Path::getTmpPath() . '/loading_status.txt';
        if (file_exists($statusFile)) {
            @unlink($statusFile);
        }
    }



    /**
     * Converts a file size in bytes to a human-readable format.
     *
     * Uses PHP's native human_readable_size() when no unit is forced.
     * Falls back to manual conversion when a specific unit is requested.
     *
     * @param  int     $size  The file size in bytes.
     * @param  string  $unit  Optional forced unit ('GB', 'MB', 'KB', or '').
     *
     * @return string  The formatted file size.
     */
    public static function humanFileSize(int $size, string $unit = ''): string
    {
        // Forced unit mode
        if ($unit !== '') {
            return match ($unit) {
                'GB' => number_format($size / (1 << 30), 2) . 'GB',
                'MB' => number_format($size / (1 << 20), 2) . 'MB',
                'KB' => number_format($size / (1 << 10), 2) . 'KB',
                default => number_format($size) . ' bytes',
            };
        }

        // Native PHP 8.3+ auto-selection
        return human_readable_size($size, precision: 2);
    }

    /**
     * Checks if the operating system is 32-bit.
     *
     * @return bool True if the OS is 32-bit, false otherwise.
     */
    public static function is32BitsOs()
    {
        global $bearsamppRegistry;
        $processor = $bearsamppRegistry->getProcessorRegKey();

        return UtilString::contains($processor, 'x86');
    }




    /**
     * Checks if a specific port is in use.
     *
     * @param   int  $port  The port number to check
     *
     * @return mixed False if the port is not in use, otherwise returns the process using the port
     */
    public static function isPortInUse($port)
    {
        // Set localIP statically
        $localIP = '127.0.0.1';

        // Save current error reporting level
        $errorReporting = error_reporting();

        // Disable error reporting temporarily
        error_reporting(0);

        $connection = @fsockopen($localIP, $port);

        // Restore original error reporting level
        error_reporting($errorReporting);

        if (is_resource($connection)) {
            fclose($connection);
            $process = Batch::getProcessUsingPort($port);

            return $process != null ? $process : 'N/A';
        }

        return false;
    }

    /**
     * Validates a domain name based on specific criteria.
     *
     * @param   string  $domainName  The domain name to validate.
     *
     * @return bool Returns true if the domain name is valid, false otherwise.
     */
    public static function isValidDomainName($domainName)
    {
        return filter_var($domainName, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }

    /**
     * Attempts to install and start a service on a specific port, with optional syntax checking and user notifications.
     *
     * @param   object  $bin             An object containing the binary information and methods related to the service.
     * @param   int     $port            The port number on which the service should run.
     * @param   string  $syntaxCheckCmd  The command to execute for syntax checking of the service configuration.
     * @param   bool    $showWindow      Optional. Whether to show message boxes for information, warnings, and errors. Defaults to false.
     *
     * @return bool Returns true if the service is successfully installed and started, false otherwise.
     */
    public static function installService($bin, $port, $syntaxCheckCmd, $showWindow = false)
    {
        global $bearsamppLang, $bearsamppWinbinder;

        if (method_exists($bin, 'initData')) {
            $bin->initData();
        }

        $name     = $bin->getName();
        $service  = $bin->getService();
        $boxTitle = sprintf($bearsamppLang->getValue(Lang::INSTALL_SERVICE_TITLE), $name);

        $isPortInUse = self::isPortInUse($port);
        if ($isPortInUse === false) {
            if (!$service->isInstalled()) {
                $service->create();
                if ($service->start()) {
                    Log::info(sprintf('%s service successfully installed. (name: %s ; port: %s)', $name, $service->getName(), $port));
                    if ($showWindow) {
                        $bearsamppWinbinder->messageBoxInfo(
                            sprintf($bearsamppLang->getValue(Lang::SERVICE_INSTALLED), $name, $service->getName(), $port),
                            $boxTitle
                        );
                    }

                    return true;
                } else {
                    $serviceError    = sprintf($bearsamppLang->getValue(Lang::SERVICE_INSTALL_ERROR), $name);
                    $serviceErrorLog = sprintf('Error during the installation of %s service', $name);
                    if (!empty($syntaxCheckCmd)) {
                        $cmdSyntaxCheck = $bin->getCmdLineOutput($syntaxCheckCmd);
                        if (!$cmdSyntaxCheck['syntaxOk']) {
                            $serviceError    .= PHP_EOL . sprintf($bearsamppLang->getValue(Lang::STARTUP_SERVICE_SYNTAX_ERROR), $cmdSyntaxCheck['content']);
                            $serviceErrorLog .= sprintf(' (conf errors detected : %s)', $cmdSyntaxCheck['content']);
                        }
                    }
                    Log::error($serviceErrorLog);
                    if ($showWindow) {
                        $bearsamppWinbinder->messageBoxError($serviceError, $boxTitle);
                    }
                }
            } else {
                Log::warning(sprintf('%s service already installed', $name));
                if ($showWindow) {
                    $bearsamppWinbinder->messageBoxWarning(
                        sprintf($bearsamppLang->getValue(Lang::SERVICE_ALREADY_INSTALLED), $name),
                        $boxTitle
                    );
                }

                return true;
            }
        } elseif ($service->isRunning()) {
            Log::warning(sprintf('%s service already installed and running', $name));
            if ($showWindow) {
                $bearsamppWinbinder->messageBoxWarning(
                    sprintf($bearsamppLang->getValue(Lang::SERVICE_ALREADY_INSTALLED), $name),
                    $boxTitle
                );
            }

            return true;
        } else {
            Log::error(sprintf('Port %s is used by an other application : %s', $port, $isPortInUse));
            if ($showWindow) {
                $bearsamppWinbinder->messageBoxError(
                    sprintf($bearsamppLang->getValue(Lang::PORT_NOT_USED_BY), $port, $isPortInUse),
                    $boxTitle
                );
            }
        }

        return false;
    }

    /**
     * Removes a service if it is installed.
     *
     * @param   Win32Service  $service  The service object to be removed.
     * @param   string        $name     The name of the service.
     *
     * @return bool Returns true if the service is successfully removed, false otherwise.
     */
    public static function removeService($service, $name)
    {
        if (!($service instanceof Win32Service)) {
            Log::error('$service not an instance of Win32Service');

            return false;
        }

        if ($service->isInstalled()) {
            if ($service->delete()) {
                Log::info(sprintf('%s service successfully removed', $name));

                return true;
            } else {
                Log::error(sprintf('Error during the uninstallation of %s service', $name));

                return false;
            }
        } else {
            Log::warning(sprintf('%s service does not exist', $name));
        }

        return true;
    }

    /**
     * Attempts to start a service and performs a syntax check if required.
     *
     * @param   object  $bin             An object containing service details.
     * @param   string  $syntaxCheckCmd  Command to check syntax errors.
     * @param   bool    $showWindow      Whether to show error messages in a window.
     *
     * @return bool Returns true if the service starts successfully, false otherwise.
     */
    public static function startService($bin, $syntaxCheckCmd, $showWindow = false)
    {
        global $bearsamppLang, $bearsamppWinbinder;

        if (method_exists($bin, 'initData')) {
            $bin->initData();
        }

        $name     = $bin->getName();
        $service  = $bin->getService();
        $boxTitle = sprintf($bearsamppLang->getValue(Lang::START_SERVICE_TITLE), $name);

        if (!$service->start()) {
            $serviceError    = sprintf($bearsamppLang->getValue(Lang::START_SERVICE_ERROR), $name);
            $serviceErrorLog = sprintf('Error while starting the %s service', $name);
            if (!empty($syntaxCheckCmd)) {
                $cmdSyntaxCheck = $bin->getCmdLineOutput($syntaxCheckCmd);
                if (!$cmdSyntaxCheck['syntaxOk']) {
                    $serviceError    .= PHP_EOL . sprintf($bearsamppLang->getValue(Lang::STARTUP_SERVICE_SYNTAX_ERROR), $cmdSyntaxCheck['content']);
                    $serviceErrorLog .= sprintf(' (conf errors detected : %s)', $cmdSyntaxCheck['content']);
                }
            }
            Log::error($serviceErrorLog);
            if ($showWindow) {
                $bearsamppWinbinder->messageBoxError($serviceError, $boxTitle);
            }

            return false;
        }

        return true;
    }





    /**
     * Opens the given content in a temporary file using the editor configured in bearsampp.conf.
     *
     * @param   string  $caption  The caption/title for the temporary file.
     * @param   string  $content  The content to write to the temporary file.
     *
     * @return void
     */
    public static function openFileContent($caption, $content)
    {
        global $bearsamppCore, $bearsamppConfig;

        $tmpFile = Path::getTmpPath() . '/' . $caption . '.txt';
        file_put_contents($tmpFile, $content);

        // Open the file with the configured editor from bearsampp.conf
        $editor = $bearsamppConfig->getNotepad();

        if (empty($editor) || !file_exists($editor)) {
            $editor = 'notepad.exe';
        }

        $bearsamppCore->getWinbinder()->exec('"' . $editor . '"', '"' . $tmpFile . '"');
    }

    /**
     * Finds a file in a given path.
     *
     * @param   string  $startPath  The path where to start the search.
     * @param   string  $findFile   The name of the file to find.
     *
     * @return string|false Returns the full path to the file if found, false otherwise.
     */
    public static function findFile($startPath, $findFile)
    {
        $handle = @opendir($startPath);
        if (!$handle) {
            return false;
        }

        while (false !== ($file = readdir($handle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (is_dir($startPath . '/' . $file)) {
                $res = self::findFile($startPath . '/' . $file, $findFile);
                if ($res !== false) {
                    closedir($handle);

                    return $res;
                }
            } elseif ($file == $findFile) {
                closedir($handle);

                return Path::formatUnixPath($startPath . '/' . $file);
            }
        }

        closedir($handle);

        return false;
    }

    /**
     * Recursively searches for repositories starting from a given path up to a specified depth.
     *
     * @param   string  $initPath   The initial path from where the search begins.
     * @param   string  $startPath  The current path from where to search.
     * @param   string  $checkFile  The file name to check for in the directory to consider it a repository.
     * @param   int     $maxDepth   The maximum depth of directories to search into.
     *
     * @return array Returns an array of paths that contain the specified file.
     */
    public static function findRepos($initPath, $startPath, $checkFile, $maxDepth = 1)
    {
        $depth  = substr_count(str_replace($initPath, '', $startPath), '/');
        $result = array();

        $handle = @opendir($startPath);
        if (!$handle) {
            return $result;
        }

        while (false !== ($file = readdir($handle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (is_dir($startPath . '/' . $file) && ($initPath == $startPath || $depth <= $maxDepth)) {
                $tmpResults = self::findRepos($initPath, $startPath . '/' . $file, $checkFile, $maxDepth);
                foreach ($tmpResults as $tmpResult) {
                    $result[] = $tmpResult;
                }
            } elseif (is_file($startPath . '/' . $checkFile) && !in_array($startPath, $result)) {
                $result[] = Path::formatUnixPath($startPath);
            }
        }

        closedir($handle);

        return $result;
    }

    /**
     * Scans a directory for folders and returns their names.
     *
     * @param   string  $path  The path to the directory to scan.
     *
     * @return array Returns an array of version names found in the directory.
     */
    public static function getVersionList($path)
    {
        $result = array();

        if (is_dir($path)) {
            $handle = @opendir($path);
            if ($handle) {
                $prefix = basename($path);
                while (false !== ($file = readdir($handle))) {
                    if ($file != '.' && $file != '..' && is_dir($path . '/' . $file) && $file != 'current') {
                        $result[] = str_replace($prefix, '', $file);
                    }
                }
                closedir($handle);
            }
        }

        natcasesort($result);

        return $result;
    }

    /**
     * Gets the list of folders in the specified path.
     *
     * @param   string  $path  The directory path to scan for folders.
     *
     * @return array|false Returns a sorted array of folder names, or false if the directory cannot be opened.
     */
    public static function getFolderList($path)
    {
        $result = array();

        $handle = @opendir($path);
        if (!$handle) {
            return false;
        }

        while (false !== ($file = readdir($handle))) {
            $filePath = $path . '/' . $file;
            if ($file != '.' && $file != '..' && is_dir($filePath) && $file != 'current') {
                $result[] = $file;
            }
        }

        closedir($handle);
        natcasesort($result);

        return $result;
    }

    /**
     * Gets the list of files to scan for path updates.
     *
     * @param   array|null  $path          The paths to scan. If null, the default paths are used.
     * @param   bool        $useCache      Whether to use the cached list of files.
     * @param   bool        $forceRefresh  Whether to force a refresh of the file list.
     *
     * @return array The list of files to scan.
     */
    public static function getFilesToScan($path = null, $useCache = true, $forceRefresh = false)
    {
        // Generate cache key based on path parameter
        $cacheKey = md5(serialize($path));

        // Try to get from cache if enabled and not forcing refresh
        if ($useCache && !$forceRefresh) {
            $cachedResult = Cache::get($cacheKey);
            if ($cachedResult !== false) {
                Cache::recordHit();
                Log::debug('File scan cache HIT (saved expensive scan operation)');
                return $cachedResult;
            }
        }

        Cache::recordMiss();
        Log::debug('File scan cache MISS (performing full scan)');

        // Perform the actual scan
        $startTime = self::getMicrotime();
        $result      = array();
        $pathsToScan = !empty($path) ? $path : self::getPathsToScan();

        foreach ($pathsToScan as $pathToScan) {
            $pathStartTime = self::getMicrotime();
            $findFiles = self::findFiles($pathToScan['path'], $pathToScan['includes'], $pathToScan['recursive']);
            foreach ($findFiles as $findFile) {
                $result[] = $findFile;
            }
            Log::debug($pathToScan['path'] . ' scanned in ' . round(self::getMicrotime() - $pathStartTime, 3) . 's');
        }

        $totalTime = round(self::getMicrotime() - $startTime, 3);
        Log::info('Full file scan completed in ' . $totalTime . 's (' . count($result) . ' files found)');

        // Store in cache if enabled
        if ($useCache) {
            Cache::set($cacheKey, $result);
        }

        return $result;
    }

    /**
     * Retrieves a list of paths to scan for path changes.
     *
     * @return array The list of paths to scan.
     */
    public static function getPathsToScan()
    {
        global $bearsamppRoot, $bearsamppBins;
        $paths = array();

        // Alias
        $paths[] = array(
            'path'      => Path::getAliasPath(),
            'includes'  => array(''),
            'recursive' => false
        );

        // Vhosts
        $paths[] = array(
            'path'      => Path::getVhostsPath(),
            'includes'  => array(''),
            'recursive' => false
        );

        // OpenSSL
        $paths[] = array(
            'path'      => Path::getOpenSslPath(),
            'includes'  => array('openssl.cfg'),
            'recursive' => false
        );

        // Homepage
        $paths[] = array(
            'path'      => Path::getHomepagePath(),
            'includes'  => array('alias.conf'),
            'recursive' => false
        );

        // Apache
        $folderList = self::getFolderList($bearsamppBins->getApache()->getRootPath());
        foreach ($folderList as $folder) {
            $paths[] = array(
                'path'      => $bearsamppBins->getApache()->getRootPath() . '/' . $folder,
                'includes'  => array('.ini', '.conf'),
                'recursive' => true
            );
        }

        // PHP
        $folderList = self::getFolderList($bearsamppBins->getPhp()->getRootPath());
        foreach ($folderList as $folder) {
            $paths[] = array(
                'path'      => $bearsamppBins->getPhp()->getRootPath() . '/' . $folder,
                'includes'  => array('.ini', '.conf'),
                'recursive' => true
            );
        }

        // MySQL
        $folderList = self::getFolderList($bearsamppBins->getMysql()->getRootPath());
        foreach ($folderList as $folder) {
            $paths[] = array(
                'path'      => $bearsamppBins->getMysql()->getRootPath() . '/' . $folder,
                'includes'  => array('.ini', '.conf'),
                'recursive' => true
            );
        }

        // MariaDB
        $folderList = self::getFolderList($bearsamppBins->getMariadb()->getRootPath());
        foreach ($folderList as $folder) {
            $paths[] = array(
                'path'      => $bearsamppBins->getMariadb()->getRootPath() . '/' . $folder,
                'includes'  => array('.ini', '.conf'),
                'recursive' => true
            );
        }

        // PostgreSQL
        $folderList = self::getFolderList($bearsamppBins->getPostgresql()->getRootPath());
        foreach ($folderList as $folder) {
            $paths[] = array(
                'path'      => $bearsamppBins->getPostgresql()->getRootPath() . '/' . $folder,
                'includes'  => array('.ini', '.conf'),
                'recursive' => true
            );
        }

        // Mailpit
        $folderList = self::getFolderList($bearsamppBins->getMailpit()->getRootPath());
        foreach ($folderList as $folder) {
            $paths[] = array(
                'path'      => $bearsamppBins->getMailpit()->getRootPath() . '/' . $folder,
                'includes'  => array('.ini', '.conf'),
                'recursive' => true
            );
        }

        // Xlight
        $folderList = self::getFolderList($bearsamppBins->getXlight()->getRootPath());
        foreach ($folderList as $folder) {
            $paths[] = array(
                'path'      => $bearsamppBins->getXlight()->getRootPath() . '/' . $folder,
                'includes'  => array('.ini', '.conf'),
                'recursive' => true
            );
        }

        return $paths;
    }

    /**
     * Recursively finds files in a directory that match a set of inclusion patterns.
     *
     * @param   string  $startPath  The directory path to start the search from.
     * @param   array   $includes   An array of file patterns to include in the search. Patterns starting with '!' are excluded.
     * @param   bool    $recursive  Determines whether the search should be recursive.
     *
     * @return array An array of files that match the inclusion patterns.
     */
    public static function findFiles($startPath, $includes = array(''), $recursive = true)
    {
        $result = array();

        $handle = @opendir($startPath);
        if (!$handle) {
            return $result;
        }

        while (false !== ($file = readdir($handle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (is_dir($startPath . '/' . $file) && $recursive) {
                $tmpResults = self::findFiles($startPath . '/' . $file, $includes);
                foreach ($tmpResults as $tmpResult) {
                    $result[] = $tmpResult;
                }
            } elseif (is_file($startPath . '/' . $file)) {
                foreach ($includes as $include) {
                    if (UtilString::startWith($include, '!')) {
                        $include = ltrim($include, '!');
                        if (UtilString::startWith($file, '.') && !UtilString::endWith($file, $include)) {
                            $result[] = Path::formatUnixPath($startPath . '/' . $file);
                        } elseif ($file != $include) {
                            $result[] = Path::formatUnixPath($startPath . '/' . $file);
                        }
                    } elseif (UtilString::endWith($file, $include) || $file == $include || empty($include)) {
                        $result[] = Path::formatUnixPath($startPath . '/' . $file);
                    }
                }
            }
        }

        closedir($handle);

        return $result;
    }

    /**
     * Retrieves the path to the PHP executable.
     *
     * @param   bool  $aetrayPath  Whether to format the path for AeTrayMenu.
     *
     * @return string The path to the PHP executable.
     */
    public static function getPhpExe($aetrayPath = false)
    {
        return Path::getPhpPath( $aetrayPath ) . '/' . Core::PHP_EXE;
    }

    /**
     * Retrieves the path to the SetEnv executable.
     *
     * @param   bool  $aetrayPath  Whether to format the path for AeTrayMenu.
     *
     * @return string The path to the SetEnv executable.
     */
    public static function getSetEnvExe($aetrayPath = false)
    {
        return Path::getSetEnvPath( $aetrayPath ) . '/' . Core::SETENV_EXE;
    }

    /**
     * Retrieves the path to the NSSM executable.
     *
     * @param   bool  $aetrayPath  Whether to format the path for AeTrayMenu.
     *
     * @return string The path to the NSSM executable.
     */
    public static function getNssmExe($aetrayPath = false)
    {
        return Path::getNssmPath( $aetrayPath ) . '/' . Core::NSSM_EXE;
    }

    /**
     * Retrieves the path to the OpenSSL executable.
     *
     * @param   bool  $aetrayPath  Whether to format the path for AeTrayMenu.
     *
     * @return string The path to the OpenSSL executable.
     */
    public static function getOpenSslExe($aetrayPath = false)
    {
        return Path::getOpenSslPath( $aetrayPath ) . '/' . Core::OPENSSL_EXE;
    }

    /**
     * Retrieves the path to the OpenSSL configuration file.
     *
     * @param   bool  $aetrayPath  Whether to format the path for AeTrayMenu.
     *
     * @return string The path to the OpenSSL configuration file.
     */
    public static function getOpenSslConf($aetrayPath = false)
    {
        return Path::getOpenSslPath( $aetrayPath ) . '/' . Core::OPENSSL_CONF;
    }

    /**
     * Retrieves the path to the HostsEditor executable.
     *
     * @param   bool  $aetrayPath  Whether to format the path for AeTrayMenu.
     *
     * @return string The path to the HostsEditor executable.
     */
    public static function getHostsEditorExe($aetrayPath = false)
    {
        return Path::getHostsEditorPath( $aetrayPath ) . '/' . Core::HOSTSEDITOR_EXE;
    }

    /**
     * Retrieves the path to the LN executable.
     *
     * @param   bool  $aetrayPath  Whether to format the path for AeTrayMenu.
     *
     * @return string The path to the LN executable.
     */
    public static function getLnExe($aetrayPath = false)
    {
        return Path::getLnPath( $aetrayPath ) . '/' . Core::LN_EXE;
    }

    /**
     * Retrieves the path to the PWGen executable.
     *
     * @param   bool  $aetrayPath  Whether to format the path for AeTrayMenu.
     *
     * @return string The path to the PWGen executable.
     */
    public static function getPwgenExe($aetrayPath = false)
    {
        return Path::getPwgenPath( $aetrayPath ) . '/' . Core::PWGEN_EXE;
    }
}

