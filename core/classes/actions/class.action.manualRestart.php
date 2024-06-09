<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionManualRestart
 *
 * This class handles the manual restart of the Bearsampp application. It stops all running services,
 * kills all relevant processes, and sets the application to restart.
 */
class ActionManualRestart
{
    /**
     * ActionManualRestart constructor.
     *
     * This constructor method initializes the manual restart process for the Bearsampp application.
     * It performs the following steps:
     * 1. Starts a loading indicator.
     * 2. Iterates through all services managed by Bearsampp and deletes them.
     * 3. Kills all relevant processes using the Win32Ps utility.
     * 4. Sets the application to restart by updating the execution action.
     * 5. Stops the loading indicator.
     *
     * @param array $args Arguments passed to the constructor (currently not used).
     *
     * @global object $bearsamppCore Core functionalities of the Bearsampp application.
     * @global object $bearsamppBins  Binaries and services managed by Bearsampp.
     */
    public function __construct($args)
    {
        global $bearsamppCore, $bearsamppBins;

        Util::startLoading();

        foreach ($bearsamppBins->getServices() as $sName => $service) {
            $service->delete();
        }

        Win32Ps::killBins(true);

        $bearsamppCore->setExec(ActionExec::RESTART);
        Util::stopLoading();
    }
}
