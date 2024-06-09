<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionExec
 *
 * This class handles execution actions such as quitting or restarting the application.
 * It reads the action from a file specified by the global `$bearsamppCore` object and
 * performs the corresponding action using the `Batch` class.
 */
class ActionExec
{
    /**
     * Constants representing possible actions.
     */
    const QUIT = 'quit';
    const RESTART = 'restart';

    /**
     * ActionExec constructor.
     *
     * This constructor checks if the action file exists and reads its content to determine
     * the action to be performed. If the action is 'quit', it calls `Batch::exitApp()`.
     * If the action is 'restart', it calls `Batch::restartApp()`. After performing the action,
     * it deletes the action file.
     *
     * @param array $args Arguments passed to the constructor (not used in the current implementation).
     */
    public function __construct($args)
    {
        global $bearsamppCore;

        if (file_exists($bearsamppCore->getExec())) {
            $action = file_get_contents($bearsamppCore->getExec());
            if ($action == self::QUIT) {
                Batch::exitApp();
            } elseif ($action == self::RESTART) {
                Batch::restartApp();
            }
            @unlink($bearsamppCore->getExec());
        }
    }
}
