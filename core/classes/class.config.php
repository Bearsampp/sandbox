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
 * Class Config
 *
 * This class handles the configuration settings for the Bearsampp application.
 * It reads the configuration from an INI file and provides methods to access and modify these settings.
 */
class Config
{
    const CFG_MAX_LOGS_ARCHIVES = 'maxLogsArchives';
    const CFG_LOGS_VERBOSE = 'logsVerbose';
    const CFG_LANG = 'lang';
    const CFG_TIMEZONE = 'timezone';
    const CFG_NOTEPAD = 'notepad';
    const CFG_SCRIPTS_TIMEOUT = 'scriptsTimeout';
    const DOWNLOAD_ID = 'DownloadId';

    const CFG_DEFAULT_LANG = 'defaultLang';
    const CFG_HOSTNAME = 'hostname';
    const CFG_BROWSER = 'browser';
    const CFG_BROWSER_MODE = 'browserMode';
    const CFG_DEFAULT_BROWSER = 'defaultBrowser';
    const CFG_CUSTOM_BROWSER = 'customBrowser';
    const CFG_ONLINE = 'online';
    const CFG_LAUNCH_STARTUP = 'launchStartup';

    const ENABLED = 1;
    const DISABLED = 0;

    const BROWSER_DEFAULT = 'default';
    const BROWSER_CUSTOM = 'custom';

    const VERBOSE_SIMPLE = 0;
    const VERBOSE_REPORT = 1;
    const VERBOSE_DEBUG = 2;
    const VERBOSE_TRACE = 3;

    private $raw;
    private $defaultBrowser;
    private $customBrowser;
    private $browserMode;

    /**
     * Constructs a Config object and initializes the configuration settings.
     * Reads the configuration from the INI file and sets the default timezone.
     */
    public function __construct()
    {
        global $bearsamppRoot;

        // Set current timezone to match whats in .conf
        $this->raw = parse_ini_file($bearsamppRoot->getConfigFilePath());
        date_default_timezone_set($this->getTimezone());
        
        // Initialize browser settings with proper defaults
        $this->browserMode = isset($this->raw[self::CFG_BROWSER_MODE]) ? $this->raw[self::CFG_BROWSER_MODE] : self::BROWSER_DEFAULT;
        $this->defaultBrowser = isset($this->raw[self::CFG_DEFAULT_BROWSER]) ? $this->raw[self::CFG_DEFAULT_BROWSER] : '';
        $this->customBrowser = isset($this->raw[self::CFG_CUSTOM_BROWSER]) ? $this->raw[self::CFG_CUSTOM_BROWSER] : '';
        
        // For backward compatibility
        if (isset($this->raw[self::CFG_BROWSER]) && !isset($this->raw[self::CFG_DEFAULT_BROWSER])) {
            $this->defaultBrowser = $this->raw[self::CFG_BROWSER];
            // Also update the raw array to ensure consistency
            $this->raw[self::CFG_DEFAULT_BROWSER] = $this->defaultBrowser;
        }
        
        // Ensure browser mode is valid
        if ($this->browserMode != self::BROWSER_DEFAULT && $this->browserMode != self::BROWSER_CUSTOM) {
            $this->browserMode = self::BROWSER_DEFAULT;
            $this->raw[self::CFG_BROWSER_MODE] = self::BROWSER_DEFAULT;
        }
        
        // Log browser settings for debugging
        Util::logTrace("Browser settings initialized - Mode: {$this->browserMode}, Default: {$this->defaultBrowser}, Custom: {$this->customBrowser}");
    }

    /**
     * Retrieves the raw configuration value for the specified key.
     *
     * @param string $key The configuration key.
     * @return mixed The configuration value.
     */
    public function getRaw($key)
    {
        return isset($this->raw[$key]) ? $this->raw[$key] : null;
    }

    /**
     * Replaces a single configuration value with the specified key and value.
     *
     * @param string $key The configuration key.
     * @param mixed $value The new configuration value.
     */
    public function replace($key, $value)
    {
        $this->replaceAll(array($key => $value));
    }

    /**
     * Replaces multiple configuration values with the specified key-value pairs.
     *
     * @param array $params An associative array of key-value pairs to replace.
     */
    public function replaceAll($params)
    {
        global $bearsamppRoot;

        Util::logTrace('Replace config:');
        $content = file_get_contents($bearsamppRoot->getConfigFilePath());
        foreach ($params as $key => $value) {
            // Check if the key already exists in the file
            if (preg_match('/^' . preg_quote($key, '/') . '\s*=\s*.*/m', $content)) {
                // Replace existing key
                $content = preg_replace('/^' . preg_quote($key, '/') . '\s*=\s*.*/m', $key . ' = "' . $value . '"', $content, -1, $count);
                Util::logTrace('## ' . $key . ': ' . $value . ' (' . $count . ' replacements done)');
            } else {
                // Add new key at the end of the file
                $content .= PHP_EOL . $key . ' = "' . $value . '"';
                Util::logTrace('## ' . $key . ': ' . $value . ' (added new key)');
            }
            $this->raw[$key] = $value;
        }

        // Write the updated content back to the file
        if (file_put_contents($bearsamppRoot->getConfigFilePath(), $content) === false) {
            Util::logError("Failed to write configuration to file: " . $bearsamppRoot->getConfigFilePath());
        }
    }

    /**
     * Retrieves the language setting from the configuration.
     *
     * @return string The language setting.
     */
    public function getLang()
    {
        return $this->raw[self::CFG_LANG];
    }

