<?php

class Registry
{
    // Registry constants
    const HKEY_CLASSES_ROOT = 'HKCR';
    const HKEY_CURRENT_USER = 'HKCU';
    const HKEY_LOCAL_MACHINE = 'HKLM';
    const HKEY_USERS = 'HKU';
    const HKEY_CURRENT_CONFIG = 'HKCC';

    // Registry environment keys
    const ENV_KEY = 'SYSTEM\\CurrentControlSet\\Control\\Session Manager\\Environment';
    const PROCESSOR_REG_SUBKEY = 'HARDWARE\\DESCRIPTION\\System\\CentralProcessor\\0';
    const PROCESSOR_REG_ENTRY = 'Identifier';
    const SYSPATH_REG_ENTRY = 'Path';
    const APP_PATH_REG_ENTRY = 'BEARSAMPP_PATH';
    const APP_BINS_REG_ENTRY = 'BEARSAMPP_BINS';

    // Registry error codes
    const REG_NO_ERROR = 0;
    const REG_ERROR = 1;

    // Registry value types
    const REG_SZ = 'REG_SZ';
    const REG_EXPAND_SZ = 'REG_EXPAND_SZ';
    const REG_BINARY = 'REG_BINARY';
    const REG_DWORD = 'REG_DWORD';
    const REG_MULTI_SZ = 'REG_MULTI_SZ';

    // Timeout for registry operations (in seconds)
    const REGISTRY_TIMEOUT = 10;

    /**
     * Constructor
     */
    public function __construct()
    {
        Util::logInitClass($this);
    }

    /**
     * Writes a log entry.
     *
     * @param   string  $log  The log message.
     */
    private function writeLog($log)
    {
        global $bearsamppRoot;
        Util::logDebug($log, $bearsamppRoot->getRegistryLogFilePath());
    }

    /**
     * Checks if a registry key exists.
     *
     * @param   string  $hKey       The registry hive.
     * @param   string  $subKey     The registry subkey.
     * @param   string  $valueName  The registry value name.
     *
     * @return bool True if the key exists, false otherwise.
     */
    public function keyExists($hKey, $subKey, $valueName)
    {
        $this->writeLog('keyExists: ' . $hKey . '\\' . $subKey . '\\' . $valueName);

        // Use direct PowerShell command for faster and more reliable registry check
        $ps_command = "powershell -NoProfile -ExecutionPolicy Bypass -Command \"";
        $ps_command .= 'try { ';
        $ps_command .= "if (Test-Path 'Registry::" . $hKey . "\\" . $subKey . "') { ";
        $ps_command .= "if (Get-ItemProperty -Path 'Registry::" . $hKey . "\\" . $subKey . "' -Name '" . $valueName . "' -ErrorAction Stop) { ";
        $ps_command .= "Write-Output 'EXISTS' } else { Write-Output 'NOT_EXISTS' }";
        $ps_command .= "} else { Write-Output 'NOT_EXISTS' }";
        $ps_command .= "} catch { Write-Output 'NOT_EXISTS' }\"";

        // Execute the command with a timeout
        $descriptorspec = array(
            0 => array('pipe', 'r'),  // stdin
            1 => array('pipe', 'w'),  // stdout
            2 => array('pipe', 'w')   // stderr
        );

        $process = proc_open($ps_command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            // Set non-blocking mode on the stdout pipe
            stream_set_blocking($pipes[1], 0);

            // Set a timeout
            $startTime = time();
            $output    = '';

            // Read from the pipe with timeout
            while (time() - $startTime < self::REGISTRY_TIMEOUT) {
                $read   = array($pipes[1]);
                $write  = null;
                $except = null;

                // Wait for data with a short timeout
                if (stream_select($read, $write, $except, 1)) {
                    $output .= stream_get_contents($pipes[1]);

                    // If we have a complete response, break
                    if (strpos($output, 'EXISTS') !== false || strpos($output, 'NOT_EXISTS') !== false) {
                        break;
                    }
                }
            }

            // Close pipes and process
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            // Terminate the process if it's still running
            $status = proc_get_status($process);
            if ($status['running']) {
                proc_terminate($process);
            }

            proc_close($process);

            // Check the result
            $exists = (strpos($output, 'EXISTS') !== false);
            $this->writeLog('keyExists result: ' . ($exists ? 'true' : 'false'));

            return $exists;
        }

        // Fallback to VBS if PowerShell fails
        $this->writeLog('Falling back to VBS for keyExists');
        $resultFile = Vbs::getTmpFile();
        $content    = 'On Error Resume Next' . PHP_EOL;
        $content    .= 'Dim WshShell, value' . PHP_EOL;
        $content    .= 'Set WshShell = CreateObject("WScript.Shell")' . PHP_EOL;
        $content    .= 'value = WshShell.RegRead("' . $hKey . '\\' . $subKey . '\\' . $valueName . '")' . PHP_EOL;
        $content    .= 'If Err.Number = 0 Then' . PHP_EOL;
        $content    .= '    WScript.Echo "EXISTS"' . PHP_EOL;
        $content    .= 'Else' . PHP_EOL;
        $content    .= '    WScript.Echo "NOT_EXISTS"' . PHP_EOL;
        $content    .= 'End If' . PHP_EOL;

        $result = Vbs::exec('keyExists', $resultFile, $content, 5);
        $exists = !empty($result) && trim($result[0]) == 'EXISTS';

        $this->writeLog('keyExists result (VBS): ' . ($exists ? 'true' : 'false'));

        return $exists;
    }

