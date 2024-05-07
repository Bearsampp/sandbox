<?php
/**
 * Core class for handling paths and configurations specific to the Bearsampp environment.
 * This class provides methods to retrieve various paths used within the Bearsampp application,
 * such as libraries, resources, languages, and icons. It also handles conditional loading
 * of the Winbinder extension if available.
 */
class Core
{
    // Constants for file names, paths, and versions of various components used within the application.

    /**
     * Constructor that checks for the Winbinder extension and includes it if present.
     */
    public function __construct()
    {
        if (extension_loaded('winbinder')) {
            require_once $this->getLibsPath() . '/winbinder/winbinder.php';
        }
    }

    /**
     * Retrieves the path to the languages directory.
     *
     * @param bool $aetrayPath Whether to adjust the path for the AeTrayMenu environment.
     * @return string The path to the languages directory.
     */
    public function getLangsPath($aetrayPath = false)
    {
        global $bearsamppRoot;
        return $bearsamppRoot->getCorePath($aetrayPath) . '/langs';
    }

    /**
     * Retrieves the path to the libraries directory.
     *
     * @param bool $aetrayPath Whether to adjust the path for the AeTrayMenu environment.
     * @return string The path to the libraries directory.
     */
    public function getLibsPath($aetrayPath = false)
    {
        global $bearsamppRoot;
        return $bearsamppRoot->getCorePath($aetrayPath) . '/libs';
    }

    /**
     * Retrieves the path to the resources directory.
     *
     * @param bool $aetrayPath Whether to adjust the path for the AeTrayMenu environment.
     * @return string The path to the resources directory.
     */
    public function getResourcesPath($aetrayPath = false)
    {
        global $bearsamppRoot;
        return $bearsamppRoot->getCorePath($aetrayPath) . '/resources';
    }

    /**
     * Retrieves the path to the icons directory.
     *
     * @param bool $aetrayPath Whether to adjust the path for the AeTrayMenu environment.
     * @return string The path to the icons directory.
     */
    public function getIconsPath($aetrayPath = false)
    {
        global $bearsamppCore;
        return $bearsamppCore->getResourcesPath($aetrayPath) . '/icons';
    }

    /**
     * Returns the path to the scripts directory.
     *
     * @param bool $aetrayPath Whether to return the path with the AeTrayMenu path format.
     * @return string The full path to the scripts directory.
     */
    public function getScriptsPath($aetrayPath = false)
    {
        global $bearsamppRoot;
        return $bearsamppRoot->getCorePath($aetrayPath) . '/scripts';
    }

    /**
     * Returns the full path to a specific script file.
     *
     * @param string $type The type of script file.
     * @return string The full path to the script file.
     */
    public function getScript($type)
    {
        return $this->getScriptsPath() . '/' . $type;
    }

    /**
     * Returns the path to the temporary directory.
     *
     * @param bool $aetrayPath Whether to return the path with the AeTrayMenu path format.
     * @return string The full path to the temporary directory.
     */
    public function getTmpPath($aetrayPath = false)
    {
        global $bearsamppRoot;
        return $bearsamppRoot->getCorePath($aetrayPath) . '/tmp';
    }

    /**
     * Returns the path to the isRoot file.
     *
     * @param bool $aetrayPath Whether to return the path with the AeTrayMenu path format.
     * @return string The full path to the isRoot file.
     */
    public function getisRootFilePath($aetrayPath = false)
    {
        global $bearsamppRoot;
        return $bearsamppRoot->getCorePath($aetrayPath) . '/' . self::isRoot_FILE;
    }

    /**
     * Retrieves the application version from a file.
     *
     * @return string|null The application version or null if the file does not exist.
     */
    public function getAppVersion()
    {
        global $bearsamppLang;

        $filePath = $this->getResourcesPath() . '/' . self::APP_VERSION;
        if (!is_file($filePath)) {
            Util::logError(sprintf($bearsamppLang->getValue(Lang::ERROR_CONF_NOT_FOUND), APP_TITLE, $filePath));
            return null;
        }

        return trim(file_get_contents($filePath));
    }

    /**
     * Returns the path to the last path file.
     *
     * @param bool $aetrayPath Whether to return the path with the AeTrayMenu path format.
     * @return string The full path to the last path file.
     */
    public function getLastPath($aetrayPath = false)
    {
        return $this->getResourcesPath($aetrayPath) . '/' . self::LAST_PATH;
    }

    /**
     * Reads the content of the last path file.
     *
     * @return string|false The content of the last path file or false on failure.
     */
    public function getLastPathContent()
    {
        return @file_get_contents($this->getLastPath());
    }

    /**
     * Returns the path to the executable file.
     *
     * @param bool $aetrayPath Whether to return the path with the AeTrayMenu path format.
     * @return string The full path to the executable file.
     */
    public function getExec($aetrayPath = false)
    {
        return $this->getTmpPath($aetrayPath) . '/' . self::EXEC;
    }

    /**
     * Writes an action to the executable file.
     *
     * @param string $action The action to write.
     */
    public function setExec($action)
    {
        file_put_contents($this->getExec(), $action);
    }

    /**
     * Returns the path to the loading PID file.
     *
     * @param bool $aetrayPath Whether to return the path with the AeTrayMenu path format.
     * @return string The full path to the loading PID file.
     */
    public function getLoadingPid($aetrayPath = false)
    {
        return $this->getResourcesPath($aetrayPath) . '/' . self::LOADING_PID;
    }

