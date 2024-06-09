<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionDebugMysql
 *
 * This class is responsible for debugging MySQL by executing specific commands and displaying the results.
 * It supports commands to retrieve MySQL version, variables, and syntax check.
 * The results are displayed either in an editor or a message box, depending on the command.
 */
class ActionDebugMysql
{
    /**
     * Constructor for ActionDebugMysql.
     *
     * @param array $args An array of arguments where the first element specifies the MySQL command to execute.
     *
     * This constructor initializes the debugging process for MySQL based on the provided command.
     * It sets up the caption for the output window, executes the command, and displays the result.
     *
     * The supported commands are:
     * - BinMysql::CMD_VERSION: Retrieves the MySQL version.
     * - BinMysql::CMD_VARIABLES: Retrieves MySQL variables.
     * - BinMysql::CMD_SYNTAX_CHECK: Performs a syntax check on MySQL configuration.
     *
     * Depending on the command, the output is either displayed in an editor or a message box.
     * If the syntax check fails, an error message box is displayed.
     */
    public function __construct($args)
    {
        global $bearsamppLang, $bearsamppBins, $bearsamppTools, $bearsamppWinbinder;

        if (isset($args[0]) && !empty($args[0])) {
            $editor = false;
            $msgBoxError = false;
            $caption = $bearsamppLang->getValue(Lang::DEBUG) . ' ' . $bearsamppLang->getValue(Lang::MYSQL) . ' - ';
            if ($args[0] == BinMysql::CMD_VERSION) {
                $caption .= $bearsamppLang->getValue(Lang::DEBUG_MYSQL_VERSION);
            } elseif ($args[0] == BinMysql::CMD_VARIABLES) {
                $editor = true;
                $caption .= $bearsamppLang->getValue(Lang::DEBUG_MYSQL_VARIABLES);
            } elseif ($args[0] == BinMysql::CMD_SYNTAX_CHECK) {
                $caption .= $bearsamppLang->getValue(Lang::DEBUG_MYSQL_SYNTAX_CHECK);
            }
            $caption .= ' (' . $args[0] . ')';

            $debugOutput = $bearsamppBins->getMysql()->getCmdLineOutput($args[0]);

            if ($args[0] == BinMysql::CMD_SYNTAX_CHECK) {
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
