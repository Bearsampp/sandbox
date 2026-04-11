<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class Log
 *
 * Centralised logging subsystem for the Bearsampp application.
 * All methods are static; no instantiation is required.
 *
 * Log levels (lowest → highest verbosity):
 *   ERROR, WARNING, INFO, DEBUG, TRACE
 *
 * Writes are buffered and flushed in batches (default 50 entries) to
 * minimise file I/O. Errors are always flushed immediately. The buffer
 * is also flushed automatically on shutdown via register_shutdown_function.
 *
 * Usage:
 * ```
 * Log::error('Something went wrong');
 * Log::debug('Variable value: ' . $var);
 * Log::trace('Entering method foo()');
 * ```
 */
class Log
{
    const ERROR   = 'ERROR';
    const WARNING = 'WARNING';
    const INFO    = 'INFO';
    const DEBUG   = 'DEBUG';
    const TRACE   = 'TRACE';

    /**
     * Log buffer for batching log writes.
     * @var array
     */
    private static $buffer = [];

    /**
     * Maximum number of log entries to buffer before flushing.
     * @var int
     */
    private static $bufferSize = 50;

    /**
     * Flag to track if the shutdown handler has been registered.
     * @var bool
     */
    private static $shutdownRegistered = false;

    /**
     * Statistics for monitoring log buffer effectiveness.
     * @var array
     */
    private static $stats = [
        'buffered' => 0,
        'flushed'  => 0,
        'writes'   => 0,
    ];

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Logs a TRACE-level message.
     *
     * @param mixed       $data The data to log.
     * @param string|null $file Optional file path to log to.
     */
    public static function trace($data, $file = null)
    {
        self::write($data, self::TRACE, $file);
    }

    /**
     * Logs a DEBUG-level message.
     *
     * @param mixed       $data The data to log.
     * @param string|null $file Optional file path to log to.
     */
    public static function debug($data, $file = null)
    {
        self::write($data, self::DEBUG, $file);
    }

    /**
     * Logs an INFO-level message.
     *
     * @param mixed       $data The data to log.
     * @param string|null $file Optional file path to log to.
     */
    public static function info($data, $file = null)
    {
        self::write($data, self::INFO, $file);
    }

    /**
     * Logs a WARNING-level message.
     *
     * @param mixed       $data The data to log.
     * @param string|null $file Optional file path to log to.
     */
    public static function warning($data, $file = null)
    {
        self::write($data, self::WARNING, $file);
    }

    /**
     * Logs an ERROR-level message. Always flushed immediately.
     *
     * @param mixed       $data The data to log.
     * @param string|null $file Optional file path to log to.
     */
    public static function error($data, $file = null)
    {
        self::write($data, self::ERROR, $file);
    }

    /**
     * Appends a separator line to all log files to improve readability between sessions.
     */
    public static function separator()
    {
        global $bearsamppRoot;

        if (!isset($bearsamppRoot) || $bearsamppRoot === null) {
            return;
        }

        $logs = [
            $bearsamppRoot->getLogFilePath(),
            $bearsamppRoot->getErrorLogFilePath(),
            $bearsamppRoot->getServicesLogFilePath(),
            $bearsamppRoot->getRegistryLogFilePath(),
            $bearsamppRoot->getStartupLogFilePath(),
            $bearsamppRoot->getBatchLogFilePath(),
            $bearsamppRoot->getWinbinderLogFilePath(),
        ];

        $separator = '========================================================================================' . PHP_EOL;
        foreach ($logs as $log) {
            if (!file_exists($log)) {
                continue;
            }
            $content = @file_get_contents($log);
            $alreadySeparated = class_exists('Util', false)
                ? Util::endWith($content, $separator)
                : (substr($content, -strlen($separator)) === $separator);
            if ($content !== false && !$alreadySeparated) {
                file_put_contents($log, $separator, FILE_APPEND);
            }
        }
    }

    /**
     * Logs the initialisation of a class instance at TRACE level.
     *
     * @param object $classInstance The instance being initialised.
     */
    public static function initClass($classInstance)
    {
        self::trace('Init ' . get_class($classInstance));
    }

    /**
     * Logs the reloading of a class instance at TRACE level.
     *
     * @param object $classInstance The instance being reloaded.
     */
    public static function reloadClass($classInstance)
    {
        self::trace('Reload ' . get_class($classInstance));
    }

