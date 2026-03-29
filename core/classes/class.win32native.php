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
}
