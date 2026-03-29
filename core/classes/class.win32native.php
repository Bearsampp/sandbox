<?php
/*
 * Copyright (c) 2022-2025 Bearsampp
 * License: GNU General Public License version 3 or later; see LICENSE.txt
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class Win32Native
 *
 * This class provides native Windows operations using PHP COM extension.
 * Replaces VBScript operations with direct COM/WMI access from PHP.
 * Uses Windows Management Instrumentation (WMI) and WScript.Shell COM objects.
 */
class Win32Native
{
    /**
     * Gets a list of running processes using COM/WMI.
     * Replaces VBS WMI process query with direct PHP COM access.
     *
     * @param array $properties Optional array of properties to retrieve (e.g., ['Name', 'ProcessID', 'ExecutablePath'])
     * @return array Array of processes with requested information
     */
    public static function getProcessList($properties = [])
    {
        Util::logDebug('getProcessList: Listing processes (COM/WMI)');

        $startTime = microtime(true);

        try {
            // Create WMI connection
            $wmi = new COM("winmgmts://./root/cimv2");

            // Build WQL query
            if (empty($properties)) {
                $properties = ['Name', 'ProcessID', 'ExecutablePath'];
            }

            $selectClause = implode(', ', $properties);
            $query = "SELECT {$selectClause} FROM Win32_Process";

            // Execute query
            $processes = $wmi->ExecQuery($query);

            // Convert to array
            $result = [];
            foreach ($processes as $proc) {
                $process = [];
                foreach ($properties as $prop) {
                    // Handle property access
                    try {
                        $value = $proc->$prop;
                        $process[$prop] = $value ?? '';
                    } catch (Exception $e) {
                        $process[$prop] = '';
                    }
                }
                $result[] = $process;
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Util::logDebug('getProcessList: Found ' . count($result) . ' processes in ' . $duration . 'ms (COM/WMI)');

            return $result;

        } catch (Exception $e) {
            Util::logError('getProcessList: COM exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Kills a process by PID using COM/WMI.
     * Replaces VBS WMI process termination with direct PHP COM access.
     *
     * @param int $pid The process ID to kill
     * @return bool True on success, false on failure
     */
    public static function killProcess($pid)
    {
        // Validate PID
        if (!is_numeric($pid) || $pid <= 0) {
            Util::logError('killProcess: Invalid PID: ' . $pid);
            return false;
        }

        Util::logDebug('killProcess: Killing process PID ' . $pid . ' (COM/WMI)');

        $startTime = microtime(true);

        try {
            // Create WMI connection
            $wmi = new COM("winmgmts://./root/cimv2");

            // Query for specific process
            $query = "SELECT * FROM Win32_Process WHERE ProcessID = {$pid}";
            $processes = $wmi->ExecQuery($query);

            // Terminate the process
            $found = false;
            foreach ($processes as $proc) {
                $proc->Terminate();
                $found = true;
                break;
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($found) {
                Util::logDebug('killProcess: Successfully killed process ' . $pid . ' in ' . $duration . 'ms (COM/WMI)');
                return true;
            } else {
                Util::logDebug('killProcess: Process ' . $pid . ' not found');
                return false;
            }

        } catch (Exception $e) {
            Util::logError('killProcess: COM exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Checks if a process with the given PID exists.
     *
     * @param int $pid The process ID to check
     * @return bool True if process exists, false otherwise
     */
    public static function processExists($pid)
    {
        if (!is_numeric($pid) || $pid <= 0) {
            return false;
        }

        try {
            $wmi = new COM("winmgmts://./root/cimv2");
            $query = "SELECT ProcessID FROM Win32_Process WHERE ProcessID = {$pid}";
            $processes = $wmi->ExecQuery($query);

            foreach ($processes as $proc) {
                return true;
            }

            return false;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Gets information about a specific process by PID.
     *
     * @param int $pid The process ID
     * @param array $properties Properties to retrieve
     * @return array|false Process information or false if not found
     */
    public static function getProcessInfo($pid, $properties = [])
    {
        if (!is_numeric($pid) || $pid <= 0) {
            return false;
        }

        if (empty($properties)) {
            $properties = ['Name', 'ProcessID', 'ExecutablePath', 'CommandLine'];
        }

        try {
            $wmi = new COM("winmgmts://./root/cimv2");
            $selectClause = implode(', ', $properties);
            $query = "SELECT {$selectClause} FROM Win32_Process WHERE ProcessID = {$pid}";
            $processes = $wmi->ExecQuery($query);

            foreach ($processes as $proc) {
                $result = [];
                foreach ($properties as $prop) {
                    try {
                        $value = $proc->$prop;
                        $result[$prop] = $value ?? '';
                    } catch (Exception $e) {
                        $result[$prop] = '';
                    }
                }
                return $result;
            }

            return false;

        } catch (Exception $e) {
            Util::logError('getProcessInfo: COM exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Finds processes by name.
     *
     * @param string $name Process name (e.g., 'notepad.exe')
     * @param array $properties Properties to retrieve
     * @return array Array of matching processes
     */
    public static function findProcessesByName($name, $properties = [])
    {
        if (empty($name)) {
            return [];
        }

        if (empty($properties)) {
            $properties = ['Name', 'ProcessID', 'ExecutablePath'];
        }

        try {
            $wmi = new COM("winmgmts://./root/cimv2");
            $selectClause = implode(', ', $properties);
            $query = "SELECT {$selectClause} FROM Win32_Process WHERE Name = '{$name}'";
            $processes = $wmi->ExecQuery($query);

            $result = [];
            foreach ($processes as $proc) {
                $process = [];
                foreach ($properties as $prop) {
                    try {
                        $value = $proc->$prop;
                        $process[$prop] = $value ?? '';
                    } catch (Exception $e) {
                        $process[$prop] = '';
                    }
                }
                $result[] = $process;
            }

            return $result;

        } catch (Exception $e) {
            Util::logError('findProcessesByName: COM exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Gets the current PHP process ID.
     *
     * @return int Current process ID
     */
    public static function getCurrentPid()
    {
        return getmypid();
    }

    /**
     * Writes a log entry.
     *
     * @param string $log The log message.
     */
    private static function writeLog($log)
    {
        global $bearsamppRoot;
        Util::logDebug($log, $bearsamppRoot->getVbsLogFilePath());
    }

    // ========================================================================
    // PHASE 3: Registry Operations (COM/WScript.Shell)
    // ========================================================================

    /**
     * Maps registry hive abbreviations to full names for WScript.Shell.
     *
     * @param string $hive The registry hive (HKLM, HKCU, HKCR, HKU)
     * @return string The full hive name
     */
    private static function mapRegistryHive($hive)
    {
        $hiveMap = [
            'HKLM' => 'HKLM',
            'HKCU' => 'HKCU',
            'HKCR' => 'HKCR',
            'HKU' => 'HKU',
            'HKEY_LOCAL_MACHINE' => 'HKLM',
            'HKEY_CURRENT_USER' => 'HKCU',
            'HKEY_CLASSES_ROOT' => 'HKCR',
            'HKEY_USERS' => 'HKU',
        ];

        return isset($hiveMap[$hive]) ? $hiveMap[$hive] : $hive;
    }

    /**
     * Checks if a registry key or value exists using COM.
     * PHASE 3: Replaces VBS/reg.exe with direct COM access.
     *
     * @param string $hive The registry hive (HKLM, HKCU, etc.)
     * @param string $key The registry key path
     * @param string|null $value The value name (null to check key existence only)
     * @return bool True if exists, false otherwise
     */
    public static function registryExists($hive, $key, $value = null)
    {
        $hive = self::mapRegistryHive($hive);
        $regPath = $hive . '\\' . $key;

        if ($value !== null) {
            $regPath .= '\\' . $value;
        }

        Util::logDebug('registryExists: Checking ' . $regPath . ' (COM)');

        try {
            $shell = new COM("WScript.Shell");

            // Try to read the value/key
            $result = $shell->RegRead($regPath);

            Util::logDebug('registryExists: Found');
            return true;

        } catch (Exception $e) {
            // If we get an exception, the key/value doesn't exist
            Util::logDebug('registryExists: Not found');
            return false;
        }
    }

    /**
     * Gets a value from the Windows registry using COM.
     * PHASE 3: Replaces VBS/reg.exe with direct COM access.
     *
     * @param string $hive The registry hive (HKLM, HKCU, etc.)
     * @param string $key The registry key path
     * @param string $value The value name (empty string for default value)
     * @return mixed|null The registry value data, or null if not found
     */
    public static function registryGetValue($hive, $key, $value = '')
    {
        $hive = self::mapRegistryHive($hive);
        $regPath = $hive . '\\' . $key;

        if ($value !== '') {
            $regPath .= '\\' . $value;
        } else {
            // For default value, append backslash
            $regPath .= '\\';
        }

        Util::logDebug('registryGetValue: Reading ' . $regPath . ' (COM)');

        $startTime = microtime(true);

        try {
            $shell = new COM("WScript.Shell");
            $result = $shell->RegRead($regPath);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // Convert result to appropriate PHP type
            if (is_object($result)) {
                // COM objects need special handling
                $result = (string)$result;
            }

            Util::logDebug('registryGetValue: Found value in ' . $duration . 'ms (COM)');
            return $result;

        } catch (Exception $e) {
            Util::logDebug('registryGetValue: Value not found');
            return null;
        }
    }

    /**
     * Sets a value in the Windows registry using COM.
     * PHASE 3: Replaces VBS/reg.exe with direct COM access.
     *
     * @param string $hive The registry hive (HKLM, HKCU, etc.)
     * @param string $key The registry key path
     * @param string $value The value name
     * @param mixed $data The data to write
     * @param string $type The registry type (REG_SZ, REG_EXPAND_SZ, REG_DWORD, REG_BINARY)
     * @return bool True on success, false on failure
     */
    public static function registrySetValue($hive, $key, $value, $data, $type = 'REG_SZ')
    {
        $hive = self::mapRegistryHive($hive);
        $regPath = $hive . '\\' . $key . '\\' . $value;

        // Validate type
        $validTypes = ['REG_SZ', 'REG_EXPAND_SZ', 'REG_DWORD', 'REG_BINARY'];
        if (!in_array($type, $validTypes)) {
            Util::logError('registrySetValue: Invalid type: ' . $type);
            return false;
        }

        Util::logDebug('registrySetValue: Writing ' . $regPath . ' (' . $type . ') (COM)');

        $startTime = microtime(true);

        try {
            $shell = new COM("WScript.Shell");

            // Convert data based on type
            if ($type === 'REG_DWORD') {
                $data = (int)$data;
            }

            // Write the value
            $shell->RegWrite($regPath, $data, $type);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Util::logDebug('registrySetValue: Successfully wrote value in ' . $duration . 'ms (COM)');

            return true;

        } catch (Exception $e) {
            Util::logError('registrySetValue: COM exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a value from the Windows registry using COM.
     * PHASE 3: Replaces VBS/reg.exe with direct COM access.
     *
     * @param string $hive The registry hive (HKLM, HKCU, etc.)
     * @param string $key The registry key path
     * @param string $value The value name to delete
     * @return bool True on success, false on failure
     */
    public static function registryDeleteValue($hive, $key, $value)
    {
        $hive = self::mapRegistryHive($hive);
        $regPath = $hive . '\\' . $key . '\\' . $value;

        Util::logDebug('registryDeleteValue: Deleting ' . $regPath . ' (COM)');

        $startTime = microtime(true);

        try {
            $shell = new COM("WScript.Shell");

            // Delete the value
            $shell->RegDelete($regPath);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Util::logDebug('registryDeleteValue: Successfully deleted value in ' . $duration . 'ms (COM)');

            return true;

        } catch (Exception $e) {
            // If the value doesn't exist, that's OK
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'Unable to remove') !== false ||
                strpos($errorMsg, 'Invalid root') !== false) {
                Util::logDebug('registryDeleteValue: Value does not exist (already deleted)');
                return true;
            }

            Util::logError('registryDeleteValue: COM exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a registry key and all its subkeys using COM.
     * PHASE 3: Additional helper method for key deletion.
     *
     * @param string $hive The registry hive (HKLM, HKCU, etc.)
     * @param string $key The registry key path to delete
     * @return bool True on success, false on failure
     */
    public static function registryDeleteKey($hive, $key)
    {
        $hive = self::mapRegistryHive($hive);
        $regPath = $hive . '\\' . $key . '\\';

        Util::logDebug('registryDeleteKey: Deleting ' . $regPath . ' (COM)');

        try {
            $shell = new COM("WScript.Shell");

            // Delete the key (note the trailing backslash)
            $shell->RegDelete($regPath);

            Util::logDebug('registryDeleteKey: Successfully deleted key (COM)');
            return true;

        } catch (Exception $e) {
            // If the key doesn't exist, that's OK
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'Unable to remove') !== false ||
                strpos($errorMsg, 'Invalid root') !== false) {
                Util::logDebug('registryDeleteKey: Key does not exist (already deleted)');
                return true;
            }

            Util::logError('registryDeleteKey: COM exception: ' . $e->getMessage());
            return false;
        }
    }

    // ========================================================================
    // PHASE 5: Shortcuts & Special Paths (COM/WScript.Shell)
    // ========================================================================

    /**
     * Gets a Windows special folder path using COM.
     * PHASE 5: Replaces VBS with direct COM access.
     *
     * @param string $folderName The special folder name (Desktop, Startup, etc.)
     * @return string|false The folder path, or false on failure
     */
    public static function getSpecialFolderPath($folderName)
    {
        Util::logDebug('getSpecialFolderPath: Getting ' . $folderName . ' path (COM)');

        $startTime = microtime(true);

        try {
            $shell = new COM("WScript.Shell");

            // Get the special folder path
            $path = $shell->SpecialFolders($folderName);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($path && !empty($path)) {
                // Convert to Unix-style path
                $path = str_replace('\\', '/', $path);
                Util::logDebug('getSpecialFolderPath: Found ' . $folderName . ' in ' . $duration . 'ms (COM)');
                return $path;
            } else {
                Util::logDebug('getSpecialFolderPath: ' . $folderName . ' not found');
                return false;
            }

        } catch (Exception $e) {
            Util::logError('getSpecialFolderPath: COM exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Creates a Windows shortcut using COM.
     * PHASE 5: Replaces VBS with direct COM access.
     *
     * @param string $shortcutPath Full path where to save the shortcut (.lnk file)
     * @param string $targetPath Path to the target executable
     * @param string $workingDir Working directory for the shortcut
     * @param string $description Shortcut description
     * @param string $iconPath Path to icon file
     * @return bool True on success, false on failure
     */
    public static function createShortcut($shortcutPath, $targetPath, $workingDir = '', $description = '', $iconPath = '')
    {
        Util::logDebug('createShortcut: Creating shortcut at ' . $shortcutPath . ' (COM)');

        $startTime = microtime(true);

        try {
            $shell = new COM("WScript.Shell");

            // Create the shortcut object
            $shortcut = $shell->CreateShortcut($shortcutPath);

            // Set shortcut properties
            $shortcut->TargetPath = $targetPath;

            if (!empty($workingDir)) {
                $shortcut->WorkingDirectory = $workingDir;
            }

            if (!empty($description)) {
                $shortcut->Description = $description;
            }

            if (!empty($iconPath)) {
                $shortcut->IconLocation = $iconPath;
            }

            // Save the shortcut
            $shortcut->Save();

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Util::logDebug('createShortcut: Successfully created shortcut in ' . $duration . 'ms (COM)');

            return true;

        } catch (Exception $e) {
            Util::logError('createShortcut: COM exception: ' . $e->getMessage());
            return false;
        }
    }

    // ========================================================================
    // PHASE 4: Browser Detection (COM/Registry)
    // ========================================================================

    /**
     * Gets the default browser's executable path using COM.
     * PHASE 4: Replaces VBS with direct COM registry access.
     *
     * @return string|false The path to the default browser executable, or false on failure
     */
    public static function getDefaultBrowser()
    {
        Util::logDebug('getDefaultBrowser: Reading default browser (COM)');

        $startTime = microtime(true);

        // Try to read the default browser from registry
        $browserPath = self::registryGetValue('HKLM', 'SOFTWARE\\Classes\\http\\shell\\open\\command', '');

        if ($browserPath === null) {
            Util::logDebug('getDefaultBrowser: No default browser found');
            return false;
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        // Extract the executable path from the command
        // Format is usually: "C:\Program Files\Browser\browser.exe" -- "%1"
        if (preg_match('/"([^"]+)"/', $browserPath, $matches)) {
            $path = $matches[1];
        } else {
            // No quotes, take everything before the first space or use as-is
            $path = trim(explode(' ', $browserPath)[0]);
            $path = str_replace('"', '', $path);
        }

        Util::logDebug('getDefaultBrowser: Found browser in ' . $duration . 'ms (COM)');
        return $path;
    }

    /**
     * Gets a list of installed browsers using COM.
     * PHASE 4: Replaces VBS with direct COM registry access.
     *
     * @return array|false An array of browser executable paths, or false on failure
     */
    public static function getInstalledBrowsers()
    {
        Util::logDebug('getInstalledBrowsers: Enumerating installed browsers (COM)');

        $startTime = microtime(true);
        $browsers = [];

        // Registry keys to check for installed browsers
        $registryKeys = [
            ['hive' => 'HKLM', 'key' => 'SOFTWARE\\WOW6432Node\\Clients\\StartMenuInternet'],
            ['hive' => 'HKLM', 'key' => 'SOFTWARE\\Clients\\StartMenuInternet'],
            ['hive' => 'HKCU', 'key' => 'SOFTWARE\\Clients\\StartMenuInternet'],
        ];

        try {
            // Use WMI StdRegProv to enumerate subkeys
            $registry = new COM("winmgmts://./root/default:StdRegProv");

            foreach ($registryKeys as $regKey) {
                $hive = $regKey['hive'];
                $keyPath = $regKey['key'];

                // Map hive to numeric value for StdRegProv
                $hiveValue = ($hive === 'HKLM') ? 0x80000002 : 0x80000001; // HKLM or HKCU

                try {
                    // Enumerate subkeys using StdRegProv
                    $subKeys = null;
                    $returnValue = null;

                    // Call EnumKey method with output parameters
                    $registry->EnumKey($hiveValue, $keyPath, $subKeys);

                    // Check if we got subkeys
                    if ($subKeys !== null && is_array($subKeys)) {
                        Util::logDebug('getInstalledBrowsers: Found ' . count($subKeys) . ' browser keys in ' . $keyPath);

                        // Iterate through each browser
                        foreach ($subKeys as $browserKey) {
                            // Try to read the command path
                            // First try: shell\open\command with default value
                            $commandPath = self::registryGetValue(
                                $hive,
                                $keyPath . '\\' . $browserKey . '\\shell\\open\\command',
                                ''
                            );

                            if ($commandPath !== null && !empty($commandPath)) {
                                // Extract executable path from command
                                // Format is usually: "C:\Path\browser.exe" [arguments]
                                if (preg_match('/"([^"]+)"/', $commandPath, $matches)) {
                                    $path = $matches[1];
                                } else {
                                    // No quotes, take first part before space
                                    $parts = explode(' ', trim($commandPath));
                                    $path = str_replace('"', '', $parts[0]);
                                }

                                // Validate and add to list
                                if (!empty($path) && !in_array($path, $browsers)) {
                                    Util::logDebug('getInstalledBrowsers: Found browser: ' . $path);
                                    $browsers[] = $path;
                                }
                            } else {
                                Util::logDebug('getInstalledBrowsers: No command path for ' . $browserKey);
                            }
                        }
                    } else {
                        Util::logDebug('getInstalledBrowsers: No subkeys found in ' . $keyPath);
                    }
                } catch (Exception $e) {
                    // Key doesn't exist or can't be read, continue to next
                    Util::logDebug('getInstalledBrowsers: Could not enumerate ' . $keyPath . ': ' . $e->getMessage());
                    continue;
                }
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Util::logDebug('getInstalledBrowsers: Found ' . count($browsers) . ' browser(s) in ' . $duration . 'ms (COM)');

            return !empty($browsers) ? $browsers : false;

        } catch (Exception $e) {
            Util::logError('getInstalledBrowsers: COM exception: ' . $e->getMessage());
            return false;
        }
    }
}
