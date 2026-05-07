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
 * Path formatting utilities and registry path management with an in-memory read-through cache for formatting results.
 *
 * Converts between Windows (backslash) and Unix (forward-slash) path styles,
 * manages path-related registry keys for the application and system environment,
 * and caches formatting results to avoid redundant string replacements for
 * frequently used paths such as root, bin, and tool paths.
 *
 * Note: Caching only applies to formatWindowsPath and formatUnixPath. Registry
 * operations perform direct registry calls and do not use the cache.
 *
 * Usage:
 * ```
 * $win = Path::formatWindowsPath('/some/unix/path');
 * $unix = Path::formatUnixPath('C:\some\windows\path');
 * $regKey = Path::getAppBinsRegKey();
 * ```
 */
class Path
{
    /**
     * Cache for path formatting operations to avoid redundant string replacements.
     * @var array
     */
    private static $pathFormatCache = [];

    /**
     * Maximum size for path format cache to prevent memory issues.
     * @var int
     */
    private static $pathFormatCacheMaxSize = 500;

    /**
     * Statistics for monitoring path format cache effectiveness.
     * @var array
     */
    private static $pathFormatStats = [
        'unix_hits'      => 0,
        'unix_misses'    => 0,
        'windows_hits'   => 0,
        'windows_misses' => 0,
    ];

    /**
     * Converts a Unix-style path to a Windows-style path with caching.
     * This is a Windows application, so paths use backslashes (\) as separators.
     *
     * Performance optimization: Caches results to avoid redundant string replacements
     * for frequently used paths (e.g., root paths, bin paths).
     *
     * @param   string  $path  The Unix-style path to convert.
     *
     * @return string Returns the converted Windows-style path.
     */
    public static function formatWindowsPath($path)
    {
        if (empty($path)) {
            return $path;
        }

        $cacheKey = 'w_' . $path;
        if (isset(self::$pathFormatCache[$cacheKey])) {
            self::$pathFormatStats['windows_hits']++;
            return self::$pathFormatCache[$cacheKey];
        }

        self::$pathFormatStats['windows_misses']++;

        $result = str_replace('/', '\\', $path);

        if (count(self::$pathFormatCache) < self::$pathFormatCacheMaxSize) {
            self::$pathFormatCache[$cacheKey] = $result;
        } else {
            $removeCount = (int)(self::$pathFormatCacheMaxSize * 0.1);
            self::$pathFormatCache = array_slice(self::$pathFormatCache, $removeCount, null, true);
            self::$pathFormatCache[$cacheKey] = $result;
        }

        return $result;
    }

    /**
     * Converts a Windows-style path to a Unix-style path with caching.
     * Unix-style paths use forward slashes (/) as separators.
     *
     * Performance optimization: Caches results to avoid redundant string replacements
     * for frequently used paths (e.g., root paths, bin paths).
     *
     * @param   string  $path  The Windows-style path to convert.
     *
     * @return string Returns the converted Unix-style path.
     */
    public static function formatUnixPath($path)
    {
        if (empty($path)) {
            return $path;
        }

        $cacheKey = 'u_' . $path;
        if (isset(self::$pathFormatCache[$cacheKey])) {
            self::$pathFormatStats['unix_hits']++;
            return self::$pathFormatCache[$cacheKey];
        }

        self::$pathFormatStats['unix_misses']++;

        $result = str_replace('\\', '/', $path);

        if (count(self::$pathFormatCache) < self::$pathFormatCacheMaxSize) {
            self::$pathFormatCache[$cacheKey] = $result;
        } else {
            $removeCount = (int)(self::$pathFormatCacheMaxSize * 0.1);
            self::$pathFormatCache = array_slice(self::$pathFormatCache, $removeCount, null, true);
            self::$pathFormatCache[$cacheKey] = $result;
        }

        return $result;
    }

