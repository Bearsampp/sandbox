<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionService
 *
 * This class handles various actions related to services such as creating, starting, stopping, restarting, installing, and removing services.
 * It interacts with different service binaries like Mailhog, Memcached, Apache, MySQL, MariaDB, PostgreSQL, and Filezilla.
 *
 * Constants:
 * - CREATE: Action to create a service.
 * - START: Action to start a service.
 * - STOP: Action to stop a service.
 * - RESTART: Action to restart a service.
 * - INSTALL: Action to install a service.
 * - REMOVE: Action to remove a service.
 *
 * Methods:
 * - __construct($args): Constructor that initializes the service action based on the provided arguments.
 * - create($service): Creates the specified service.
 * - start($bin, $syntaxCheckCmd): Starts the specified service binary, optionally performing a syntax check.
 * - stop($service): Stops the specified service.
 * - restart($bin, $syntaxCheckCmd): Restarts the specified service binary, optionally performing a syntax check.
 * - install($bin, $port, $syntaxCheckCmd): Installs the specified service binary on the given port, optionally performing a syntax check.
 * - remove($service, $name): Removes the specified service.
 *
 * @package Bearsampp
 * @license GNU General Public License version 3 or later; see LICENSE.txt
 * @author Bear
 * @link https://bearsampp.com
 * @link https://github.com/Bearsampp
 */
class ActionService
{
    const CREATE = 'create';
    const START = 'start';
    const STOP = 'stop';
    const RESTART = 'restart';

    const INSTALL = 'install';
    const REMOVE = 'remove';

    /**
     * Constructor to initialize the service action based on the provided arguments.
     *
     * @param array $args Arguments to specify the service name and action.
     */
    public function __construct($args)
    {
        global $bearsamppBins;
        Util::startLoading();

        // reload bins
        $bearsamppBins->reload();

        if (isset($args[0]) && !empty($args[0]) && isset($args[1]) && !empty($args[1])) {
            $sName = $args[0];
            $bin = null;
            $port = 0;
            $syntaxCheckCmd = null;

            if ($sName == BinMailhog::SERVICE_NAME) {
                $bin = $bearsamppBins->getMailhog();
                $port = $bin->getSmtpPort();
            } elseif ($sName == BinMemcached::SERVICE_NAME) {
                $bin = $bearsamppBins->getMemcached();
                $port = $bin->getPort();
            } elseif ($sName == BinApache::SERVICE_NAME) {
                $bin = $bearsamppBins->getApache();
                $port = $bin->getPort();
                $syntaxCheckCmd = BinApache::CMD_SYNTAX_CHECK;
            } elseif ($sName == BinMysql::SERVICE_NAME) {
                $bin = $bearsamppBins->getMysql();
                $port = $bin->getPort();
                $syntaxCheckCmd = BinMysql::CMD_SYNTAX_CHECK;
            } elseif ($sName == BinMariadb::SERVICE_NAME) {
                $bin = $bearsamppBins->getMariadb();
                $port = $bin->getPort();
                $syntaxCheckCmd = BinMariadb::CMD_SYNTAX_CHECK;
            } elseif ($sName == BinPostgresql::SERVICE_NAME) {
                $bin = $bearsamppBins->getPostgresql();
                $port = $bin->getPort();
            } elseif ($sName == BinFilezilla::SERVICE_NAME) {
                $bin = $bearsamppBins->getFilezilla();
                $port = $bin->getPort();
            }

            $name = $bin->getName();
            $service = $bin->getService();

            if (!empty($service) && $service instanceof Win32Service) {
                if ($args[1] == self::CREATE) {
                    $this->create($service);
                } elseif ($args[1] == self::START) {
                    $this->start($bin, $syntaxCheckCmd);
                } elseif ($args[1] == self::STOP) {
                    $this->stop($service);
                } elseif ($args[1] == self::RESTART) {
                    $this->restart($bin, $syntaxCheckCmd);
                } elseif ($args[1] == self::INSTALL) {
                    if (!empty($port)) {
                        $this->install($bin, $port, $syntaxCheckCmd);
                    }
                } elseif ($args[1] == self::REMOVE) {
                    $this->remove($service, $name);
                }
            }
        }

        Util::stopLoading();
    }

    /**
     * Creates the specified service.
     *
     * @param Win32Service $service The service to be created.
     */
    private function create($service)
    {
        $service->create();
    }

    /**
     * Starts the specified service binary, optionally performing a syntax check.
     *
     * @param mixed $bin The service binary to be started.
     * @param string|null $syntaxCheckCmd The command for syntax checking, if applicable.
     */
    private function start($bin, $syntaxCheckCmd)
    {
        Util::startService($bin, $syntaxCheckCmd, true);
    }

    /**
     * Stops the specified service.
     *
     * @param Win32Service $service The service to be stopped.
     */
    private function stop($service)
    {
        $service->stop();
    }

    /**
     * Restarts the specified service binary, optionally performing a syntax check.
     *
     * @param mixed $bin The service binary to be restarted.
     * @param string|null $syntaxCheckCmd The command for syntax checking, if applicable.
     */
    private function restart($bin, $syntaxCheckCmd)
    {
        if ($bin->getService()->stop()) {
            $this->start($bin, $syntaxCheckCmd);
        }
    }

    /**
     * Installs the specified service binary on the given port, optionally performing a syntax check.
     *
     * @param mixed $bin The service binary to be installed.
     * @param int $port The port on which the service will be installed.
     * @param string|null $syntaxCheckCmd The command for syntax checking, if applicable.
     */
    private function install($bin, $port, $syntaxCheckCmd)
    {
        Util::installService($bin, $port, $syntaxCheckCmd, true);
    }

    /**
     * Removes the specified service.
     *
     * @param Win32Service $service The service to be removed.
     * @param string $name The name of the service to be removed.
     */
    private function remove($service, $name)
    {
        Util::removeService($service, $name);
    }
}
