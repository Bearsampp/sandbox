<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionRestart
 *
 * This class handles the restart action within the Bearsampp application. When an instance of this class is created,
 * it displays a message box with information about the restart process.
 */
class ActionRestart
{
    /**
     * Constructor for the ActionRestart class.
     *
     * This method initializes the restart action by displaying a message box with information about the restart process.
     * It uses global variables to access language settings and the Winbinder instance for UI interactions.
     *
     * @param array $args An array of arguments passed to the constructor, which can be used to customize the restart action.
     */
    public function __construct($args)
    {
        global $bearsamppLang, $bearsamppWinbinder;

        // Display an informational message box about the restart process.
        $bearsamppWinbinder->messageBoxInfo(
            sprintf($bearsamppLang->getValue(Lang::RESTART_TEXT), APP_TITLE),
            $bearsamppLang->getValue(Lang::RESTART_TITLE)
        );
    }
}
