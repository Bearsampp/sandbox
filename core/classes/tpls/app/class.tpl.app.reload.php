<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class TplAppReload
 *
 * This class handles the reload action for the application. It provides methods to process the reload action
 * and generate the necessary configuration and service reset commands.
 */
class TplAppReload
{
    /**
     * @var string The action name for reloading.
     */
    const ACTION = 'reload';

    /**
     * Processes the reload action.
     *
     * This method generates a multi-action string for reloading the application. It includes the reload action,
     * the localized caption for the reload action, and the glyph index for the reload icon.
     *
     * @global object $bearsamppLang The language application object.
     * @return array An array containing the call string and the section content for the reload action.
     */
    public static function process()
    {
        global $bearsamppLang;

        return TplApp::getActionMulti(
            self::ACTION, null,
            array($bearsamppLang->getValue(Lang::RELOAD), TplAestan::GLYPH_RELOAD),
            false, get_called_class()
        );
    }

    /**
     * Generates the reload action string.
     *
     * This method generates a string that includes the reload action, reset services action, and read configuration action.
     *
     * @return string The generated reload action string.
     */
    public static function getActionReload()
    {
        return TplApp::getActionRun(Action::RELOAD) . PHP_EOL .
            'Action: resetservices' . PHP_EOL .
            'Action: readconfig';
    }
}
