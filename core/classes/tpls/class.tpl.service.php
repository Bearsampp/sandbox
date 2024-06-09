<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class TplService
 *
 * This class provides methods to generate actions and menu items for managing services.
 * It includes methods to create, start, stop, restart, install, and remove services.
 * The generated actions and menu items are formatted as strings that can be used within the Bearsampp application.
 *
 * Methods:
 * - getActionCreate($sName): Generates the action to create a service.
 * - getActionStart($sName): Generates the action to start a service.
 * - getActionStop($sName): Generates the action to stop a service.
 * - getActionRestart($sName): Generates the action to restart a service.
 * - getActionInstall($sName): Generates the action to install a service.
 * - getActionRemove($sName): Generates the action to remove a service.
 * - getItemStart($sName): Generates a menu item to start a service.
 * - getItemStop($sName): Generates a menu item to stop a service.
 * - getItemRestart($sName): Generates a menu item to restart a service.
 * - getItemInstall($sName): Generates a menu item to install a service.
 * - getItemRemove($sName): Generates a menu item to remove a service.
 *
 * @package Bearsampp
 * @license GNU General Public License version 3 or later; see LICENSE.txt
 * @author Bear
 * @link https://bearsampp.com
 * @link https://github.com/Bearsampp
 */
class TplService
{
    /**
     * Generates the action to create a service.
     *
     * @param string $sName The name of the service to create.
     * @return string The formatted action string for creating the service.
     */
    public static function getActionCreate($sName)
    {
        return TplApp::getActionRun(Action::SERVICE, array($sName, ActionService::CREATE));
    }

    /**
     * Generates the action to start a service.
     *
     * @param string $sName The name of the service to start.
     * @return string The formatted action string for starting the service.
     */
    public static function getActionStart($sName)
    {
        return TplApp::getActionRun(Action::SERVICE, array($sName, ActionService::START));
    }

    /**
     * Generates the action to stop a service.
     *
     * @param string $sName The name of the service to stop.
     * @return string The formatted action string for stopping the service.
     */
    public static function getActionStop($sName)
    {
        return TplApp::getActionRun(Action::SERVICE, array($sName, ActionService::STOP));
    }

    /**
     * Generates the action to restart a service.
     *
     * @param string $sName The name of the service to restart.
     * @return string The formatted action string for restarting the service.
     */
    public static function getActionRestart($sName)
    {
        return TplApp::getActionRun(Action::SERVICE, array($sName, ActionService::RESTART));
    }

    /**
     * Generates the action to install a service.
     *
     * @param string $sName The name of the service to install.
     * @return string The formatted action string for installing the service.
     */
    public static function getActionInstall($sName)
    {
        return TplApp::getActionRun(Action::SERVICE, array($sName, ActionService::INSTALL));
    }

    /**
     * Generates the action to remove a service.
     *
     * @param string $sName The name of the service to remove.
     * @return string The formatted action string for removing the service.
     */
    public static function getActionRemove($sName)
    {
        return TplApp::getActionRun(Action::SERVICE, array($sName, ActionService::REMOVE));
    }

    /**
     * Generates a menu item to start a service.
     *
     * @param string $sName The name of the service to start.
     * @return string The formatted menu item string for starting the service.
     */
    public static function getItemStart($sName)
    {
        global $bearsamppLang;

        return TplApp::getActionRun(
            Action::SERVICE, array($sName, ActionService::START),
            array($bearsamppLang->getValue(Lang::MENU_START_SERVICE), TplAestan::GLYPH_START)
        );
    }

    /**
     * Generates a menu item to stop a service.
     *
     * @param string $sName The name of the service to stop.
     * @return string The formatted menu item string for stopping the service.
     */
    public static function getItemStop($sName)
    {
        global $bearsamppLang;

        return TplApp::getActionRun(
            Action::SERVICE, array($sName, ActionService::STOP),
            array($bearsamppLang->getValue(Lang::MENU_STOP_SERVICE), TplAestan::GLYPH_STOP)
        );
    }

    /**
     * Generates a menu item to restart a service.
     *
     * @param string $sName The name of the service to restart.
     * @return string The formatted menu item string for restarting the service.
     */
    public static function getItemRestart($sName)
    {
        global $bearsamppLang;

        return TplApp::getActionRun(
            Action::SERVICE, array($sName, ActionService::RESTART),
            array($bearsamppLang->getValue(Lang::MENU_RESTART_SERVICE), TplAestan::GLYPH_RELOAD)
        );
    }

    /**
     * Generates a menu item to install a service.
     *
     * @param string $sName The name of the service to install.
     * @return string The formatted menu item string for installing the service.
     */
    public static function getItemInstall($sName)
    {
        global $bearsamppLang;

        return TplApp::getActionRun(
            Action::SERVICE, array($sName, ActionService::INSTALL),
            array($bearsamppLang->getValue(Lang::MENU_INSTALL_SERVICE), TplAestan::GLYPH_SERVICE_INSTALL)
        );
    }

    /**
     * Generates a menu item to remove a service.
     *
     * @param string $sName The name of the service to remove.
     * @return string The formatted menu item string for removing the service.
     */
    public static function getItemRemove($sName)
    {
        global $bearsamppLang;

        return TplApp::getActionRun(
            Action::SERVICE, array($sName, ActionService::REMOVE),
            array($bearsamppLang->getValue(Lang::MENU_REMOVE_SERVICE), TplAestan::GLYPH_SERVICE_REMOVE)
        );
    }
}
