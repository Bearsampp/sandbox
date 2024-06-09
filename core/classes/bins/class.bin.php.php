<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class BinPhp
 *
 * This class represents a PHP binary module and provides methods to manage its configuration and settings.
 * It extends the Module class and includes constants for various PHP configuration settings.
 *
 * @package Bearsampp
 * @license GNU General Public License version 3 or later; see LICENSE.txt
 * @author  Bear
 * @link    https://bearsampp.com
 * @link    https://github.com/Bearsampp
 */
class BinPhp extends Module
{
    // Constants for configuration keys
    const ROOT_CFG_ENABLE = 'phpEnable';
    const ROOT_CFG_VERSION = 'phpVersion';

    // Constants for local configuration keys
    const LOCAL_CFG_CLI_EXE = 'phpCliExe';
    const LOCAL_CFG_CLI_SILENT_EXE = 'phpCliSilentExe';
    const LOCAL_CFG_CONF = 'phpConf';
    const LOCAL_CFG_PEAR_EXE = 'phpPearExe';

    // Constants for PHP INI settings
    const INI_SHORT_OPEN_TAG = 'short_open_tag';
    const INI_ASP_TAGS = 'asp_tags';
    const INI_Y2K_COMPLIANCE = 'y2k_compliance';
    const INI_OUTPUT_BUFFERING = 'output_buffering';
    const INI_ZLIB_OUTPUT_COMPRESSION = 'zlib.output_compression';
    const INI_IMPLICIT_FLUSH = 'implicit_flush';
    const INI_ALLOW_CALL_TIME_PASS_REFERENCE = 'allow_call_time_pass_reference';
    const INI_SAFE_MODE = 'safe_mode';
    const INI_SAFE_MODE_GID = 'safe_mode_gid';
    const INI_EXPOSE_PHP = 'expose_php';
    const INI_DISPLAY_ERRORS = 'display_errors';
    const INI_DISPLAY_STARTUP_ERRORS = 'display_startup_errors';
    const INI_LOG_ERRORS = 'log_errors';
    const INI_IGNORE_REPEATED_ERRORS = 'ignore_repeated_errors';
    const INI_IGNORE_REPEATED_SOURCE = 'ignore_repeated_source';
    const INI_REPORT_MEMLEAKS = 'report_memleaks';
    const INI_TRACK_ERRORS = 'track_errors';
    const INI_HTML_ERRORS = 'html_errors';
    const INI_REGISTER_GLOBALS = 'register_globals';
    const INI_REGISTER_LONG_ARRAYS = 'register_long_arrays';
    const INI_REGISTER_ARGC_ARGV = 'register_argc_argv';
    const INI_AUTO_GLOBALS_JIT = 'auto_globals_jit';
    const INI_MAGIC_QUOTES_GPC = 'magic_quotes_gpc';
    const INI_MAGIC_QUOTES_RUNTIME = 'magic_quotes_runtime';
    const INI_MAGIC_QUOTES_SYBASE = 'magic_quotes_sybase';
    const INI_ENABLE_DL = 'enable_dl';
    const INI_CGI_FORCE_REDIRECT = 'cgi.force_redirect';
    const INI_CGI_FIX_PATHINFO = 'cgi.fix_pathinfo';
    const INI_FILE_UPLOADS = 'file_uploads';
    const INI_ALLOW_URL_FOPEN = 'allow_url_fopen';
    const INI_ALLOW_URL_INCLUDE = 'allow_url_include';
    const INI_PHAR_READONLY = 'phar.readonly';
    const INI_PHAR_REQUIRE_HASH = 'phar.require_hash';
    const INI_DEFINE_SYSLOG_VARIABLES = 'define_syslog_variables';
    const INI_MAIL_ADD_X_HEADER = 'mail.add_x_header';
    const INI_SQL_SAFE_MODE = 'sql.safe_mode';
    const INI_ODBC_ALLOW_PERSISTENT = 'odbc.allow_persistent';
    const INI_ODBC_CHECK_PERSISTENT = 'odbc.check_persistent';
    const INI_MYSQL_ALLOW_LOCAL_INFILE = 'mysql.allow_local_infile';
    const INI_MYSQL_ALLOW_PERSISTENT = 'mysql.allow_persistent';
    const INI_MYSQL_TRACE_MODE = 'mysql.trace_mode';
    const INI_MYSQLI_ALLOW_PERSISTENT = 'mysqli.allow_persistent';
    const INI_MYSQLI_RECONNECT = 'mysqli.reconnect';
    const INI_MYSQLND_COLLECT_STATISTICS = 'mysqlnd.collect_statistics';
    const INI_MYSQLND_COLLECT_MEMORY_STATISTICS = 'mysqlnd.collect_memory_statistics';
    const INI_PGSQL_ALLOW_PERSISTENT = 'pgsql.allow_persistent';
    const INI_PGSQL_AUTO_RESET_PERSISTENT = 'pgsql.auto_reset_persistent';
    const INI_SYBCT_ALLOW_PERSISTENT = 'sybct.allow_persistent';
    const INI_SESSION_USE_COOKIES = 'session.use_cookies';
    const INI_SESSION_USE_ONLY_COOKIES = 'session.use_only_cookies';
    const INI_SESSION_AUTO_START = 'session.auto_start';
    const INI_SESSION_COOKIE_HTTPONLY = 'session.cookie_httponly';
    const INI_SESSION_BUG_COMPAT_42 = 'session.bug_compat_42';
    const INI_SESSION_BUG_COMPAT_WARN = 'session.bug_compat_warn';
    const INI_SESSION_USE_TRANS_SID = 'session.use_trans_sid';
    const INI_MSSQL_ALLOW_PERSISTENT = 'mssql.allow_persistent';
    const INI_MSSQL_COMPATIBILITY_MODE = 'mssql.compatability_mode';
    const INI_MSSQL_SECURE_CONNECTION = 'mssql.secure_connection';
    const INI_TIDY_CLEAN_OUTPUT = 'tidy.clean_output';
    const INI_SOAP_WSDL_CACHE_ENABLED = 'soap.wsdl_cache_enabled';
    const INI_XDEBUG_REMOTE_ENABLE = 'xdebug.remote_enable';
    const INI_XDEBUG_PROFILER_ENABLE = 'xdebug.profiler_enable';
    const INI_XDEBUG_PROFILER_ENABLE_TRIGGER = 'xdebug.profiler_enable_trigger';
    const INI_APC_ENABLED = 'apc.enabled';
    const INI_APC_INCLUDE_ONCE_OVERRIDE = 'apc.include_once_override';
    const INI_APC_CANONICALIZE = 'apc.canonicalize';
    const INI_APC_STAT = 'apc.stat';

