<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

class ActionLaunchStartup
{
    public function __construct($args)
    {
        global $bearsamppConfig;

        if (isset($args[0])) {
            Util::startLoading();
            $launchStartup = $args[0] == Config::ENABLED;
            if ($launchStartup) {
                Util::enableLaunchStartup();
            } else {
                Util::disableLaunchStartup();
            }
            $bearsamppConfig->replace(Config::CFG_LAUNCH_STARTUP, $args[0]);
        }
    }
}
