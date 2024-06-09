<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class TplAppNodejs
 *
 * This class provides methods to generate and manage the Node.js menu and actions within the Bearsampp application.
 */
class TplAppNodejs
{
    const MENU = 'nodejs';
    const MENU_VERSIONS = 'nodejsVersions';

    const ACTION_ENABLE = 'enableNodejs';
    const ACTION_SWITCH_VERSION = 'switchNodejsVersion';

    /**
     * Processes the Node.js menu.
     *
     * @global object $bearsamppLang The language object.
     * @global object $bearsamppBins The bins object.
     * @return array The generated menu and its content.
     */
    public static function process()
    {
        global $bearsamppLang, $bearsamppBins;

        return TplApp::getMenuEnable(
            $bearsamppLang->getValue(Lang::NODEJS),
            self::MENU,
            get_called_class(),
            $bearsamppBins->getNodejs()->isEnable()
        );
    }

    /**
     * Generates the Node.js menu.
     *
     * @global object $bearsamppBins The bins object.
     * @global object $bearsamppLang The language object.
     * @global object $bearsamppTools The tools object.
     * @return string The generated menu items and actions.
     */
    public static function getMenuNodejs()
    {
        global $bearsamppBins, $bearsamppLang, $bearsamppTools;
        $resultItems = $resultActions = '';

        $isEnabled = $bearsamppBins->getNodejs()->isEnable();

        // Download
        $resultItems .= TplAestan::getItemLink(
            $bearsamppLang->getValue(Lang::DOWNLOAD_MORE),
            Util::getWebsiteUrl('module/filezilla', '#releases'),
            false,
            TplAestan::GLYPH_BROWSER
        ) . PHP_EOL;

        // Enable
        $tplEnable = TplApp::getActionMulti(
            self::ACTION_ENABLE,
            array($isEnabled ? Config::DISABLED : Config::ENABLED),
            array($bearsamppLang->getValue(Lang::MENU_ENABLE), $isEnabled ? TplAestan::GLYPH_CHECK : ''),
            false,
            get_called_class()
        );
        $resultItems .= $tplEnable[TplApp::SECTION_CALL] . PHP_EOL;
        $resultActions .= $tplEnable[TplApp::SECTION_CONTENT] . PHP_EOL;

        if ($isEnabled) {
            $resultItems .= TplAestan::getItemSeparator() . PHP_EOL;

            // Versions
            $tplVersions = TplApp::getMenu(
                $bearsamppLang->getValue(Lang::VERSIONS),
                self::MENU_VERSIONS,
                get_called_class()
            );
            $resultItems .= $tplVersions[TplApp::SECTION_CALL] . PHP_EOL;
            $resultActions .= $tplVersions[TplApp::SECTION_CONTENT];

            // Console
            $resultItems .= TplAestan::getItemConsoleZ(
                $bearsamppLang->getValue(Lang::CONSOLE),
                TplAestan::GLYPH_CONSOLEZ,
                $bearsamppTools->getConsoleZ()->getTabTitleNodejs()
            ) . PHP_EOL;

            // Conf
            $resultItems .= TplAestan::getItemNotepad(
                basename($bearsamppBins->getNodejs()->getConf()),
                $bearsamppBins->getNodejs()->getConf()
            ) . PHP_EOL;
        }

        return $resultItems . PHP_EOL . $resultActions;
    }

    /**
     * Generates the Node.js versions menu.
     *
     * @global object $bearsamppBins The bins object.
     * @return string The generated menu items and actions for Node.js versions.
     */
    public static function getMenuNodejsVersions()
    {
        global $bearsamppBins;
        $items = '';
        $actions = '';

        foreach ($bearsamppBins->getNodejs()->getVersionList() as $version) {
            $tplSwitchNodejsVersion = TplApp::getActionMulti(
                self::ACTION_SWITCH_VERSION,
                array($version),
                array($version, $version == $bearsamppBins->getNodejs()->getVersion() ? TplAestan::GLYPH_CHECK : ''),
                false,
                get_called_class()
            );

            // Item
            $items .= $tplSwitchNodejsVersion[TplApp::SECTION_CALL] . PHP_EOL;

            // Action
            $actions .= PHP_EOL . $tplSwitchNodejsVersion[TplApp::SECTION_CONTENT];
        }

        return $items . $actions;
    }

    /**
     * Generates the action to enable or disable Node.js.
     *
     * @global object $bearsamppBins The bins object.
     * @param int $enable The enable flag (1 for enable, 0 for disable).
     * @return string The generated action string.
     */
    public static function getActionEnableNodejs($enable)
    {
        global $bearsamppBins;

        return TplApp::getActionRun(
            Action::ENABLE,
            array($bearsamppBins->getNodejs()->getName(), $enable)
        ) . PHP_EOL . TplAppReload::getActionReload();
    }

    /**
     * Generates the action to switch the Node.js version.
     *
     * @global object $bearsamppBins The bins object.
     * @param string $version The version to switch to.
     * @return string The generated action string.
     */
    public static function getActionSwitchNodejsVersion($version)
    {
        global $bearsamppBins;

        return TplApp::getActionRun(
            Action::SWITCH_VERSION,
            array($bearsamppBins->getNodejs()->getName(), $version)
        ) . PHP_EOL . TplAppReload::getActionReload() . PHP_EOL;
    }
}
