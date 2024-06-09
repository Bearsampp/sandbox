<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class TplAppPython
 *
 * This class provides methods to generate and manage the Python-related menu items for the application.
 * It includes methods to process and generate the main Python menu and its sub-items.
 */
class TplAppPython
{
    /**
     * Constant representing the menu name for Python.
     */
    const MENU = 'python';

    /**
     * Processes and generates the main Python menu.
     *
     * @global object $bearsamppLang The language application object.
     * @return array An array containing the call string and the menu content.
     */
    public static function process()
    {
        global $bearsamppLang;

        return TplApp::getMenu($bearsamppLang->getValue(Lang::PYTHON), self::MENU, get_called_class());
    }

    /**
     * Generates the content of the Python menu.
     *
     * @global object $bearsamppLang The language application object.
     * @global object $bearsamppTools The tools application object.
     * @return string The generated menu items as a string.
     */
    public static function getMenuPython()
    {
        global $bearsamppLang, $bearsamppTools;

        $resultItems = TplAestan::getItemConsoleZ(
            $bearsamppLang->getValue(Lang::PYTHON_CONSOLE),
            TplAestan::GLYPH_PYTHON,
            $bearsamppTools->getConsoleZ()->getTabTitlePython()
        ) . PHP_EOL;

        $resultItems .= TplAestan::getItemExe(
            $bearsamppLang->getValue(Lang::PYTHON) . ' IDLE',
            $bearsamppTools->getPython()->getIdleExe(),
            TplAestan::GLYPH_PYTHON
        ) . PHP_EOL;

        $resultItems .= TplAestan::getItemExe(
            $bearsamppLang->getValue(Lang::PYTHON_CP),
            $bearsamppTools->getPython()->getCpExe(),
            TplAestan::GLYPH_PYTHON_CP
        ) . PHP_EOL;

        return $resultItems;
    }
}
