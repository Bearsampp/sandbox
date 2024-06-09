<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class TplAppServices
 *
 * This class provides methods to process and generate actions for starting, stopping, and restarting services.
 * It includes methods to generate multi-actions for these operations and to retrieve the corresponding action strings.
 */
class TplAppServices
{
    // Constants representing service actions
    const ACTION_START = 'startServices';
    const ACTION_STOP = 'stopServices';
    const ACTION_RESTART = 'restartServices';

    /**
     * Processes and generates the actions for starting, stopping, and restarting services.
     *
     * @global object $bearsamppLang The language application object.
     * @return array An array containing the items and actions as strings.
     */
    public static function process()
    {
        global $bearsamppLang;

        $tplStart = TplApp::getActionMulti(
            self::ACTION_START, null,
            array($bearsamppLang->getValue(Lang::MENU_START_SERVICES), TplAestan::GLYPH_SERVICES_START),
            false, get_called_class()
        );

        $tplStop = TplApp::getActionMulti(
            self::ACTION_STOP, null,
            array($bearsamppLang->getValue(Lang::MENU_STOP_SERVICES), TplAestan::GLYPH_SERVICES_STOP),
            false, get_called_class()
        );

        $tplRestart = TplApp::getActionMulti(
            self::ACTION_RESTART, null,
            array($bearsamppLang->getValue(Lang::MENU_RESTART_SERVICES), TplAestan::GLYPH_SERVICES_RESTART),
            false, get_called_class()
        );

        // Items
        $items = $tplStart[TplApp::SECTION_CALL] . PHP_EOL .
            $tplStop[TplApp::SECTION_CALL] . PHP_EOL .
            $tplRestart[TplApp::SECTION_CALL] . PHP_EOL;

        // Actions
        $actions = PHP_EOL . $tplStart[TplApp::SECTION_CONTENT] .
            PHP_EOL . $tplStop[TplApp::SECTION_CONTENT] .
            PHP_EOL . $tplRestart[TplApp::SECTION_CONTENT];

        return array($items, $actions);
    }

    /**
     * Generates the action string to start all services.
     *
     * @global object $bearsamppBins The bins application object.
     * @return string The formatted action string to start all services.
     */
    public static function getActionStartServices()
    {
        global $bearsamppBins;
        $actions = '';

        foreach ($bearsamppBins->getServices() as $sName => $service) {
            $actions .= TplService::getActionStart($service->getName()) . PHP_EOL;
        }

        return $actions;
    }

    /**
     * Generates the action string to stop all services.
     *
     * @global object $bearsamppBins The bins application object.
     * @return string The formatted action string to stop all services.
     */
    public static function getActionStopServices()
    {
        global $bearsamppBins;
        $actions = '';

        foreach ($bearsamppBins->getServices() as $sName => $service) {
            $actions .= TplService::getActionStop($service->getName()) . PHP_EOL;
        }

        return $actions;
    }

    /**
     * Generates the action string to restart all services.
     *
     * @return string The concatenated action string to stop and then start all services.
     */
    public static function getActionRestartServices()
    {
        return self::getActionStopServices() . self::getActionStartServices();
    }
}
