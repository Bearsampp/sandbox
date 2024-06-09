<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionLaunchStartup
 *
 * This class is responsible for managing the application's launch startup settings. It allows enabling or disabling
 * the application's ability to launch at startup based on the provided arguments. The class interacts with the global
 * configuration object to update the relevant settings and utilizes utility functions to modify the system's startup
 * behavior.
 */
class ActionLaunchStartup
{
    /**
     * Constructor for the ActionLaunchStartup class.
     *
     * This constructor initializes the class and manages the application's launch startup settings. It uses the provided
     * arguments to determine whether to enable or disable the application's launch at startup feature. The constructor
     * starts the loading process, updates the launch startup configuration based on the provided argument, and modifies
     * the system's startup settings accordingly.
     *
     * @param array $args An array of arguments where the first element should be either Config::ENABLED or Config::DISABLED
     *                    to indicate the desired launch startup setting.
     */
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