    /**
     * Gets path format cache statistics.
     * Useful for monitoring cache effectiveness and tuning cache size.
     *
     * @return array Array containing unix_hits, unix_misses, windows_hits, windows_misses.
     */
    public static function getPathFormatStats()
    {
        return self::$pathFormatStats;
    }

    /**
     * Clears the path format cache.
     * Useful when paths change or for testing purposes.
     *
     * @return void
     */
    public static function clearPathFormatCache()
    {
        self::$pathFormatCache = [];
        self::$pathFormatStats = [
            'unix_hits'      => 0,
            'unix_misses'    => 0,
            'windows_hits'   => 0,
            'windows_misses' => 0,
        ];
    }

    /**
     * Gets the current size of the path format cache.
     *
     * @return int Number of cached path conversions.
     */
    public static function getPathFormatCacheSize()
    {
        return count(self::$pathFormatCache);
    }

    /**
     * Retrieves the application binaries registry key from the registry or generates it.
     *
     * @param   bool  $fromRegistry  Determines whether to retrieve the key from the registry or generate it.
     *
     * @return string Returns the application binaries registry key.
     */
    public static function getAppBinsRegKey($fromRegistry = true)
    {
        global $bearsamppRegistry;

        if ($fromRegistry) {
            $value = $bearsamppRegistry->getValue(
                Registry::HKEY_LOCAL_MACHINE,
                Registry::ENV_KEY,
                Registry::APP_BINS_REG_ENTRY
            );
            Log::debug('App reg key from registry: ' . $value);
        } else {
            global $bearsamppBins, $bearsamppTools;
            $value = '';
            if ($bearsamppBins->getApache()->isEnable()) {
                $value .= $bearsamppBins->getApache()->getSymlinkPath() . '/bin;';
            }
            if ($bearsamppBins->getPhp()->isEnable()) {
                $value .= $bearsamppBins->getPhp()->getSymlinkPath() . ';';
                $value .= $bearsamppBins->getPhp()->getSymlinkPath() . '/pear;';
                $value .= $bearsamppBins->getPhp()->getSymlinkPath() . '/deps;';
                $value .= $bearsamppBins->getPhp()->getSymlinkPath() . '/imagick;';
            }
            if ($bearsamppBins->getNodejs()->isEnable()) {
                $value .= $bearsamppBins->getNodejs()->getSymlinkPath() . ';';
            }
            if ($bearsamppTools->getComposer()->isEnable()) {
                $value .= $bearsamppTools->getComposer()->getSymlinkPath() . ';';
                $value .= $bearsamppTools->getComposer()->getSymlinkPath() . '/vendor/bin;';
            }
            if ($bearsamppTools->getGhostscript()->isEnable()) {
                $value .= $bearsamppTools->getGhostscript()->getSymlinkPath() . '/bin;';
            }
            if ($bearsamppTools->getGit()->isEnable()) {
                $value .= $bearsamppTools->getGit()->getSymlinkPath() . '/bin;';
            }
            if ($bearsamppTools->getNgrok()->isEnable()) {
                $value .= $bearsamppTools->getNgrok()->getSymlinkPath() . ';';
            }
            if ($bearsamppTools->getPerl()->isEnable()) {
                $value .= $bearsamppTools->getPerl()->getSymlinkPath() . '/perl/site/bin;';
                $value .= $bearsamppTools->getPerl()->getSymlinkPath() . '/perl/bin;';
                $value .= $bearsamppTools->getPerl()->getSymlinkPath() . '/c/bin;';
            }
            if ($bearsamppTools->getPython()->isEnable()) {
                $value .= $bearsamppTools->getPython()->getSymlinkPath() . '/bin;';
            }
            if ($bearsamppTools->getRuby()->isEnable()) {
                $value .= $bearsamppTools->getRuby()->getSymlinkPath() . '/bin;';
            }
            $value = self::formatWindowsPath($value);
            Log::debug('Generated app bins reg key: ' . $value);
        }

        return $value;
    }