    /**
     * Retrieves the default language setting from the configuration.
     *
     * @return string The default language setting.
     */
    public function getDefaultLang()
    {
        return $this->raw[self::CFG_DEFAULT_LANG];
    }

    /**
     * Retrieves the timezone setting from the configuration.
     *
     * @return string The timezone setting.
     */
    public function getTimezone()
    {
        return $this->raw[self::CFG_TIMEZONE];
    }

    /**
     * Retrieves the license key from the configuration.
     *
     * @return string The license key.
     */
    public function getDownloadId()
    {
        return $this->raw[self::DOWNLOAD_ID];
    }

    /**
     * Checks if the application is set to be online.
     *
     * @return bool True if online, false otherwise.
     */
    public function isOnline()
    {
        return $this->raw[self::CFG_ONLINE] == self::ENABLED;
    }

    /**
     * Checks if the application is set to launch at startup.
     *
     * @return bool True if set to launch at startup, false otherwise.
     */
    public function isLaunchStartup()
    {
        return $this->raw[self::CFG_LAUNCH_STARTUP] == self::ENABLED;
    }

    /**
     * Gets the browser mode (default or custom).
     *
     * @return string The browser mode.
     */
    public function getBrowserMode()
    {
        return $this->browserMode;
    }

    /**
     * Sets the browser mode (default or custom).
     *
     * @param string $browserMode The browser mode.
     */
    public function setBrowserMode($browserMode)
    {
        if ($browserMode == self::BROWSER_DEFAULT || $browserMode == self::BROWSER_CUSTOM) {
            $this->browserMode = $browserMode;
            $this->replace(self::CFG_BROWSER_MODE, $browserMode);
            Util::logTrace("Browser mode set to: " . $browserMode);
        } else {
            // Default to default browser if invalid mode is provided
            $this->browserMode = self::BROWSER_DEFAULT;
            $this->replace(self::CFG_BROWSER_MODE, self::BROWSER_DEFAULT);
            Util::logWarning("Invalid browser mode '{$browserMode}' specified, defaulting to " . self::BROWSER_DEFAULT);
        }
    }

    /**
     * Gets the path to the default browser executable.
     *
     * @return string The path to the default browser executable.
     */
    public function getDefaultBrowser()
    {
        return $this->defaultBrowser;
    }

    /**
     * Sets the path to the default browser executable.
     *
     * @param string $defaultBrowser The path to the default browser executable.
     */
    public function setDefaultBrowser($defaultBrowser)
    {
        $this->defaultBrowser = $defaultBrowser;
        $this->replace(self::CFG_DEFAULT_BROWSER, $defaultBrowser);
        // For backward compatibility
        $this->replace(self::CFG_BROWSER, $defaultBrowser);
        Util::logTrace("Default browser set to: " . $defaultBrowser);
    }

    /**
     * Gets the path to the custom browser executable.
     *
     * @return string The path to the custom browser executable.
     */
    public function getCustomBrowser()
    {
        return $this->customBrowser;
    }

    /**
     * Sets the path to the custom browser executable.
     *
     * @param string $customBrowser The path to the custom browser executable.
     */
    public function setCustomBrowser($customBrowser)
    {
        $this->customBrowser = $customBrowser;
        $this->replace(self::CFG_CUSTOM_BROWSER, $customBrowser);
        Util::logTrace("Custom browser set to: " . $customBrowser);
    }

    /**
     * Retrieves the browser setting from the configuration.
     * This method determines which browser to use based on the browser mode.
     *
     * @return string The browser setting.
     */
    public function getBrowser()
    {
        Util::logTrace("Getting browser with mode: {$this->browserMode}");
        
        if ($this->browserMode == self::BROWSER_CUSTOM && !empty($this->customBrowser) && file_exists($this->customBrowser)) {
            Util::logTrace("Using custom browser: {$this->customBrowser}");
            return $this->customBrowser;
        } else {
            // If custom browser is selected but not valid, log a warning
            if ($this->browserMode == self::BROWSER_CUSTOM) {
                Util::logWarning("Custom browser selected but path is invalid or empty: {$this->customBrowser}");
            }
            
            // Use default browser if it's set
            if (!empty($this->defaultBrowser)) {
                Util::logTrace("Using default browser: {$this->defaultBrowser}");
                return $this->defaultBrowser;
            }
            
            // Fallback to system default browser
            Util::logTrace("No browser configured, using system default");
            return 'explorer.exe';
        }
    }

    /**
     * Retrieves the hostname setting from the configuration.
     *
     * @return string The hostname setting.
     */
    public function getHostname()
    {
        return $this->raw[self::CFG_HOSTNAME];
    }

    /**
     * Retrieves the scripts timeout setting from the configuration.
     *
     * @return int The scripts timeout setting.
     */
    public function getScriptsTimeout()
    {
        return intval($this->raw[self::CFG_SCRIPTS_TIMEOUT]);
    }

    /**
     * Retrieves the notepad setting from the configuration.
     *
     * @return string The notepad setting.
     */
    public function getNotepad()
    {
        return $this->raw[self::CFG_NOTEPAD];
    }

    /**
     * Retrieves the logs verbosity setting from the configuration.
     *
     * @return int The logs verbosity setting.
     */
    public function getLogsVerbose()
    {
        return intval($this->raw[self::CFG_LOGS_VERBOSE]);
    }

    /**
     * Retrieves the maximum logs archives setting from the configuration.
     *
     * @return int The maximum logs archives setting.
     */
    public function getMaxLogsArchives()
    {
        return intval($this->raw[self::CFG_MAX_LOGS_ARCHIVES]);
    }
}