    private $apacheConf;
    private $errorLog;

    private $cliExe;
    private $cliSilentExe;
    private $conf;
    private $pearExe;

    /**
     * Constructor for the BinPhp class.
     *
     * @param   string  $id    The ID of the PHP module.
     * @param   string  $type  The type of the PHP module.
     */
    public function __construct($id, $type)
    {
        Util::logInitClass( $this );
        $this->reload( $id, $type );
    }

    /**
     * Reloads the configuration and settings for the PHP module.
     *
     * @param   string|null  $id    The ID of the PHP module.
     * @param   string|null  $type  The type of the PHP module.
     */
    public function reload($id = null, $type = null)
    {
        global $bearsamppRoot, $bearsamppConfig, $bearsamppBins, $bearsamppLang;
        Util::logReloadClass( $this );

        $this->name    = $bearsamppLang->getValue( Lang::PHP );
        $this->version = $bearsamppConfig->getRaw( self::ROOT_CFG_VERSION );
        parent::reload( $id, $type );

        $this->enable     = $this->enable && $bearsamppConfig->getRaw( self::ROOT_CFG_ENABLE );
        $this->apacheConf = $bearsamppBins->getApache()->getCurrentPath() . '/' . $this->apacheConf; //FIXME: Useful ?
        $this->errorLog   = $bearsamppRoot->getLogsPath() . '/php_error.log';

        if ( $this->bearsamppConfRaw !== false ) {
            $this->cliExe       = $this->symlinkPath . '/' . $this->bearsamppConfRaw[self::LOCAL_CFG_CLI_EXE];
            $this->cliSilentExe = $this->symlinkPath . '/' . $this->bearsamppConfRaw[self::LOCAL_CFG_CLI_SILENT_EXE];
            $this->conf         = $this->symlinkPath . '/' . $this->bearsamppConfRaw[self::LOCAL_CFG_CONF];
            $this->pearExe      = $this->symlinkPath . '/' . $this->bearsamppConfRaw[self::LOCAL_CFG_PEAR_EXE];
        }

        if ( !$this->enable ) {
            Util::logInfo( $this->name . ' is not enabled!' );

            return;
        }
        if ( !is_dir( $this->currentPath ) ) {
            Util::logError( sprintf( $bearsamppLang->getValue( Lang::ERROR_FILE_NOT_FOUND ), $this->name . ' ' . $this->version, $this->currentPath ) );

            return;
        }
        if ( !is_dir( $this->symlinkPath ) ) {
            Util::logError( sprintf( $bearsamppLang->getValue( Lang::ERROR_FILE_NOT_FOUND ), $this->name . ' ' . $this->version, $this->symlinkPath ) );

            return;
        }
        if ( !is_file( $this->bearsamppConf ) ) {
            Util::logError( sprintf( $bearsamppLang->getValue( Lang::ERROR_CONF_NOT_FOUND ), $this->name . ' ' . $this->version, $this->bearsamppConf ) );

            return;
        }
        if ( !is_file( $this->cliExe ) ) {
            Util::logError( sprintf( $bearsamppLang->getValue( Lang::ERROR_EXE_NOT_FOUND ), $this->name . ' ' . $this->version, $this->cliExe ) );
        }
        if ( !is_file( $this->cliSilentExe ) ) {
            Util::logError( sprintf( $bearsamppLang->getValue( Lang::ERROR_EXE_NOT_FOUND ), $this->name . ' ' . $this->version, $this->cliSilentExe ) );
        }
        if ( !is_file( $this->conf ) ) {
            Util::logError( sprintf( $bearsamppLang->getValue( Lang::ERROR_CONF_NOT_FOUND ), $this->name . ' ' . $this->version, $this->conf ) );
        }
        if ( !is_file( $this->pearExe ) ) {
            Util::logError( sprintf( $bearsamppLang->getValue( Lang::ERROR_EXE_NOT_FOUND ), $this->name . ' ' . $this->version, $this->pearExe ) );
        }
    }

