<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionDebugMariadb
 *
 * This class is responsible for debugging MariaDB by executing specific commands and displaying the results.
 * It supports commands to retrieve the MariaDB version, variables, and perform a syntax check.
 * The results are displayed either in a message box or an editor, depending on the command.
 */
class ActionDebugMariadb
{
    /**
     * Constructor for the ActionDebugMariadb class.
     *
     * @param array $args An array of arguments where the first element specifies the command to execute.
     *                    Supported commands are:
     *                    - BinMariadb::CMD_VERSION: Retrieves the MariaDB version.
     *                    - BinMariadb::CMD_VARIABLES: Retrieves the MariaDB variables.
     *                    - BinMariadb::CMD_SYNTAX_CHECK: Performs a syntax check on the MariaDB configuration.
     *
     * @global object $bearsamppLang Global object for language translations.
     * @global object $bearsamppBins Global object for accessing various binaries including MariaDB.
     * @global object $bearsamppTools Global object for accessing various tools.
     * @global object $bearsamppWinbinder Global object for handling Windows-specific operations.
     */
    public function __construct($args)
    {
        global $bearsamppLang, $bearsamppBins, $bearsamppTools, $bearsamppWinbinder;

        if (isset($args[0]) && !empty($args[0])) {
            $editor = false;
            $msgBoxError = false;
            $caption = $bearsamppLang->getValue(Lang::DEBUG) . ' ' . $bearsamppLang->getValue(Lang::MARIADB) . ' - ';
            if ($args[0] == BinMariadb::CMD_VERSION) {
                $caption .= $bearsamppLang->getValue(Lang::DEBUG_MARIADB_VERSION);
            } elseif ($args[0] == BinMariadb::CMD_VARIABLES) {
                $editor = true;
                $caption .= $bearsamppLang->getValue(Lang::DEBUG_MARIADB_VARIABLES);
            } elseif ($args[0] == BinMariadb::CMD_SYNTAX_CHECK) {
                $caption .= $bearsamppLang->getValue(Lang::DEBUG_MARIADB_SYNTAX_CHECK);
            }
            $caption .= ' (' . $args[0] . ')';

            $debugOutput = $bearsamppBins->getMariadb()->getCmdLineOutput($args[0]);

            if ($args[0] == BinMariadb::CMD_SYNTAX_CHECK) {
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
