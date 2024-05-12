<?php
/*
 * Copyright (c) 2-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: @author@
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

class TplAppOnline
{
    const ACTION = 'status';

    public static function process()
    {
        global $bearsamppConfig, $bearsamppLang;

        return TplApp::getActionMulti(
            self::ACTION, array($bearsamppConfig->isOnline() ? Config::DISABLED : Config::ENABLED),
            array($bearsamppConfig->isOnline() ? $bearsamppLang->getValue(Lang::MENU_PUT_OFFLINE) : $bearsamppLang->getValue(Lang::MENU_PUT_ONLINE)),
            false, get_called_class()
        );
    }

    public static function getActionStatus($status)
    {
        return TplApp::getActionRun(Action::SWITCH_ONLINE, array($status)) . PHP_EOL .
            TplService::getActionRestart(BinApache::SERVICE_NAME) . PHP_EOL .
            TplService::getActionRestart(BinFilezilla::SERVICE_NAME) . PHP_EOL .
            TplAppReload::getActionReload() . PHP_EOL;
    }
}
