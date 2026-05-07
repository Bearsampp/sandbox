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
}