    /**
     * Switches the PHP version to the specified version.
     *
     * @param   string  $version     The version to switch to.
     * @param   bool    $showWindow  Whether to show a window with the result.
     *
     * @return bool True if the switch was successful, false otherwise.
     */
    public function switchVersion($version, $showWindow = false)
    {
        Util::logDebug( 'Switch ' . $this->name . ' version to ' . $version );

        return $this->updateConfig( $version, 0, $showWindow );
    }

    /**
     * Updates the configuration for the PHP module.
     *
     * @param   string|null  $version     The version to update to.
     * @param   int          $sub         The sub-level for logging.
     * @param   bool         $showWindow  Whether to show a window with the result.
     *
     * @return bool True if the update was successful, false otherwise.
     */
    protected function updateConfig($version = null, $sub = 0, $showWindow = false)
    {
        global $bearsamppLang, $bearsamppBins, $bearsamppApps, $bearsamppWinbinder;

        if ( !$this->enable ) {
            return true;
        }

        $version = $version == null ? $this->version : $version;
        Util::logDebug( ($sub > 0 ? str_repeat( ' ', 2 * $sub ) : '') . 'Update ' . $this->name . ' ' . $version . ' config' );

        $boxTitle = sprintf( $bearsamppLang->getValue( Lang::SWITCH_VERSION_TITLE ), $this->getName(), $version );

        //$phpPath = str_replace('php' . $this->getVersion(), 'php' . $version, $this->getCurrentPath());
        $conf          = str_replace( 'php' . $this->getVersion(), 'php' . $version, $this->getConf() );
        $bearsamppConf = str_replace( 'php' . $this->getVersion(), 'php' . $version, $this->bearsamppConf );

        $tsDll = $this->getTsDll( $version );
        //$apacheShortVersion = substr(str_replace('.', '', $bearsamppBins->getApache()->getVersion()), 0, 2);
        //$apachePhpModuleName = $tsDll !== false ? substr($tsDll, 0, 4) . '_module' : null;
        $apachePhpModulePath = $this->getApacheModule( $bearsamppBins->getApache()->getVersion(), $version );

        Util::logDebug( ($sub > 0 ? str_repeat( ' ', 2 * $sub ) : '') . 'PHP TsDll found: ' . $tsDll );
        Util::logDebug( ($sub > 0 ? str_repeat( ' ', 2 * $sub ) : '') . 'PHP Apache module found: ' . $apachePhpModulePath );

        if ( !file_exists( $conf ) || !file_exists( $bearsamppConf ) ) {
            Util::logError( 'bearsampp config files not found for ' . $this->getName() . ' ' . $version );
            if ( $showWindow ) {
                $bearsamppWinbinder->messageBoxError(
                    sprintf( $bearsamppLang->getValue( Lang::BEARSAMPP_CONF_NOT_FOUND_ERROR ), $this->getName() . ' ' . $version ),
                    $boxTitle
                );
            }

            return false;
        }

        $bearsamppConfRaw = parse_ini_file( $bearsamppConf );
        if ( $bearsamppConfRaw === false || !isset( $bearsamppConfRaw[self::ROOT_CFG_VERSION] ) || $bearsamppConfRaw[self::ROOT_CFG_VERSION] != $version ) {
            Util::logError( 'bearsampp config file malformed for ' . $this->getName() . ' ' . $version );
            if ( $showWindow ) {
                $bearsamppWinbinder->messageBoxError(
                    sprintf( $bearsamppLang->getValue( Lang::BEARSAMPP_CONF_MALFORMED_ERROR ), $this->getName() . ' ' . $version ),
                    $boxTitle
                );
            }

            return false;
        }

        if ( $tsDll === false || $apachePhpModulePath === false ) {
            Util::logDebug( $this->getName() . ' ' . $version . ' does not seem to be compatible with Apache ' . $bearsamppBins->getApache()->getVersion() );
            if ( $showWindow ) {
                $bearsamppWinbinder->messageBoxError(
                    sprintf( $bearsamppLang->getValue( Lang::PHP_INCPT ), $version, $bearsamppBins->getApache()->getVersion() ),
                    $boxTitle
                );
            }

            return false;
        }

        // bearsampp.conf
        $this->setVersion( $version );

        // conf
        Util::replaceInFile( $this->getConf(), array(
            '/^mysql.default_port\s=\s(\d+)/'  => 'mysql.default_port = ' . $bearsamppBins->getMysql()->getPort(),
            '/^mysqli.default_port\s=\s(\d+)/' => 'mysqli.default_port = ' . $bearsamppBins->getMysql()->getPort()
        ) );

        // apache
        $bearsamppBins->getApache()->update( $sub + 1 );

        // phpmyadmin
        $bearsamppApps->getPhpmyadmin()->update( $sub + 1 );

        return true;
    }

