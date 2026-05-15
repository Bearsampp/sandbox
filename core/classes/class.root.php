<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class Root
 *
 * This class represents the root of the Bearsampp application. It handles the initialization,
 * configuration, and management of various components and settings within the application.
 */
class Root
{
    const ERROR_HANDLER = 'errorHandler';

    public $path;
    private $procs;
    private $isRoot;

    /**
     * Constructs a Root object with the specified root path.
     *
     * @param string $rootPath The root path of the application.
     */
    public function __construct($rootPath)
    {
        require_once dirname(__FILE__) . '/class.path.php';
        Path::setRootPath($rootPath);
        $this->path = Path::getRootPath();
        $this->isRoot = strpos($_SERVER['PHP_SELF'], 'root.php') !== false;
    }

    /**
     * Registers the application components and initializes error handling.
     */
    public function register()
    {
        // Params
        set_time_limit(0);
        clearstatcache();

        // External classes required for error handling and path utilities
        // Path class is already required in constructor

        // Error log
        $this->initErrorHandling();

        // External classes
        require_once Path::getCorePath() . '/classes/class.log.php';
        require_once Path::getCorePath() . '/classes/class.util.php';
        require_once Path::getCorePath() . '/classes/class.util.string.php';
        Log::init();

        // Autoloader
        require_once Path::getCorePath() . '/classes/class.autoloader.php';
        $bearsamppAutoloader = new Autoloader();
        $bearsamppAutoloader->register();

        // Load
        self::loadCore();
        self::loadConfig();
        self::loadLang();
        self::loadOpenSsl();
        self::loadBins();
        self::loadTools();
        self::loadApps();
        self::loadWinbinder();
        self::loadRegistry();
        self::loadHomepage();
        Log::separator();

        // Init
        if ($this->isRoot) {
            $this->procs = Win32Ps::getListProcs();
        }
    }

    /**
     * Initializes error handling settings for the application.
     */
    public function initErrorHandling()
    {
        error_reporting(-1);
        ini_set('error_log', Path::getErrorLogFilePath());
        ini_set('display_errors', '1');
        set_error_handler(array($this, self::ERROR_HANDLER));
    }

    /**
     * Removes the custom error handling, reverting to the default PHP error handling.
     */
    public function removeErrorHandling()
    {
        error_reporting(0);
        ini_set('error_log', null);
        ini_set('display_errors', '0');
        restore_error_handler();
    }

    /**
     * Retrieves the list of processes.
     *
     * @return array The list of processes.
     */
    public function getProcs()
    {
        return $this->procs;
    }

    /**
     * Checks if the current script is executed from the root path.
     *
     * @return bool True if executed from the root, false otherwise.
     */
    public function isRoot()
    {
        return $this->isRoot;
    }

    /**
     * Gets the name of the process.
     *
     * @return string The process name.
     */
    public function getProcessName()
    {
        return 'bearsampp';
    }

    /**
     * Loads the core components of the application.
     */
    public static function loadCore()
    {
        global $bearsamppCore;
        $bearsamppCore = new Core();
    }

    /**
     * Loads the configuration settings of the application.
     */
    public static function loadConfig()
    {
        global $bearsamppConfig;
        $bearsamppConfig = new Config();
    }

    /**
     * Loads the language settings of the application.
     */
    public static function loadLang()
    {
        global $bearsamppLang;
        $bearsamppLang = new LangProc();
    }

    /**
     * Loads the OpenSSL settings of the application.
     */
    public static function loadOpenSsl()
    {
        global $bearsamppOpenSsl;
        $bearsamppOpenSsl = new OpenSsl();
    }

    /**
     * Loads the binary components of the application.
     */
    public static function loadBins()
    {
        global $bearsamppBins;
        $bearsamppBins = new Bins();
    }

    /**
     * Loads the tools components of the application.
     */
    public static function loadTools()
    {
        global $bearsamppTools;
        $bearsamppTools = new Tools();
    }

    /**
     * Loads the apps components of the application.
     */
    public static function loadApps()
    {
        global $bearsamppApps;
        $bearsamppApps = new Apps();
    }

    /**
     * Loads the Winbinder extension if available.
     */
    public static function loadWinbinder()
    {
        global $bearsamppWinbinder;
        if (extension_loaded('winbinder')) {
            $bearsamppWinbinder = new WinBinder();
        }
    }

    /**
     * Loads the registry settings of the application.
     */
    public static function loadRegistry()
    {
        global $bearsamppRegistry;
        $bearsamppRegistry = new Registry();
    }

    /**
     * Loads the homepage settings of the application.
     */
    public static function loadHomepage()
    {
        global $bearsamppHomepage;
        $bearsamppHomepage = new Homepage();
    }

    /**
     * Handles errors and logs them to the error log file.
     *
     * @param int $errno The level of the error raised.
     * @param string $errstr The error message.
     * @param string $errfile The filename that the error was raised in.
     * @param int $errline The line number the error was raised at.
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (error_reporting() === 0) {
            return;
        }

        $errfile = Path::formatUnixPath($errfile);
        $errfile = str_replace(Path::getRootPath(), '', $errfile);

        if (!defined('E_DEPRECATED')) {
            define('E_DEPRECATED', 8192);
        }

        $errNames = array(
            E_ERROR             => 'E_ERROR',
            E_WARNING           => 'E_WARNING',
            E_PARSE             => 'E_PARSE',
            E_NOTICE            => 'E_NOTICE',
            E_CORE_ERROR        => 'E_CORE_ERROR',
            E_CORE_WARNING      => 'E_CORE_WARNING',
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
            E_USER_ERROR        => 'E_USER_ERROR',
            E_USER_WARNING      => 'E_USER_WARNING',
            E_USER_NOTICE       => 'E_USER_NOTICE',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED        => 'E_DEPRECATED',
        );

        $content = '[' . date('Y-m-d H:i:s', time()) . '] ';
        $content .= $errNames[$errno] . ' ';
        $content .= $errstr . ' in ' .  $errfile;
        $content .= ' on line ' . $errline . PHP_EOL;
        $content .= self::debugStringBacktrace() . PHP_EOL;

        file_put_contents(Path::getErrorLogFilePath(), $content, FILE_APPEND);
    }

    /**
     * Generates a debug backtrace string.
     *
     * @return string The debug backtrace.
     */
    private static function debugStringBacktrace()
    {
        ob_start();
        debug_print_backtrace();
        $trace = ob_get_contents();
        ob_end_clean();

        $trace = preg_replace('/^#0\s+Root::debugStringBacktrace[^\n]*\n/', '', $trace, 1);
        $trace = preg_replace('/^#1\s+isRoot->errorHandler[^\n]*\n/', '', $trace, 1);
        $trace = preg_replace_callback('/^#(\d+)/m', 'debugStringPregReplace', $trace);
        return $trace;
    }
}

    /**
     * Adjusts the trace number in debug backtrace.
     *
     * @param array $match The matches from the regular expression.
     * @return string The adjusted trace number.
     */
    function debugStringPregReplace($match)
    {
        return '  #' . ($match[1] - 1);
    }