    /**
     * Appends a PID to the loading PID file.
     *
     * @param int $pid The process ID to add.
     */
    public function addLoadingPid($pid)
    {
        file_put_contents($this->getLoadingPid(), $pid . PHP_EOL, FILE_APPEND);
    }

    /**
     * Returns the path to the PHP directory.
     *
     * @param bool $aetrayPath Whether to return the path with the AeTrayMenu path format.
     * @return string The full path to the PHP directory.
     */
    public function getPhpPath($aetrayPath = false)
    {
        return $this->getLibsPath($aetrayPath) . '/php';
    }

    /**
     * Returns the full path to the PHP executable.
     *
     * @param bool $aetrayPath Whether to adjust the path for the AeTrayMenu environment.
     * @return string The full path to the PHP executable.
     */
    public function getPhpExe($aetrayPath = false)
    {
        return $this->getPhpPath($aetrayPath) . '/' . self::PHP_EXE;
    }

    /**
     * Retrieves the path to the setenv directory.
     *
     * @param bool $aetrayPath Whether to adjust the path for the AeTrayMenu environment.
     * @return string The path to the setenv directory.
     */
    public function getSetEnvPath($aetrayPath = false)
    {
        return $this->getLibsPath($aetrayPath) . '/setenv';
    }

    /**
     * Returns the full path to the setenv executable.
     *
     * @param bool $aetrayPath Whether to adjust the path for the AeTrayMenu environment.
     * @return string The full path to the setenv executable.
     */
    public function getSetEnvExe($aetrayPath = false)
    {
        return $this->getSetEnvPath($aetrayPath) . '/' . self::SETENV_EXE;
    }

    /**
     * Retrieves the path to the nssm directory.
     *
     * @param bool $aetrayPath Whether to adjust the path for the AeTrayMenu environment.
     * @return string The path to the nssm directory.
     */
    public function getNssmPath($aetrayPath = false)
    {
        return $this->getLibsPath($aetrayPath) . '/nssm';
    }

    /**
     * Returns the full path to the nssm executable.
     *
     * @param bool $aetrayPath Whether to adjust the path for the AeTrayMenu environment.
     * @return string The full path to the nssm executable.
     */
    public function getNssmExe($aetrayPath = false)
    {
        return $this->getNssmPath($aetrayPath) . '/' . self::NSSM_EXE;
    }

    /**
     * Retrieves the path to the OpenSSL directory.
     *
     * @param bool $aetrayPath Whether to adjust the path for the AeTrayMenu environment.
     * @return string The path to the OpenSSL directory.
     */
    public function getOpenSslPath($aetrayPath = false)
    {
        return $this->getLibsPath($aetrayPath) . '/openssl';
    }

    /**
     * Returns the full path to the OpenSSL executable.
     *
     * @param bool $aetrayPath Whether to adjust the path for the AeTrayMenu environment.
     * @return string The full path to the OpenSSL executable.
     */
    public function getOpenSslExe($aetrayPath = false)
    {
        return $this->getOpenSslPath($aetrayPath) . '/' . self::OPENSSL_EXE;
    }

    /**
     * Returns the full path to the OpenSSL configuration file.
     *
     * @param bool $aetrayPath Whether to adjust the path for the AeTrayMenu environment.
     * @return string The full path to the OpenSSL configuration file.
     */
    public function getOpenSslConf($aetrayPath = false)
    {
        return $this->getOpenSslPath($aetrayPath) . '/' . self::OPENSSL_CONF;
    }

    /**
     * Retrieves the path to the hosts editor directory.
     *
     * @param bool $aetrayPath Whether to adjust the path for the AeTrayMenu environment.
     * @return string The path to the hosts editor directory.
     */
    public function getHostsEditorPath($aetrayPath = false)
    {
        return $this->getLibsPath($aetrayPath) . '/hostseditor';
    }

    /**
     * Returns the full path to the hosts editor executable.
     *
     * @param bool $aetrayPath Whether to adjust the path for the AeTrayMenu environment.
     * @return string The full path to the hosts editor executable.
     */
    public function getHostsEditorExe($aetrayPath = false)
    {
        return $this->getHostsEditorPath($aetrayPath) . '/' . self::HOSTSEDITOR_EXE;
    }

    /**
     * Retrieves the path to the ln directory.
     *
     * @param bool $aetrayPath Whether to adjust the path for the AeTrayMenu environment.
     * @return string The path to the ln directory.
     */
    public function getLnPath($aetrayPath = false)
    {
        return $this->getLibsPath($aetrayPath) . '/ln';
    }

    /**
     * Returns the full path to the ln executable.
     *
     * @param bool $aetrayPath Whether to adjust the path for the AeTrayMenu environment.
     * @return string The full path to the ln executable.
     */
    public function getLnExe($aetrayPath = false)
    {
        return $this->getLnPath($aetrayPath) . '/' . self::LN_EXE;
    }

    /**
     * Provides a string representation of the core object.
     *
     * @return string A string describing the core object.
     */
    public function __toString() {
        return 'core object';
    }

    /**
     * Unzips a file to a specified destination.
     *
     * @param string $zipFilePath The path to the zip file.
     * @param string $destinationPath The path where the contents should be extracted.
     * @return bool True on success, false on failure.
     */
    public function unzipFile($zipFilePath, $destinationPath)
    {
        $zip = new ZipArchive;
        if ($zip->open($zipFilePath) === TRUE) {
            $zip->extractTo($destinationPath);
            $zip->close();
            return true;
        } else {
            return false;
        }
    }

}