    /**
     * Retrieves the settings for the PHP module.
     *
     * @return array An associative array of settings categories and their respective settings.
     */
    public function getSettings()
    {
        return array(
            'Language options'           => array(
                'Short open tag'                 => self::INI_SHORT_OPEN_TAG,
                'ASP-style tags'                 => self::INI_ASP_TAGS,
                'Year 2000 compliance'           => self::INI_Y2K_COMPLIANCE,
                'Output buffering'               => self::INI_OUTPUT_BUFFERING,
                'Zlib output compression'        => self::INI_ZLIB_OUTPUT_COMPRESSION,
                'Implicit flush'                 => self::INI_IMPLICIT_FLUSH,
                'Allow call time pass reference' => self::INI_ALLOW_CALL_TIME_PASS_REFERENCE,
                'Safe mode'                      => self::INI_SAFE_MODE,
                'Safe mode GID'                  => self::INI_SAFE_MODE_GID,
            ),
            'Miscellaneous'              => array(
                'Expose PHP' => self::INI_EXPOSE_PHP,
            ),
            'Error handling and logging' => array(
                'Display errors'         => self::INI_DISPLAY_ERRORS,
                'Display startup errors' => self::INI_DISPLAY_STARTUP_ERRORS,
                'Log errors'             => self::INI_LOG_ERRORS,
                'Ignore repeated errors' => self::INI_IGNORE_REPEATED_ERRORS,
                'Ignore repeated source' => self::INI_IGNORE_REPEATED_SOURCE,
                'Report memory leaks'    => self::INI_REPORT_MEMLEAKS,
                'Track errors'           => self::INI_TRACK_ERRORS,
                'HTML errors'            => self::INI_HTML_ERRORS,
            ),
            'Data Handling'              => array(
                'Register globals'          => self::INI_REGISTER_GLOBALS,
                'Register long arrays'      => self::INI_REGISTER_LONG_ARRAYS,
                'Register argc argv'        => self::INI_REGISTER_ARGC_ARGV,
                'Auto globals just in time' => self::INI_AUTO_GLOBALS_JIT,
                'Magic quotes gpc'          => self::INI_MAGIC_QUOTES_GPC,
                'Magic quotes runtime'      => self::INI_MAGIC_QUOTES_RUNTIME,
                'Magic quotes Sybase'       => self::INI_MAGIC_QUOTES_SYBASE,
            ),
            'Paths and Directories'      => array(
                'Enable dynamic loading' => self::INI_ENABLE_DL,
                'CGI force redirect'     => self::INI_CGI_FORCE_REDIRECT,
                'CGI fix path info'      => self::INI_CGI_FIX_PATHINFO,
            ),
            'File uploads'               => array(
                'File uploads' => self::INI_FILE_UPLOADS,
            ),
            'Fopen wrappers'             => array(
                'Allow url fopen'   => self::INI_ALLOW_URL_FOPEN,
                'Allow url include' => self::INI_ALLOW_URL_INCLUDE,
            ),
            'Module settings'            => array(
                'Phar'                => array(
                    'Read only'    => self::INI_PHAR_READONLY,
                    'Require hash' => self::INI_PHAR_REQUIRE_HASH,
                ),
                'Syslog'              => array(
                    'Define syslog variables' => self::INI_DEFINE_SYSLOG_VARIABLES,
                ),
                'Mail'                => array(
                    'Add X-PHP-Originating-Script' => self::INI_MAIL_ADD_X_HEADER,
                ),
                'SQL'                 => array(
                    'Safe mode' => self::INI_SQL_SAFE_MODE,
                ),
                'ODBC'                => array(
                    'Allow persistent' => self::INI_ODBC_ALLOW_PERSISTENT,
                    'Check persistent' => self::INI_ODBC_CHECK_PERSISTENT,
                ),
                'MySQL'               => array(
                    'Allow local infile' => self::INI_MYSQL_ALLOW_LOCAL_INFILE,
                    'Allow persistent'   => self::INI_MYSQL_ALLOW_PERSISTENT,
                    'Trace mode'         => self::INI_MYSQL_TRACE_MODE,
                ),
                'MySQLi'              => array(
                    'Allow persistent' => self::INI_MYSQLI_ALLOW_PERSISTENT,
                    'Reconnect'        => self::INI_MYSQLI_RECONNECT,
                ),
                'MySQL Native Driver' => array(
                    'Collect statistics'        => self::INI_MYSQLND_COLLECT_STATISTICS,
                    'Collect memory statistics' => self::INI_MYSQLND_COLLECT_MEMORY_STATISTICS,
                ),
                'PostgresSQL'         => array(
                    'Allow persistent'      => self::INI_PGSQL_ALLOW_PERSISTENT,
                    'Auto reset persistent' => self::INI_PGSQL_AUTO_RESET_PERSISTENT,
                ),
                'Sybase-CT'           => array(
                    'Allow persistent' => self::INI_SYBCT_ALLOW_PERSISTENT,
                ),
                'Session'             => array(
                    'Use cookies'        => self::INI_SESSION_USE_COOKIES,
                    'Use only cookies'   => self::INI_SESSION_USE_ONLY_COOKIES,
                    'Auto start'         => self::INI_SESSION_AUTO_START,
                    'Cookie HTTP only'   => self::INI_SESSION_COOKIE_HTTPONLY,
                    'Bug compat 42'      => self::INI_SESSION_BUG_COMPAT_42,
                    'Bug compat warning' => self::INI_SESSION_BUG_COMPAT_WARN,
                    'Use trans sid'      => self::INI_SESSION_USE_TRANS_SID,
                ),
                'MSSQL'               => array(
                    'Allow persistent'   => self::INI_MSSQL_ALLOW_PERSISTENT,
                    'Compatibility mode' => self::INI_MSSQL_COMPATIBILITY_MODE,
                    'Secure connection'  => self::INI_MSSQL_SECURE_CONNECTION,
                ),
                'Tidy'                => array(
                    'Clean output' => self::INI_TIDY_CLEAN_OUTPUT,
                ),
                'SOAP'                => array(
                    'WSDL cache enabled' => self::INI_SOAP_WSDL_CACHE_ENABLED,
                ),
                'XDebug'              => array(
                    'Remote enable'           => self::INI_XDEBUG_REMOTE_ENABLE,
                    'Profiler enable'         => self::INI_XDEBUG_PROFILER_ENABLE,
                    'Profiler enable trigger' => self::INI_XDEBUG_PROFILER_ENABLE_TRIGGER,
                ),
                'APC'                 => array(
                    'Enabled'               => self::INI_APC_ENABLED,
                    'Include once override' => self::INI_APC_INCLUDE_ONCE_OVERRIDE,
                    'Canonicalize'          => self::INI_APC_CANONICALIZE,
                    'Stat'                  => self::INI_APC_STAT,
                ),
            ),
        );
    }