    // -------------------------------------------------------------------------
    // Buffer management (public so external code can flush/tune if needed)
    // -------------------------------------------------------------------------

    /**
     * Flushes all buffered log entries to disk.
     * Groups entries by destination file to minimise file operations.
     */
    public static function flushBuffer()
    {
        if (empty(self::$buffer)) {
            return;
        }

        global $bearsamppCore;

        if (!isset($bearsamppCore) || $bearsamppCore === null) {
            foreach (self::$buffer as $entry) {
                error_log('[' . $entry['type'] . '] ' . $entry['data']);
            }
            self::$buffer = [];
            return;
        }

        $byFile = [];
        foreach (self::$buffer as $entry) {
            $byFile[$entry['file']][] = $entry;
        }

        $failed = [];
        foreach ($byFile as $file => $entries) {
            $content = '';
            foreach ($entries as $entry) {
                $content .= '[' . date('Y-m-d H:i:s', $entry['time']) . '] # ' .
                            APP_TITLE . ' ' . $bearsamppCore->getAppVersion() . ' # ' .
                            $entry['type'] . ': ' . $entry['data'] . PHP_EOL;
            }
            if (file_put_contents($file, $content, FILE_APPEND | LOCK_EX) === false) {
                error_log('[Log::flushBuffer] Failed to write to log file: ' . $file);
                foreach ($entries as $entry) {
                    error_log('[' . $entry['type'] . '] ' . $entry['data']);
                    $failed[] = $entry;
                }
            } else {
                self::$stats['flushed'] += count($entries);
                self::$stats['writes']++;
            }
        }

        // Retain entries that could not be written so the next flush can retry them.
        self::$buffer = $failed;
    }

    /**
     * Returns current buffer statistics (buffered, flushed, writes).
     *
     * @return array
     */
    public static function getStats()
    {
        return self::$stats;
    }

    /**
     * Sets the buffer size (1–1000).
     *
     * @param int $size
     */
    public static function setBufferSize($size)
    {
        if ($size > 0 && $size <= 1000) {
            self::$bufferSize = $size;
        }
    }

    /**
     * Returns the current buffer size.
     *
     * @return int
     */
    public static function getBufferSize()
    {
        return self::$bufferSize;
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    /**
     * Core write method. Adds the entry to the buffer and flushes when needed.
     *
     * @param mixed       $data
     * @param string      $type  One of the Log::* level constants.
     * @param string|null $file
     */
    private static function write($data, $type, $file = null)
    {
        global $bearsamppRoot, $bearsamppCore, $bearsamppConfig;

        // Fallback before globals are initialised (very early boot)
        if (!isset($bearsamppRoot) || $bearsamppRoot === null ||
            !isset($bearsamppCore) || $bearsamppCore === null ||
            !isset($bearsamppConfig) || $bearsamppConfig === null) {
            error_log('[' . $type . '] ' . $data);
            return;
        }

        if (!class_exists('Config', false)) {
            error_log('[' . $type . '] ' . $data);
            return;
        }

        if (!self::$shutdownRegistered) {
            register_shutdown_function([self::class, 'flushBuffer']);
            self::$shutdownRegistered = true;
        }

        $file = $file ?? ($type === self::ERROR
            ? $bearsamppRoot->getErrorLogFilePath()
            : $bearsamppRoot->getLogFilePath());

        if (!$bearsamppRoot->isRoot()) {
            $file = $bearsamppRoot->getHomepageLogFilePath();
        }

        $verbose = [
            Config::VERBOSE_SIMPLE => $type === self::ERROR || $type === self::WARNING,
            Config::VERBOSE_REPORT => $type === self::ERROR || $type === self::WARNING || $type === self::INFO,
            Config::VERBOSE_DEBUG  => in_array($type, [self::ERROR, self::WARNING, self::INFO, self::DEBUG]),
            Config::VERBOSE_TRACE  => true,
        ];

        $logsVerbose = $bearsamppConfig->getLogsVerbose();
        if (!isset($verbose[$logsVerbose]) || !$verbose[$logsVerbose]) {
            return;
        }

        self::$buffer[] = [
            'file' => $file,
            'data' => $data,
            'type' => $type,
            'time' => time(),
        ];
        self::$stats['buffered']++;

        if (count(self::$buffer) >= self::$bufferSize || $type === self::ERROR) {
            self::flushBuffer();
        }
    }
}
