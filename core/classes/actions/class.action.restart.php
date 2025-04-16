<?php
/*
 *
 *  * Copyright (c) 2022-2025 Bearsampp
 *  * License: GNU General Public License version 3 or later; see LICENSE.txt
 *  * Website: https://bearsampp.com
 *  * Github: https://github.com/Bearsampp
 *
 */

/**
 * Class ActionRestart
 * Handles the restart action for the Bearsampp application.
 */
class ActionRestart
{
    /**
     * ActionRestart constructor.
     * Displays a message box with restart information.
     *
     * @param array $args Command line arguments passed to the action.
     */
    public function __construct($args)
    {
        global $bearsamppLang, $bearsamppWinbinder, $bearsamppCore;

        Util::logTrace('ActionRestart: [RESTART_FLOW] Constructor starting - ' . microtime(true));
        Util::logTrace('ActionRestart: [RESTART_FLOW] Args: ' . (is_array($args) ? json_encode($args) : 'No arguments'));
        
        $restartTitle = $bearsamppLang->getValue(Lang::RESTART_TITLE);
        $restartText = sprintf($bearsamppLang->getValue(Lang::RESTART_TEXT), APP_TITLE);
        
        Util::logTrace('ActionRestart: [RESTART_FLOW] Preparing restart message box - ' . microtime(true));
        Util::logTrace('ActionRestart: [RESTART_FLOW] Title: ' . $restartTitle);
        Util::logTrace('ActionRestart: [RESTART_FLOW] Text: ' . $restartText);
        
        Util::logTrace('ActionRestart: [RESTART_FLOW] Checking WinBinder state before message box - ' . microtime(true));
        Util::logTrace('ActionRestart: [RESTART_FLOW] WinBinder object: ' . (is_object($bearsamppWinbinder) ? 'Valid' : 'Invalid'));
        
        try {
            Util::logTrace('ActionRestart: [RESTART_FLOW] Displaying message box - ' . microtime(true));
            $result = $bearsamppWinbinder->messageBoxInfo(
                $restartText,
                $restartTitle
            );
            Util::logTrace('ActionRestart: [RESTART_FLOW] Message box displayed successfully - Result: ' . $result . ' - ' . microtime(true));
        } catch (Exception $e) {
            Util::logTrace('ActionRestart: [RESTART_FLOW] Exception displaying message box: ' . $e->getMessage() . ' - ' . microtime(true));
        }
        
        Util::logTrace('ActionRestart: [RESTART_FLOW] WinBinder state after message box - ' . microtime(true));
        Util::logTrace('ActionRestart: [RESTART_FLOW] WinBinder object: ' . (is_object($bearsamppWinbinder) ? 'Valid' : 'Invalid'));
        
        // Verify that the exec action is set correctly
        Util::logTrace('ActionRestart: [RESTART_FLOW] ExecAction value: ' . $bearsamppCore->getExec());
        
        // Pre-exit logging
        Util::logTrace('ActionRestart: [RESTART_FLOW] Constructor completed - About to return from constructor - ' . microtime(true));
    }
}