    /**
     * Retrieves the settings values for various PHP configuration options.
     *
     * @return array An associative array where the keys are configuration option names and the values are arrays containing possible values.
     */
    public function getSettingsValues()
    {
        // Method implementation
    }

    /**
     * Checks if a specific PHP setting is active based on the configuration file.
     *
     * @param   string  $name  The name of the PHP setting to check.
     *
     * @return bool True if the setting is active, false otherwise.
     */
    public function isSettingActive($name)
    {
        // Method implementation
    }

    /**
     * Checks if a specific PHP setting exists in the configuration file.
     *
     * @param   string  $name  The name of the PHP setting to check.
     *
     * @return bool True if the setting exists, false otherwise.
     */
    public function isSettingExists($name)
    {
        // Method implementation
    }

    /**
     * Retrieves a list of PHP extensions from both the configuration file and the extensions folder.
     *
     * @return array An associative array where the keys are extension names and the values are their statuses (enabled or disabled).
     */
    public function getExtensions()
    {
        // Method implementation
    }

    /**
     * Checks if a specific PHP extension is excluded from being listed.
     *
     * @param   string  $ext  The name of the PHP extension to check.
     *
     * @return bool True if the extension is excluded, false otherwise.
     */
    private function isExtensionExcluded($ext)
    {
        // Method implementation
    }

