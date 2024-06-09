<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

class TplAppOnline
{
    const ACTION = 'status';

    /**
     * Processes the online status action.
     *
     * This method generates a multi-action string based on the current online status of the application.
     * It toggles the online status and returns the appropriate action string.
     *
     * @global Config $bearsamppConfig The configuration object for the application.
     * @global Lang $bearsamppLang The language object for the application.
     * @return array An array containing the call string and the section content.
     */
    public static function process()
    {
        global $bearsamppConfig, $bearsamppLang;

        return TplApp::getActionMulti(
            self::ACTION,
            array($bearsamppConfig->isOnline() ? Config::DISABLED : Config::ENABLED),
            array($bearsamppConfig->isOnline() ? $bearsamppLang->getValue(Lang::MENU_PUT_OFFLINE) : $bearsamppLang->getValue(Lang::MENU_PUT_ONLINE)),
            false,
            get_called_class()
        );
    }

    /**
     * Generates the action string to switch the online status.
     *
     * This method generates a run action string to switch the online status of the application.
     * It also includes actions to restart the Apache and FileZilla services, and to reload the application.
     *
     * @param int $status The status to switch to (enabled or disabled).
     * @return string The generated action string.
     */
    public static function getActionStatus($status)
    {
        return TplApp::getActionRun(Action::SWITCH_ONLINE, array($status)) . PHP_EOL .
            TplService::getActionRestart(BinApache::SERVICE_NAME) . PHP_EOL .
            TplService::getActionRestart(BinFilezilla::SERVICE_NAME) . PHP_EOL .
            TplAppReload::getActionReload() . PHP_EOL;
    }
}