    /**
     * Gets a registry value.
     *
     * @param   string  $hKey       The registry hive.
     * @param   string  $subKey     The registry subkey.
     * @param   string  $valueName  The registry value name.
     *
     * @return mixed The registry value or false on error.
     */
    public function getValue($hKey, $subKey, $valueName)
    {
        $this->writeLog('getValue: ' . $hKey . '\\' . $subKey . '\\' . $valueName);

        // Use PowerShell for faster and more reliable registry access
        $ps_command = "powershell -NoProfile -ExecutionPolicy Bypass -Command \"";
        $ps_command .= 'try { ';
        $ps_command .= "if (Test-Path 'Registry::" . $hKey . "\\" . $subKey . "') { ";
        $ps_command .= "Get-ItemProperty -Path 'Registry::" . $hKey . "\\" . $subKey . "' -Name '" . $valueName . "' -ErrorAction Stop | ";
        $ps_command .= "Select-Object -ExpandProperty '" . $valueName . "' ";
        $ps_command .= "} else { Write-Output 'NOT_EXISTS' }";
        $ps_command .= "} catch { Write-Output 'ERROR: ' + $_.Exception.Message }\"";

        // Execute the command with a timeout
        $descriptorspec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );

        $process = proc_open($ps_command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            // Set non-blocking mode on the stdout pipe
            stream_set_blocking($pipes[1], 0);

            // Set a timeout
            $startTime = time();
            $output    = '';

            // Read from the pipe with timeout
            while (time() - $startTime < self::REGISTRY_TIMEOUT) {
                $read   = array($pipes[1]);
                $write  = null;
                $except = null;

                // Wait for data with a short timeout
                if (stream_select($read, $write, $except, 1)) {
                    $output .= stream_get_contents($pipes[1]);

                    // If we have a complete response, break
                    if (!empty($output) && strpos($output, 'NOT_EXISTS') === false && strpos($output, 'ERROR:') === false) {
                        break;
                    }
                }
            }

            // Close pipes and process
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            // Terminate the process if it's still running
            $status = proc_get_status($process);
            if ($status['running']) {
                proc_terminate($process);
            }

            proc_close($process);

            // Check the result
            if (strpos($output, 'NOT_EXISTS') !== false || strpos($output, 'ERROR:') !== false) {
                $this->writeLog('getValue result: false (key not found or error)');

                return false;
            }

            $value = trim($output);
            $this->writeLog('getValue result: ' . $value);

            return $value;
        }

