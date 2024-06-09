<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionDebugApache
 *
 * This class is responsible for debugging various aspects of the Apache server.
 * It retrieves and displays information such as version number, compile settings,
 * compiled modules, configuration directives, virtual host settings, loaded modules,
 * and performs syntax checks.
 *
 * The class utilizes global variables to access language settings, binaries, tools,
 * and the WinBinder library for displaying messages.
 */
class ActionDebugApache
{
    /**
     * Constructor for ActionDebugApache
     *
     * @param array $args An array of arguments specifying the type of debug information to retrieve.
     *
     * This constructor checks the provided argument to determine the type of Apache debug information
     * to retrieve. It sets the appropriate caption for the debug window and retrieves the command line
     * output from the Apache binary. Depending on the type of information, it either opens the content
     * in an editor or displays it in a message box.
     *
     * The following debug types are supported:
     * - Version Number
     * - Compile Settings
     * - Compiled Modules
     * - Configuration Directives
     * - Virtual Host Settings
     * - Loaded Modules
     * - Syntax Check
     *
     * If the syntax check is performed, the result is displayed in a message box indicating whether
     * the syntax is OK or if there are errors.
     *
     * @global object $bearsamppLang Language settings object.
     * @global object $bearsamppBins Binaries object.
     * @global object $bearsamppTools Tools object.
     * @global object $bearsamppWinbinder WinBinder library object.
     */
    public function __construct($args)
    {
        global $bearsamppLang, $bearsamppBins, $bearsamppTools, $bearsamppWinbinder;

        if (isset($args[0]) && !empty($args[0])) {
            $editor = false;
            $msgBoxError = false;
            $caption = $bearsamppLang->getValue(Lang::DEBUG) . ' ' . $bearsamppLang->getValue(Lang::APACHE) . ' - ';
            if ($args[0] == BinApache::CMD_VERSION_NUMBER) {
                $caption .= $bearsamppLang->getValue(Lang::DEBUG_APACHE_VERSION_NUMBER);
            } elseif ($args[0] == BinApache::CMD_COMPILE_SETTINGS) {
                $caption .= $bearsamppLang->getValue(Lang::DEBUG_APACHE_COMPILE_SETTINGS);
            } elseif ($args[0] == BinApache::CMD_COMPILED_MODULES) {
                $caption .= $bearsamppLang->getValue(Lang::DEBUG_APACHE_COMPILED_MODULES);
            } elseif ($args[0] == BinApache::CMD_CONFIG_DIRECTIVES) {
                $editor = true;
                $caption .= $bearsamppLang->getValue(Lang::DEBUG_APACHE_CONFIG_DIRECTIVES);
            } elseif ($args[0] == BinApache::CMD_VHOSTS_SETTINGS) {
                $editor = true;
                $caption .= $bearsamppLang->getValue(Lang::DEBUG_APACHE_VHOSTS_SETTINGS);
            } elseif ($args[0] == BinApache::CMD_LOADED_MODULES) {
                $editor = true;
                $caption .= $bearsamppLang->getValue(Lang::DEBUG_APACHE_LOADED_MODULES);
            } elseif ($args[0] == BinApache::CMD_SYNTAX_CHECK) {
                $caption .= $bearsamppLang->getValue(Lang::DEBUG_APACHE_SYNTAX_CHECK);
            }
            $caption .= ' (' . $args[0] . ')';

            $debugOutput = $bearsamppBins->getApache()->getCmdLineOutput($args[0]);

            if ($args[0] == BinApache::CMD_SYNTAX_CHECK) {
                $msgBoxError = !$debugOutput['syntaxOk'];
                $debugOutput['content'] = $debugOutput['syntaxOk'] ? 'Syntax OK !' : $debugOutput['content'];
            }

            if ($editor) {
                Util::openFileContent($caption, $debugOutput['content']);
            } else {
                if ($msgBoxError) {
                    $bearsamppWinbinder->messageBoxError(
                        $debugOutput['content'],
                        $caption
                    );
                } else {
                    $bearsamppWinbinder->messageBoxInfo(
                        $debugOutput['content'],
                        $caption
                    );
                }
            }
        }
    }
}
