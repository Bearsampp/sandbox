<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class Action handles the execution of various actions based on command line arguments.
 *
 * This class provides a mechanism to process and execute different actions specified via command line arguments.
 * It supports a wide range of actions such as adding aliases, changing ports, debugging services, and more.
 * The actions are defined as constants within the class.
 *
 * Usage:
 * - Instantiate the class: $action = new Action();
 * - Process the action: $action->process();
 * - Call a specific action: $action->call('actionName', $actionArgs);
 *
 * Example:
 * ```php
 * $action = new Action();
 * $action->process();
 * ```
 */
class Action
{
    // Constants for different actions
    const ABOUT = 'about';
    const ADD_ALIAS = 'addAlias';
    const ADD_VHOST = 'addVhost';
    const CHANGE_BROWSER = 'changeBrowser';
    const CHANGE_DB_ROOT_PWD = 'changeDbRootPwd';
    const CHANGE_PORT = 'changePort';
    const CHECK_PORT = 'checkPort';
    const CHECK_VERSION = 'checkVersion';
    const CLEAR_FOLDERS = 'clearFolders';
    const DEBUG_APACHE = 'debugApache';
    const DEBUG_MARIADB = 'debugMariadb';
    const DEBUG_MYSQL = 'debugMysql';
    const DEBUG_POSTGRESQL = 'debugPostgresql';
    const EDIT_ALIAS = 'editAlias';
    const EDIT_VHOST = 'editVhost';
    const ENABLE = 'enable';
    const EXEC = 'exec';
    const GEN_SSL_CERTIFICATE = 'genSslCertificate';
    const LAUNCH_STARTUP = 'launchStartup';
    const MANUAL_RESTART = 'manualRestart';
    const LOADING = 'loading';
    const QUIT = 'quit';
    const REFRESH_REPOS = 'refreshRepos';
    const REFRESH_REPOS_STARTUP = 'refreshReposStartup';
    const RELOAD = 'reload';
    const RESTART = 'restart';
    const SERVICE = 'service';
    const STARTUP = 'startup';
    const SWITCH_APACHE_MODULE = 'switchApacheModule';
    const SWITCH_LANG = 'switchLang';
    const SWITCH_LOGS_VERBOSE = 'switchLogsVerbose';
    const SWITCH_PHP_EXTENSION = 'switchPhpExtension';
    const SWITCH_PHP_PARAM = 'switchPhpParam';
    const SWITCH_ONLINE = 'switchOnline';
    const SWITCH_VERSION = 'switchVersion';

    const EXT = 'ext';

    /**
     * @var mixed Holds the current action instance.
     */
    private $current;

    /**
     * Constructor for the Action class.
     *
     * Initializes a new instance of the Action class.
     */
    public function __construct()
    {
    }

    /**
     * Processes the action based on command line arguments.
     *
     * This method checks if an action exists in the command line arguments, cleans the arguments,
     * and then attempts to instantiate and execute the corresponding action class.
     *
     * @return void
     */
    public function process()
    {
        if ($this->exists()) {
            $action = Util::cleanArgv(1);
            $actionClass = 'Action' . ucfirst($action);

            $args = array();
            foreach ($_SERVER['argv'] as $key => $arg) {
                if ($key > 1) {
                    $args[] = $action == self::EXT ? $arg : base64_decode($arg);
                }
            }

            $this->current = null;
            if (class_exists($actionClass)) {
                Util::logDebug('Start ' . $actionClass);
                $this->current = new $actionClass($args);
            }
        }
    }

    /**
     * Calls a specific action by name with optional arguments.
     *
     * This method allows for the direct invocation of a specific action class by its name.
     * It logs the start of the action and then instantiates the action class with the provided arguments.
     *
     * @param string $actionName The name of the action to call.
     * @param mixed $actionArgs Optional arguments for the action.
     * @return void
     */
    public function call($actionName, $actionArgs = null)
    {
        $actionClass = 'Action' . ucfirst($actionName);
        if (class_exists($actionClass)) {
            Util::logDebug('Start ' . $actionClass);
            new $actionClass($actionArgs);
        }
    }

    /**
     * Checks if the action exists in the command line arguments.
     *
     * This method verifies if the command line arguments contain a valid action.
     * It checks if the `argv` array is set and if the second element (the action) is not empty.
     *
     * @return bool Returns true if the action exists, false otherwise.
     */
    public function exists()
    {
        return isset($_SERVER['argv'])
            && isset($_SERVER['argv'][1])
            && !empty($_SERVER['argv'][1]);
    }
}
