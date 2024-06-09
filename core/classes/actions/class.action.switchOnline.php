<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionSwitchOnline
 *
 * This class handles the switching of the application between online and offline modes.
 * It updates the configuration settings and refreshes the configurations for Apache, aliases, virtual hosts, and FileZilla.
 */
class ActionSwitchOnline
{
    /**
     * Constructor for ActionSwitchOnline.
     *
     * @param array $args An array of arguments where the first element indicates whether to enable (Config::ENABLED) or disable (Config::DISABLED) online mode.
     */
    public function __construct($args)
    {
        global $bearsamppConfig;

        if (isset($args[0]) && $args[0] == Config::ENABLED || $args[0] == Config::DISABLED) {
            Util::startLoading();
            $putOnline = $args[0] == Config::ENABLED;

            $this->switchApache($putOnline);
            $this->switchAlias($putOnline);
            $this->switchVhosts($putOnline);
            $this->switchFilezilla($putOnline);
            $bearsamppConfig->replace(Config::CFG_ONLINE, $args[0]);
        }
    }

    /**
     * Switches the Apache configuration between online and offline modes.
     *
     * @param bool $putOnline True to enable online mode, false to enable offline mode.
     */
    private function switchApache($putOnline)
    {
        global $bearsamppBins;
        $bearsamppBins->getApache()->refreshConf($putOnline);
    }

    /**
     * Switches the alias configuration between online and offline modes.
     *
     * @param bool $putOnline True to enable online mode, false to enable offline mode.
     */
    private function switchAlias($putOnline)
    {
        global $bearsamppBins;
        $bearsamppBins->getApache()->refreshAlias($putOnline);
    }

    /**
     * Switches the virtual hosts configuration between online and offline modes.
     *
     * @param bool $putOnline True to enable online mode, false to enable offline mode.
     */
    private function switchVhosts($putOnline)
    {
        global $bearsamppBins;
        $bearsamppBins->getApache()->refreshVhosts($putOnline);
    }

    /**
     * Switches the FileZilla configuration between online and offline modes.
     *
     * @param bool $putOnline True to enable online mode, false to enable offline mode.
     */
    private function switchFilezilla($putOnline)
    {
        global $bearsamppBins;

        if ($putOnline) {
            $bearsamppBins->getFilezilla()->setConf(array(
                BinFilezilla::CFG_IP_FILTER_ALLOWED => '*',
                BinFilezilla::CFG_IP_FILTER_DISALLOWED => '',
            ));
        } else {
            $bearsamppBins->getFilezilla()->setConf(array(
                BinFilezilla::CFG_IP_FILTER_ALLOWED => '127.0.0.1 ::1',
                BinFilezilla::CFG_IP_FILTER_DISALLOWED => '*',
            ));
        }
    }
}
