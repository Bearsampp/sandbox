<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionSwitchLang handles the action of switching the application's language.
 */
class ActionSwitchLang
{
    /**
     * Constructor for the ActionSwitchLang class.
     *
     * This constructor takes an array of arguments, checks if the first argument is set and not empty,
     * and updates the application's language configuration accordingly.
     *
     * @param array $args An array of arguments where the first element is expected to be the new language code.
     */
    public function __construct($args)
    {
        global $bearsamppConfig;

        if (isset($args[0]) && !empty($args[0])) {
            $bearsamppConfig->replace(Config::CFG_LANG, $args[0]);
        }
    }
}