    /**
     * Sets the application binaries registry key.
     *
     * @param   string  $value  The value for the application binaries registry key.
     *
     * @return bool True on success, false on failure.
     */
    public static function setAppBinsRegKey($value)
    {
        global $bearsamppRegistry;

        return $bearsamppRegistry->setStringValue(
            Registry::HKEY_LOCAL_MACHINE,
            Registry::ENV_KEY,
            Registry::APP_BINS_REG_ENTRY,
            $value
        );
    }

    /**
     * Retrieves the application path from the registry.
     *
     * @return mixed The value of the application path registry key or false on error.
     */
    public static function getAppPathRegKey()
    {
        global $bearsamppRegistry;

        return $bearsamppRegistry->getValue(
            Registry::HKEY_LOCAL_MACHINE,
            Registry::ENV_KEY,
            Registry::APP_PATH_REG_ENTRY
        );
    }

    /**
     * Sets the application path in the registry.
     *
     * @param   string  $value  The new value for the application path.
     *
     * @return bool True on success, false on failure.
     */
    public static function setAppPathRegKey($value)
    {
        global $bearsamppRegistry;

        return $bearsamppRegistry->setStringValue(
            Registry::HKEY_LOCAL_MACHINE,
            Registry::ENV_KEY,
            Registry::APP_PATH_REG_ENTRY,
            $value
        );
    }

    /**
     * Retrieves the system path from the registry.
     *
     * @return mixed The value of the system path registry key or false on error.
     */
    public static function getSysPathRegKey()
    {
        global $bearsamppRegistry;

        return $bearsamppRegistry->getValue(
            Registry::HKEY_LOCAL_MACHINE,
            Registry::ENV_KEY,
            Registry::SYSPATH_REG_ENTRY
        );
    }

    /**
     * Sets the system path in the registry.
     *
     * @param   string  $value  The new value for the system path.
     *
     * @return bool True on success, false on failure.
     */
    public static function setSysPathRegKey($value)
    {
        global $bearsamppRegistry;

        return $bearsamppRegistry->setExpandStringValue(
            Registry::HKEY_LOCAL_MACHINE,
            Registry::ENV_KEY,
            Registry::SYSPATH_REG_ENTRY,
            $value
        );
    }

    /**
     * Retrieves the path for the startup link file.
     *
     * @return string The full path to the startup link file.
     */
    public static function getStartupLnkPath()
    {
        $startupPath = Win32Native::getSpecialFolderPath('Startup');
        return $startupPath ? $startupPath . '/' . APP_TITLE . '.lnk' : false;
    }

    /**
     * Finds the path to the PowerShell executable in the Windows System32 directory.
     *
     * @return string|false Returns the path to powershell.exe if found, otherwise false.
     */
    public static function getPowerShellPath()
    {
        if (is_dir('C:\Windows\System32\WindowsPowerShell')) {
            return Util::findFile('C:\Windows\System32\WindowsPowerShell', 'powershell.exe');
        }

        return false;
    }

