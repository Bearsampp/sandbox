<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionEnable
 *
 * This class is responsible for enabling various services (Apache, PHP, MySQL, MariaDB, Node.js, PostgreSQL, FileZilla, MailHog, Memcached)
 * based on the provided arguments. It interacts with the global `$bearsamppBins` object to perform these actions.
 */
class ActionEnable
{
    /**
     * ActionEnable constructor.
     *
     * Initializes the ActionEnable class and enables the specified service if the arguments are valid.
     *
     * @param array $args An array of arguments where:
     *                    - $args[0] is the name of the service to be enabled.
     *                    - $args[1] is a boolean indicating whether to enable (true) or disable (false) the service.
     *
     * @global object $bearsamppBins Global object containing instances of various services.
     */
    public function __construct($args)
    {
        global $bearsamppBins;

        if (isset($args[0]) && !empty($args[0]) && isset($args[1])) {
            Util::startLoading();
            if ($args[0] == $bearsamppBins->getApache()->getName()) {
                $bearsamppBins->getApache()->setEnable($args[1], true);
            } elseif ($args[0] == $bearsamppBins->getPhp()->getName()) {
                $bearsamppBins->getPhp()->setEnable($args[1], true);
            } elseif ($args[0] == $bearsamppBins->getMysql()->getName()) {
                $bearsamppBins->getMysql()->setEnable($args[1], true);
            } elseif ($args[0] == $bearsamppBins->getMariadb()->getName()) {
                $bearsamppBins->getMariadb()->setEnable($args[1], true);
            } elseif ($args[0] == $bearsamppBins->getNodejs()->getName()) {
                $bearsamppBins->getNodejs()->setEnable($args[1], true);
            } elseif ($args[0] == $bearsamppBins->getPostgresql()->getName()) {
                $bearsamppBins->getPostgresql()->setEnable($args[1], true);
            } elseif ($args[0] == $bearsamppBins->getFilezilla()->getName()) {
                $bearsamppBins->getFilezilla()->setEnable($args[1], true);
            } elseif ($args[0] == $bearsamppBins->getMailhog()->getName()) {
                $bearsamppBins->getMailhog()->setEnable($args[1], true);
            } elseif ($args[0] == $bearsamppBins->getMemcached()->getName()) {
                $bearsamppBins->getMemcached()->setEnable($args[1], true);
            }
        }
    }
}
