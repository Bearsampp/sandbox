<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionSwitchLogsVerbose
 *
 * This class handles the action of switching the verbosity level of logs in the Bearsampp application.
 * It updates the configuration setting for log verbosity based on the provided arguments.
 */
class ActionSwitchLogsVerbose
{
    /**
     * Constructor for the ActionSwitchLogsVerbose class.
     *
     * This constructor takes an array of arguments and updates the log verbosity level in the configuration
     * if the first argument is a valid verbosity level (an integer between 0 and 3 inclusive).
     *
     * @param array $args An array of arguments where the first element is expected to be the verbosity level.
     */
    public function __construct($args)
    {
        global $bearsamppConfig;

        // Check if the first argument is set, is numeric, and within the valid range for verbosity levels.
        if (isset($args[0]) && is_numeric($args[0]) && $args[0] >= 0 && $args[0] <= 3) {
            // Update the configuration setting for log verbosity.
            $bearsamppConfig->replace(Config::CFG_LOGS_VERBOSE, $args[0]);
        }
    }
}
