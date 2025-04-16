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
 * Class ActionManualRestart
 *
 * This class handles the manual restart of services in the Bearsampp application.
 * It stops all running services, kills all related processes, and sets the application
 * to restart.
 */
class ActionManualRestart
{
    /**
     * ActionManualRestart constructor.
     *
     * @param array $args Arguments passed to the constructor.
     *
     * This constructor initializes the manual restart process by performing the following steps:
     * 1. Starts the loading process.
     * 2. Deletes all services managed by Bearsampp.
     * 3. Kills all related processes.
     * 4. Sets the application to restart.
     * 5. Stops the loading process.
     */
    public function __construct($args)
    {
        global $bearsamppCore, $bearsamppBins;

        // Start the loading process
        Util::logTrace('ActionManualRestart: [RESTART_FLOW] Manual restart process initiated - ' . microtime(true));
        Util::startLoading();
        Util::logTrace('ActionManualRestart: [RESTART_FLOW] Loading process started');

        // Delete all services managed by Bearsampp
        Util::logTrace('ActionManualRestart: [RESTART_FLOW] Beginning service deletion process');
        $serviceCount = count($bearsamppBins->getServices());
        Util::logTrace('ActionManualRestart: [RESTART_FLOW] Found ' . $serviceCount . ' services to delete');
        
        $deletedServices = 0;
        foreach ($bearsamppBins->getServices() as $sName => $service) {
            Util::logTrace('ActionManualRestart: [RESTART_FLOW] Deleting service: ' . $sName);
            // PHP 8.x compatibility - Handle exceptions with try/catch since more errors are exceptions in PHP 8
            try {
                $result = $service->delete();
                Util::logTrace('ActionManualRestart: [RESTART_FLOW] Service ' . $sName . ' deletion ' . ($result ? 'successful' : 'failed'));
            } catch (Throwable $e) {
                // Use Throwable instead of Exception to catch both Exception and Error in PHP 8
                Util::logTrace('ActionManualRestart: [RESTART_FLOW] Error deleting service ' . $sName . ': ' . $e->getMessage());
            }
            $deletedServices++;
        }
        Util::logTrace('ActionManualRestart: [RESTART_FLOW] Completed deletion of ' . $deletedServices . ' services');

        // Kill all related processes
        Util::logTrace('ActionManualRestart: [RESTART_FLOW] Starting process termination');
        
        // PHP 8 compatibility - Use try/catch for process operations
        try {
            $killedProcesses = Win32Ps::killBins(true);
            Util::logTrace('ActionManualRestart: [RESTART_FLOW] Terminated ' . count($killedProcesses) . ' processes');
        } catch (Throwable $e) {
            Util::logTrace('ActionManualRestart: [RESTART_FLOW] Error during process termination: ' . $e->getMessage());
        }

        // Set the application to restart
        Util::logTrace('ActionManualRestart: [RESTART_FLOW] Setting application to restart mode');
        $bearsamppCore->setExec(ActionExec::RESTART);
        Util::logTrace('ActionManualRestart: [RESTART_FLOW] Application set to restart mode successfully');

        // Stop the loading process
        Util::logTrace('ActionManualRestart: [RESTART_FLOW] Stopping loading process');
        Util::stopLoading();
        
        // PHP 8 compatibility - Ensure proper handling of process termination
        Util::logTrace('ActionManualRestart: [RESTART_FLOW] Manual restart process completed - ' . microtime(true));
        
        // In PHP 8, we need to explicitly register a shutdown function to ensure proper cleanup
        register_shutdown_function(function() {
            Util::logTrace('ActionManualRestart: [RESTART_FLOW] Shutdown function executed for clean termination');
        });
    }
}
