<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionDebugPostgresql
 *
 * This class is responsible for handling the debugging actions for PostgreSQL within the Bearsampp application.
 * It retrieves and displays PostgreSQL debug information based on the provided command-line arguments.
 *
 * @package Bearsampp
 */
class ActionDebugPostgresql
{
    /**
     * Constructor for ActionDebugPostgresql.
     *
     * This method initializes the debugging process for PostgreSQL. It uses global variables to access language settings,
     * PostgreSQL binaries, tools, and the Winbinder for displaying messages. Depending on the provided arguments, it retrieves
     * the PostgreSQL command-line output and displays it either in an editor or a message box.
     *
     * @param array $args An array of command-line arguments. The first argument is expected to be a PostgreSQL command.
     *
     * @global object $bearsamppLang Global object for accessing language settings.
     * @global object $bearsamppBins Global object for accessing Bearsampp binaries.
     * @global object $bearsamppTools Global object for accessing Bearsampp tools.
     * @global object $bearsamppWinbinder Global object for displaying message boxes.
     */
    public function __construct($args)
    {
        global $bearsamppLang, $bearsamppBins, $bearsamppTools, $bearsamppWinbinder;

        if (isset($args[0]) && !empty($args[0])) {
            $editor = false;
            $msgBoxError = false;
            $caption = $bearsamppLang->getValue(Lang::DEBUG) . ' ' . $bearsamppLang->getValue(Lang::POSTGRESQL) . ' - ';
            if ($args[0] == BinPostgresql::CMD_VERSION) {
                $caption .= $bearsamppLang->getValue(Lang::DEBUG_POSTGRESQL_VERSION);
            }
            $caption .= ' (' . $args[0] . ')';

            $debugOutput = $bearsamppBins->getPostgresql()->getCmdLineOutput($args[0]);

            if ($editor) {
                Util::openFileContent($caption, $debugOutput);
            } else {
                if ($msgBoxError) {
                    $bearsamppWinbinder->messageBoxError(
                        $debugOutput,
                        $caption
                    );
                } else {
                    $bearsamppWinbinder->messageBoxInfo(
                        $debugOutput,
                        $caption
                    );
                }
            }
        }
    }
}