    /**
     * Retrieves a list of directories and file types to scan within the BEARSAMPP environment.
     *
     * This method compiles an array of paths from various components of the BEARSAMPP stack,
     * including Apache, PHP, MySQL, MariaDB, PostgreSQL, Node.js, Composer, PowerShell,
     * Python and Ruby. Each path entry includes the directory path, file types to include
     * in the scan, and whether the scan should be recursive.
     *
     * The method uses global variables to access the root paths of each component. It then
     * dynamically fetches specific subdirectories using the `Util::getFolderList` method and
     * constructs an array of path specifications.
     *
     * Each path specification is an associative array with the following keys:
     * - 'path': The full directory path to scan.
     * - 'includes': An array of file extensions or filenames to include in the scan.
     * - 'recursive': A boolean indicating whether the scan should include subdirectories.
     *
     * The method is designed to be used for setting up scans of configuration files and other
     * important files within the BEARSAMPP environment, possibly for purposes like configuration
     * management, backup, or security auditing.
     *
     * @return array An array of associative arrays, each containing 'path', 'includes', and 'recursive' keys.
     */
    public static function getPathsToScan()
    {
        global $bearsamppRoot, $bearsamppCore, $bearsamppBins, $bearsamppApps, $bearsamppTools;
        $paths = array();

        // Alias
        $paths[] = array(
            'path'      => $bearsamppRoot->getAliasPath(),
            'includes'  => array(''),
            'recursive' => false
        );

        // Vhosts
        $paths[] = array(
            'path'      => $bearsamppRoot->getVhostsPath(),
            'includes'  => array(''),
            'recursive' => false
        );

        // OpenSSL
        $paths[] = array(
            'path'      => $bearsamppCore->getOpenSslPath(),
            'includes'  => array('openssl.cfg'),
            'recursive' => false
        );

        // Homepage
        $paths[] = array(
            'path'      => $bearsamppCore->getResourcesPath() . '/homepage',
            'includes'  => array('alias.conf'),
            'recursive' => false
        );

        // Apache
        $folderList = Util::getFolderList($bearsamppBins->getApache()->getRootPath());
        foreach ($folderList as $folder) {
            $paths[] = array(
                'path'      => $bearsamppBins->getApache()->getRootPath() . '/' . $folder,
                'includes'  => array('.ini', '.conf'),
                'recursive' => true
            );
        }

        // PHP
        $folderList = Util::getFolderList($bearsamppBins->getPhp()->getRootPath());
        foreach ($folderList as $folder) {
            $paths[] = array(
                'path'      => $bearsamppBins->getPhp()->getRootPath() . '/' . $folder,
                'includes'  => array('.php', '.bat', '.ini', '.reg', '.inc'),
                'recursive' => true
            );
        }

        // MySQL
        $folderList = Util::getFolderList($bearsamppBins->getMysql()->getRootPath());
        foreach ($folderList as $folder) {
            $paths[] = array(
                'path'      => $bearsamppBins->getMysql()->getRootPath() . '/' . $folder,
                'includes'  => array('my.ini'),
                'recursive' => false
            );
        }

        // MariaDB
        $folderList = Util::getFolderList($bearsamppBins->getMariadb()->getRootPath());
        foreach ($folderList as $folder) {
            $paths[] = array(
                'path'      => $bearsamppBins->getMariadb()->getRootPath() . '/' . $folder,
                'includes'  => array('my.ini'),
                'recursive' => false
            );
            // Also scan data directory for my.ini (created during initialization)
            $dataPath = $bearsamppBins->getMariadb()->getRootPath() . '/' . $folder . '/data';
            if (is_dir($dataPath)) {
                $paths[] = array(
                    'path'      => $dataPath,
                    'includes'  => array('my.ini'),
                    'recursive' => false
                );
            }
        }

        // PostgreSQL
        $folderList = Util::getFolderList($bearsamppBins->getPostgresql()->getRootPath());
        foreach ($folderList as $folder) {
            $paths[] = array(
                'path'      => $bearsamppBins->getPostgresql()->getRootPath() . '/' . $folder,
                'includes'  => array( '.conf', '.bat', '.ber'),
                'recursive' => true
            );
        }

        // Node.js
        $folderList = Util::getFolderList($bearsamppBins->getNodejs()->getRootPath());
        foreach ($folderList as $folder) {
            $paths[] = array(
                'path'      => $bearsamppBins->getNodejs()->getRootPath() . '/' . $folder . '/etc',
                'includes'  => array('npmrc'),
                'recursive' => true
            );
            $paths[] = array(
                'path'      => $bearsamppBins->getNodejs()->getRootPath() . '/' . $folder . '/node_modules/npm',
                'includes'  => array('npmrc'),
                'recursive' => false
            );
        }

        // Composer
        $folderList = Util::getFolderList($bearsamppTools->getComposer()->getRootPath());
        foreach ($folderList as $folder) {
            $paths[] = array(
                'path'      => $bearsamppTools->getComposer()->getRootPath() . '/' . $folder,
                'includes'  => array('giscus.json'),
                'recursive' => false
            );
        }

        // PowerShell
        $folderList = Util::getFolderList($bearsamppTools->getPowerShell()->getRootPath());
        foreach ($folderList as $folder) {
            $paths[] = array(
                'path'      => $bearsamppTools->getPowerShell()->getRootPath() . '/' . $folder,
                'includes'  => array('console.xml', '.ini', '.btm'),
                'recursive' => true
            );
        }

        // Python
        $folderList = Util::getFolderList($bearsamppTools->getPython()->getRootPath());
        foreach ($folderList as $folder) {
            $paths[] = array(
                'path'      => $bearsamppTools->getPython()->getRootPath() . '/' . $folder . '/bin',
                'includes'  => array('.bat'),
                'recursive' => false
            );
            $paths[] = array(
                'path'      => $bearsamppTools->getPython()->getRootPath() . '/' . $folder . '/settings',
                'includes'  => array('winpython.ini'),
                'recursive' => false
            );
        }

        // Ruby
        $folderList = Util::getFolderList($bearsamppTools->getRuby()->getRootPath());
        foreach ($folderList as $folder) {
            $paths[] = array(
                'path'      => $bearsamppTools->getRuby()->getRootPath() . '/' . $folder . '/bin',
                'includes'  => array('!.dll', '!.exe'),
                'recursive' => false
            );
        }

        return $paths;
    }

