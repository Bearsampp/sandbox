<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionCheckPort
 *
 * This class is responsible for checking the port status of various services (Apache, MySQL, MariaDB, PostgreSQL, FileZilla, MailHog, Memcached)
 * based on the provided arguments. It utilizes the global `$bearsamppBins` object to interact with these services.
 */
class ActionCheckPort
{
    /**
     * ActionCheckPort constructor.
     *
     * Initializes the port check action for the specified service.
     *
     * @param array $args An array of arguments where:
     *                    - $args[0] (string): The name of the service (e.g., 'Apache', 'MySQL').
     *                    - $args[1] (int): The port number to check.
     *                    - $args[2] (optional): A flag indicating whether SSL is enabled (for services that support SSL).
     *
     * The constructor checks if the provided service name matches any of the known services and then calls the appropriate method to check the port status.
     * If SSL is enabled (indicated by the presence and non-emptiness of $args[2]), it will check the port with SSL.
     *
     * Example usage:
     * $action = new ActionCheckPort(['Apache', 80, true]);
     *
     * Global variables:
     * @global object $bearsamppBins An object containing instances of various service classes (Apache, MySQL, etc.).
     */
    public function __construct($args)
    {
        global $bearsamppBins;

        if (isset($args[0]) && !empty($args[0]) && isset($args[1]) && !empty($args[1])) {
            $ssl = isset($args[2]) && !empty($args[2]);
            if ($args[0] == $bearsamppBins->getApache()->getName()) {
                $bearsamppBins->getApache()->checkPort($args[1], $ssl, true);
            } elseif ($args[0] == $bearsamppBins->getMysql()->getName()) {
                $bearsamppBins->getMysql()->checkPort($args[1], true);
            } elseif ($args[0] == $bearsamppBins->getMariadb()->getName()) {
                $bearsamppBins->getMariadb()->checkPort($args[1], true);
            } elseif ($args[0] == $bearsamppBins->getPostgresql()->getName()) {
                $bearsamppBins->getPostgresql()->checkPort($args[1], true);
            } elseif ($args[0] == $bearsamppBins->getFilezilla()->getName()) {
                $bearsamppBins->getFilezilla()->checkPort($args[1], $ssl, true);
            } elseif ($args[0] == $bearsamppBins->getMailhog()->getName()) {
                $bearsamppBins->getMailhog()->checkPort($args[1], true);
            } elseif ($args[0] == $bearsamppBins->getMemcached()->getName()) {
                $bearsamppBins->getMemcached()->checkPort($args[1], true);
            }
        }
    }
}
