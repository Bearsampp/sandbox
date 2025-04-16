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
 * Class ActionExec
 *
 * This class handles the execution of specific actions based on the content of a file.
 * The actions include quitting the application or restarting it. The actions are read
 * from a file whose path is provided by the global `$bearsamppCore` object.
 */
class ActionExec
{
    /**
     * Constant representing the 'quit' action.
     */
    const QUIT = 'quit';

    /**
     * Constant representing the 'restart' action.
     */
    const RESTART = 'restart';

    /**
     * ActionExec constructor.
     *
     * This constructor reads the action from a file specified by `$bearsamppCore->getExec()`.
     * If the action is 'quit', it calls `Batch::exitApp()`. If the action is 'restart', it calls
     * `Batch::restartApp()`. After executing the action, it deletes the action file.
     *
     * @param array $args Arguments passed to the constructor (not used in the current implementation).
     */
    public function __construct($args)
    {
        global $bearsamppCore;
        
        Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Constructor starting - ' . microtime(true));
        Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Args: ' . (is_array($args) ? json_encode($args) : 'No arguments') . ' - ' . microtime(true));
        
        // Check if bearsamppCore is available
        if (!isset($bearsamppCore) || !is_object($bearsamppCore)) {
            Util::logTrace('ActionExec::__construct: [RESTART_FLOW] ERROR: bearsamppCore not available - ' . microtime(true));
            return;
        }
        
        // Get exec file path
        $execPath = $bearsamppCore->getExec();
        Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Exec file path: ' . $execPath . ' - ' . microtime(true));
        
        // Check if exec file exists
        if (file_exists($execPath)) {
            Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Exec file exists, reading content - ' . microtime(true));
            
            // Read action from file
            $action = file_get_contents($execPath);
            Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Action read from file: "' . $action . '" - ' . microtime(true));
            
            // Delete the exec file first to avoid re-execution
            Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Deleting exec file - ' . microtime(true));
            $unlinkResult = @unlink($execPath);
            Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Delete result: ' . ($unlinkResult ? 'success' : 'failed') . ' - ' . microtime(true));
            
            // Process quit action
            if ($action == self::QUIT) {
                Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Processing QUIT action - ' . microtime(true));
                
                try {
                    Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Calling Batch::exitApp() - ' . microtime(true));
                    Batch::exitApp();
                    Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Batch::exitApp() called successfully - ' . microtime(true));
                } catch (Exception $e) {
                    Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Exception in Batch::exitApp(): ' . $e->getMessage() . ' - ' . microtime(true));
                }
            } 
            // Process restart action
            elseif ($action == self::RESTART) {
                Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Processing RESTART action - ' . microtime(true));
                
                try {
                    Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Calling Batch::restartApp() - ' . microtime(true));
                    // Use a more direct approach if Batch::restartApp() is failing
                    if (method_exists('Batch', 'restartApp')) {
                        Batch::restartApp();
                        Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Batch::restartApp() called successfully - ' . microtime(true));
                    } else {
                        Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Batch::restartApp() not available, using direct restart - ' . microtime(true));
                        
                        // Get the executable path
                        global $bearsamppRoot;
                        if (isset($bearsamppRoot) && is_object($bearsamppRoot)) {
                            $exePath = $bearsamppRoot->getExeFilePath();
                            Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Executable path: ' . $exePath . ' - ' . microtime(true));
                            
                            if (file_exists($exePath)) {
                                // Start the executable directly
                                Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Starting executable directly - ' . microtime(true));
                                pclose(popen('start "" "' . $exePath . '"', 'r'));
                                Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Executable started - ' . microtime(true));
                            } else {
                                Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Executable not found: ' . $exePath . ' - ' . microtime(true));
                            }
                        } else {
                            Util::logTrace('ActionExec::__construct: [RESTART_FLOW] bearsamppRoot not available - ' . microtime(true));
                        }
                    }
                } catch (Exception $e) {
                    Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Exception in restart process: ' . $e->getMessage() . ' - ' . microtime(true));
                }
            }
            // Unknown action
            else {
                Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Unknown action: "' . $action . '" - ' . microtime(true));
            }
            
            // Verify file deletion
            if (file_exists($execPath)) {
                Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Warning: Exec file still exists after deletion attempt - ' . microtime(true));
            } else {
                Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Exec file successfully deleted - ' . microtime(true));
            }
        } 
        // Exec file doesn't exist
        else {
            Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Exec file does not exist at path: ' . $execPath . ' - ' . microtime(true));
        }
        
        Util::logTrace('ActionExec::__construct: [RESTART_FLOW] Constructor completed - ' . microtime(true));
    }
}