    /**
     * Gets the NSSM environment paths.
     *
     * @return string The NSSM environment paths string.
     */
    public static function getNssmEnvPaths()
    {
        global $bearsamppBins, $bearsamppTools;

        $paths = '';

        // Add paths for enabled bins
        if ($bearsamppBins->getApache()->isEnable()) {
            $paths .= $bearsamppBins->getApache()->getSymlinkPath() . '/bin;';
        }
        if ($bearsamppBins->getPhp()->isEnable()) {
            $paths .= $bearsamppBins->getPhp()->getSymlinkPath() . ';';
            $paths .= $bearsamppBins->getPhp()->getSymlinkPath() . '/pear;';
            $paths .= $bearsamppBins->getPhp()->getSymlinkPath() . '/deps;';
            $paths .= $bearsamppBins->getPhp()->getSymlinkPath() . '/imagick;';
        }
        if ($bearsamppBins->getNodejs()->isEnable()) {
            $paths .= $bearsamppBins->getNodejs()->getSymlinkPath() . ';';
        }
        if ($bearsamppTools->getComposer()->isEnable()) {
            $paths .= $bearsamppTools->getComposer()->getSymlinkPath() . ';';
            $paths .= $bearsamppTools->getComposer()->getSymlinkPath() . '/vendor/bin;';
        }
        if ($bearsamppTools->getGhostscript()->isEnable()) {
            $paths .= $bearsamppTools->getGhostscript()->getSymlinkPath() . '/bin;';
        }
        if ($bearsamppTools->getGit()->isEnable()) {
            $paths .= $bearsamppTools->getGit()->getSymlinkPath() . '/bin;';
        }
        if ($bearsamppTools->getNgrok()->isEnable()) {
            $paths .= $bearsamppTools->getNgrok()->getSymlinkPath() . ';';
        }
        if ($bearsamppTools->getPerl()->isEnable()) {
            $paths .= $bearsamppTools->getPerl()->getSymlinkPath() . '/perl/site/bin;';
            $paths .= $bearsamppTools->getPerl()->getSymlinkPath() . '/perl/bin;';
            $paths .= $bearsamppTools->getPerl()->getSymlinkPath() . '/c/bin;';
        }
        if ($bearsamppTools->getPython()->isEnable()) {
            $paths .= $bearsamppTools->getPython()->getSymlinkPath() . '/bin;';
        }
        if ($bearsamppTools->getRuby()->isEnable()) {
            $paths .= $bearsamppTools->getRuby()->getSymlinkPath() . '/bin;';
        }

        return self::formatWindowsPath($paths);
    }
}