    /**
     * Retrieves a list of PHP extensions from the configuration file.
     *
     * @return array An associative array where the keys are extension names and the values are their statuses (enabled or disabled).
     */
    public function getExtensionsFromConf()
    {
        // Method implementation
    }

    /**
     * Retrieves a list of currently loaded PHP extensions.
     *
     * @return array An array of extension names that are currently loaded.
     */
    public function getExtensionsLoaded()
    {
        // Method implementation
    }

    /**
     * Retrieves a list of PHP extensions from the extensions folder.
     *
     * @return array An associative array where the keys are extension names and the values are their statuses (enabled or disabled).
     */
    public function getExtensionsFromFolder()
    {
        // Method implementation
    }

    /**
     * Retrieves the Apache module for a specific Apache and PHP version.
     *
     * @param   string       $apacheVersion  The version of Apache.
     * @param   string|null  $phpVersion     The version of PHP. If null, the current PHP version is used.
     *
     * @return string|false The path to the Apache module if found, false otherwise.
     */
    public function getApacheModule($apacheVersion, $phpVersion = null)
    {
        // Method implementation
    }

    /**
     * Retrieves the Thread Safe (TS) DLL for a specific PHP version.
     *
     * @param   string|null  $phpVersion  The version of PHP. If null, the current PHP version is used.
     *
     * @return string|false The name of the TS DLL if found, false otherwise.
     */
    public function getTsDll($phpVersion = null)
    {
        // Method implementation
    }

    /**
     * Sets the PHP version and reloads the configuration.
     *
     * @param   string  $version  The version of PHP to set.
     */
    public function setVersion($version)
    {
        // Method implementation
    }

    /**
     * Enables or disables the PHP configuration and updates related services.
     *
     * @param   int   $enabled     The status to set (enabled or disabled).
     * @param   bool  $showWindow  Whether to show a window with messages.
     */
    public function setEnable($enabled, $showWindow = false)
    {
        // Method implementation
    }

    /**
     * Retrieves the path to the error log file.
     *
     * @return string The path to the error log file.
     */
    public function getErrorLog()
    {
        // Method implementation
    }

    /**
     * Retrieves the path to the CLI executable.
     *
     * @return string The path to the CLI executable.
     */
    public function getCliExe()
    {
        // Method implementation
    }

    /**
     * Retrieves the path to the silent CLI executable.
     *
     * @return string The path to the silent CLI executable.
     */
    public function getCliSilentExe()
    {
        // Method implementation
    }

    /**
     * Retrieves the path to the PHP configuration file.
     *
     * @return string The path to the PHP configuration file.
     */
    public function getConf()
    {
        // Method implementation
    }

    /**
     * Retrieves the path to the PEAR executable.
     *
     * @return string The path to the PEAR executable.
     */
    public function getPearExe()
    {
        // Method implementation
    }

    /**
     * Retrieves the version of PEAR, optionally using a cached value.
     *
     * @param   bool  $cache  Whether to use the cached value.
     *
     * @return string|null The PEAR version if found, null otherwise.
     */
    public function getPearVersion($cache = false)
    {
        // Method implementation
    }
}