        // Fallback to VBS if PowerShell fails
        $this->writeLog('Falling back to VBS for getValue');
        $resultFile = Vbs::getTmpFile();
        $content    = 'On Error Resume Next' . PHP_EOL;
        $content    .= 'Dim WshShell, value' . PHP_EOL;
        $content    .= 'Set WshShell = CreateObject("WScript.Shell")' . PHP_EOL;
        $content    .= 'value = WshShell.RegRead("' . $hKey . '\\' . $subKey . '\\' . $valueName . '")' . PHP_EOL;
        $content    .= 'If Err.Number = 0 Then' . PHP_EOL;
        $content    .= '    WScript.Echo value' . PHP_EOL;
        $content    .= 'Else' . PHP_EOL;
        $content    .= '    WScript.Echo "ERROR: " & Err.Description' . PHP_EOL;
        $content    .= 'End If' . PHP_EOL;

        $result = Vbs::exec('getValue', $resultFile, $content, 5);

        if (empty($result) || strpos($result[0], 'ERROR:') === 0) {
            $this->writeLog('getValue result (VBS): false');

            return false;
        }

        $value = trim($result[0]);
        $this->writeLog('getValue result (VBS): ' . $value);

        return $value;
    }

    /**
     * Sets a string value in the registry.
     *
     * @param   string  $hKey       The registry hive.
     * @param   string  $subKey     The registry subkey.
     * @param   string  $valueName  The registry value name.
     * @param   string  $value      The value to set.
     *
     * @return bool True on success, false on failure.
     */
    public function setStringValue($hKey, $subKey, $valueName, $value)
    {
        return $this->setValue($hKey, $subKey, $valueName, $value, self::REG_SZ);
    }

    /**
     * Sets an expandable string value in the registry.
     *
     * @param   string  $hKey       The registry hive.
     * @param   string  $subKey     The registry subkey.
     * @param   string  $valueName  The registry value name.
     * @param   string  $value      The value to set.
     *
     * @return bool True on success, false on failure.
     */
    public function setExpandStringValue($hKey, $subKey, $valueName, $value)
    {
        return $this->setValue($hKey, $subKey, $valueName, $value, self::REG_EXPAND_SZ);
    }

    /**
     * Sets a registry value.
     *
     * @param   string  $hKey       The registry hive.
     * @param   string  $subKey     The registry subkey.
     * @param   string  $valueName  The registry value name.
     * @param   mixed   $value      The value to set.
     * @param   string  $type       The registry value type.
     *
     * @return bool True on success, false on failure.
     */
    private function setValue($hKey, $subKey, $valueName, $value, $type)
    {
        $this->writeLog('setValue: ' . $hKey . '\\' . $subKey . '\\' . $valueName . ' = ' . $value . ' (' . $type . ')');

        // Use PowerShell for faster and more reliable registry access
        $ps_command = "powershell -NoProfile -ExecutionPolicy Bypass -Command \"";
        $ps_command .= 'try { ';
        $ps_command .= "if (!(Test-Path 'Registry::" . $hKey . "\\" . $subKey . "')) { ";
        $ps_command .= "New-Item -Path 'Registry::" . $hKey . "\\" . $subKey . "' -Force | Out-Null ";
        $ps_command .= '} ';

        // Handle different registry types
        switch ($type) {
            case self::REG_SZ:
                $ps_command .= "Set-ItemProperty -Path 'Registry::" . $hKey . "\\" . $subKey . "' -Name '" . $valueName . "' -Value '" . str_replace(
                        "'",
                        "''",
                        $value
                    ) . "' -Type String -Force ";
                break;
            case self::REG_EXPAND_SZ:
                $ps_command .= "Set-ItemProperty -Path 'Registry::" . $hKey . "\\" . $subKey . "' -Name '" . $valueName . "' -Value '" . str_replace(
                        "'",
                        "''",
                        $value
                    ) . "' -Type ExpandString -Force ";
                break;
            case self::REG_DWORD:
                $ps_command .= "Set-ItemProperty -Path 'Registry::" . $hKey . "\\" . $subKey . "' -Name '" . $valueName . "' -Value " . intval($value) . ' -Type DWord -Force ';
                break;
            default:
                $ps_command .= "Set-ItemProperty -Path 'Registry::" . $hKey . "\\" . $subKey . "' -Name '" . $valueName . "' -Value '" . str_replace(
                        "'",
                        "''",
                        $value
                    ) . "' -Type String -Force ";
        }

        $ps_command .= "Write-Output 'SUCCESS' ";
        $ps_command .= "} catch { Write-Output 'ERROR: ' + $_.Exception.Message }\"";

        // Execute the command with a timeout
        $descriptorspec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );

        $process = proc_open($ps_command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            // Set non-blocking mode on the stdout pipe
            stream_set_blocking($pipes[1], 0);

            // Set a timeout
            $startTime = time();
            $output    = '';

            // Read from the pipe with timeout
            while (time() - $startTime < self::REGISTRY_TIMEOUT) {
                $read   = array($pipes[1]);
                $write  = null;
                $except = null;

                // Wait for data with a short timeout
                if (stream_select($read, $write, $except, 1)) {
                    $output .= stream_get_contents($pipes[1]);

                    // If we have a complete response, break
                    if (strpos($output, 'SUCCESS') !== false || strpos($output, 'ERROR:') !== false) {
                        break;
                    }
                }
            }

            // Close pipes and process
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            // Terminate the process if it's still running
            $status = proc_get_status($process);
            if ($status['running']) {
                proc_terminate($process);
            }

            proc_close($process);

            // Check the result
            $success = (strpos($output, 'SUCCESS') !== false);
            $this->writeLog('setValue result: ' . ($success ? 'true' : 'false'));

            return $success;
        }

        // Fallback to VBS if PowerShell fails
        $this->writeLog('Falling back to VBS for setValue');
        $resultFile = Vbs::getTmpFile();
        $content    = 'On Error Resume Next' . PHP_EOL;
        $content    .= 'Dim WshShell' . PHP_EOL;
        $content    .= 'Set WshShell = CreateObject("WScript.Shell")' . PHP_EOL;

        // Handle different registry types
        switch ($type) {
            case self::REG_SZ:
                $content .= 'WshShell.RegWrite "' . $hKey . '\\' . $subKey . '\\' . $valueName . '", "' . str_replace('"', '""', $value) . '", "REG_SZ"' . PHP_EOL;
                break;
            case self::REG_EXPAND_SZ:
                $content .= 'WshShell.RegWrite "' . $hKey . '\\' . $subKey . '\\' . $valueName . '", "' . str_replace('"', '""', $value) . '", "REG_EXPAND_SZ"' . PHP_EOL;
                break;
            case self::REG_DWORD:
                $content .= 'WshShell.RegWrite "' . $hKey . '\\' . $subKey . '\\' . $valueName . '", ' . intval($value) . ', "REG_DWORD"' . PHP_EOL;
                break;
            default:
                $content .= 'WshShell.RegWrite "' . $hKey . '\\' . $subKey . '\\' . $valueName . '", "' . str_replace('"', '""', $value) . '", "REG_SZ"' . PHP_EOL;
        }

        $content .= 'If Err.Number = 0 Then' . PHP_EOL;
        $content .= '    WScript.Echo "' . self::REG_NO_ERROR . '"' . PHP_EOL;
        $content .= 'Else' . PHP_EOL;
        $content .= '    WScript.Echo "' . self::REG_ERROR . '"' . PHP_EOL;
        $content .= 'End If' . PHP_EOL;

        $result = Vbs::exec('setValue', $resultFile, $content, 5);

        $success = !empty($result) && trim($result[0]) == self::REG_NO_ERROR;
        $this->writeLog('setValue result (VBS): ' . ($success ? 'true' : 'false'));

        return $success;
    }

    /**
     * Deletes a registry value.
     *
     * @param   string  $hKey       The registry hive.
     * @param   string  $subKey     The registry subkey.
     * @param   string  $valueName  The registry value name.
     *
     * @return bool True on success, false on failure.
     */
    public function deleteValue($hKey, $subKey, $valueName)
    {
        $this->writeLog('deleteValue: ' . $hKey . '\\' . $subKey . '\\' . $valueName);
        
        // Use direct REG command for reliable registry deletion
        $regCmd = 'reg delete "' . $hKey . '\\' . $subKey . '" /v "' . $valueName . '" /f 2>nul';
        $output = [];
        $returnCode = 0;
        exec($regCmd, $output, $returnCode);
        
        // Check if deletion was successful (return code 0 means success)
        // Return code 1 often means the key doesn't exist, which is fine for deletion
        $success = ($returnCode === 0 || $returnCode === 1);
        
        // Log the result
        $this->writeLog('deleteValue result: ' . ($success ? 'true' : 'false') . ' (return code: ' . $returnCode . ')');
        
        // If direct REG command failed, try PowerShell as first fallback
        if (!$success) {
            $this->writeLog('REG command failed, trying PowerShell for deleteValue');
            
            $ps_command = "powershell -NoProfile -ExecutionPolicy Bypass -Command \"";
            $ps_command .= 'try { ';
            $ps_command .= "if (Test-Path 'Registry::" . $hKey . "\\" . $subKey . "') { ";
            $ps_command .= "Remove-ItemProperty -Path 'Registry::" . $hKey . "\\" . $subKey . "' -Name '" . $valueName . "' -Force -ErrorAction Stop ";
            $ps_command .= "Write-Output 'SUCCESS' ";
            $ps_command .= "} else { Write-Output 'SUCCESS' } "; // Consider it a success if the key doesn't exist
            $ps_command .= "} catch { Write-Output 'ERROR: ' + $_.Exception.Message }\"";

            // Execute the command with a timeout
            $descriptorspec = array(
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w')
            );

            $process = proc_open($ps_command, $descriptorspec, $pipes);
            $output = '';

            if (is_resource($process)) {
                // Set non-blocking mode on the stdout pipe
                stream_set_blocking($pipes[1], 0);

                // Set a timeout
                $startTime = time();
                
                // Read from the pipe with timeout
                while (time() - $startTime < self::REGISTRY_TIMEOUT) {
                    $read   = array($pipes[1]);
                    $write  = null;
                    $except = null;

                    // Wait for data with a short timeout
                    if (stream_select($read, $write, $except, 1)) {
                        $output .= stream_get_contents($pipes[1]);

                        // If we have a complete response, break
                        if (strpos($output, 'SUCCESS') !== false || strpos($output, 'ERROR:') !== false) {
                            break;
                        }
                    }
                }

                // Close pipes and process
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);

                // Terminate the process if it's still running
                $status = proc_get_status($process);
                if ($status['running']) {
                    proc_terminate($process);
                }

                proc_close($process);

                // Check the result
                $success = (strpos($output, 'SUCCESS') !== false);
                $this->writeLog('deleteValue result (PowerShell): ' . ($success ? 'true' : 'false'));
                
                if ($success) {
                    return true;
                }
            }
            
            // Fallback to VBS if PowerShell fails
            $this->writeLog('PowerShell failed, trying VBS for deleteValue');
            $resultFile = Vbs::getTmpFile();
            $content    = 'On Error Resume Next' . PHP_EOL;
            $content    .= 'Dim WshShell' . PHP_EOL;
            $content    .= 'Set WshShell = CreateObject("WScript.Shell")' . PHP_EOL;
            $content    .= 'WshShell.RegDelete "' . $hKey . '\\' . $subKey . '\\' . $valueName . '"' . PHP_EOL;
            $content    .= 'If Err.Number = 0 Or Err.Number = 2 Then' . PHP_EOL; // 2 = File not found, which is fine for deletion
            $content    .= '    WScript.Echo "' . self::REG_NO_ERROR . '"' . PHP_EOL;
            $content    .= 'Else' . PHP_EOL;
            $content    .= '    WScript.Echo "' . self::REG_ERROR . '"' . PHP_EOL;
            $content    .= 'End If' . PHP_EOL;

            $result = Vbs::exec('deleteValue', $resultFile, $content, 5);

            $success = !empty($result) && trim($result[0]) == self::REG_NO_ERROR;
            $this->writeLog('deleteValue result (VBS): ' . ($success ? 'true' : 'false'));
        }

        return $success;
    }

    /**
     * Deletes a registry key.
     *
     * @param   string  $hKey    The registry hive.
     * @param   string  $subKey  The registry subkey.
     *
     * @return bool True on success, false on failure.
     */
    public function deleteKey($hKey, $subKey)
    {
        $this->writeLog('deleteKey: ' . $hKey . '\\' . $subKey);

        // Use PowerShell for faster and more reliable registry access
        $ps_command = "powershell -NoProfile -ExecutionPolicy Bypass -Command \"";
        $ps_command .= 'try { ';
        $ps_command .= "if (Test-Path 'Registry::" . $hKey . "\\" . $subKey . "') { ";
        $ps_command .= "Remove-Item -Path 'Registry::" . $hKey . "\\" . $subKey . "' -Force -Recurse -ErrorAction Stop ";
        $ps_command .= "Write-Output 'SUCCESS' ";
        $ps_command .= "} else { Write-Output 'SUCCESS' } "; // Consider it a success if the key doesn't exist
        $ps_command .= "} catch { Write-Output 'ERROR: ' + $_.Exception.Message }\"";

        // Execute the command with a timeout
        $descriptorspec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );

        $process = proc_open($ps_command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            // Set non-blocking mode on the stdout pipe
            stream_set_blocking($pipes[1], 0);

            // Set a timeout
            $startTime = time();
            $output    = '';

            // Read from the pipe with timeout
            while (time() - $startTime < self::REGISTRY_TIMEOUT) {
                $read   = array($pipes[1]);
                $write  = null;
                $except = null;

                // Wait for data with a short timeout
                if (stream_select($read, $write, $except, 1)) {
                    $output .= stream_get_contents($pipes[1]);

                    // If we have a complete response, break
                    if (strpos($output, 'SUCCESS') !== false || strpos($output, 'ERROR:') !== false) {
                        break;
                    }
                }
            }

            // Close pipes and process
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            // Terminate the process if it's still running
            $status = proc_get_status($process);
            if ($status['running']) {
                proc_terminate($process);
            }

            proc_close($process);

            // Check the result
            $success = (strpos($output, 'SUCCESS') !== false);
            $this->writeLog('deleteKey result: ' . ($success ? 'true' : 'false'));

            return $success;
        }

        // Fallback to VBS if PowerShell fails
        $this->writeLog('Falling back to VBS for deleteKey');
        $resultFile = Vbs::getTmpFile();
        $content    = 'On Error Resume Next' . PHP_EOL;
        $content    .= 'Dim WshShell' . PHP_EOL;
        $content    .= 'Set WshShell = CreateObject("WScript.Shell")' . PHP_EOL;
        $content    .= 'WshShell.RegDelete "' . $hKey . '\\' . $subKey . '\\"' . PHP_EOL;
        $content    .= 'If Err.Number = 0 Or Err.Number = 2 Then' . PHP_EOL; // 2 = File not found, which is fine for deletion
        $content    .= '    WScript.Echo "' . self::REG_NO_ERROR . '"' . PHP_EOL;
        $content    .= 'Else' . PHP_EOL;
        $content    .= '    WScript.Echo "' . self::REG_ERROR . '"' . PHP_EOL;
        $content    .= 'End If' . PHP_EOL;

        $result = Vbs::exec('deleteKey', $resultFile, $content, 5);

        $success = !empty($result) && trim($result[0]) == self::REG_NO_ERROR;
        $this->writeLog('deleteKey result (VBS): ' . ($success ? 'true' : 'false'));

        return $success;
    }
}
