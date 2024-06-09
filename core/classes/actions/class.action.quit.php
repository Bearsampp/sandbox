<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionQuit
 *
 * Handles the quitting process of the Bearsampp application. This includes displaying a splash screen,
 * stopping services, deleting symbolic links, and killing other processes.
 */
class ActionQuit
{
    /**
     * @var Splash The splash screen object used to display progress.
     */
    private $splash;

    /**
     * Gauge values used for progress bar calculations.
     */
    const GAUGE_PROCESSES = 1;
    const GAUGE_OTHERS = 1;

    /**
     * ActionQuit constructor.
     *
     * Initializes the quitting process by displaying a splash screen and setting up the main loop.
     *
     * @param array $args Command line arguments passed to the script.
     */
    public function __construct($args)
    {
        global $bearsamppCore, $bearsamppLang, $bearsamppBins, $bearsamppWinbinder, $arrayOfCurrents;

        // Start splash screen
        $this->splash = new Splash();
        $this->splash->init(
            $bearsamppLang->getValue(Lang::QUIT),
            self::GAUGE_PROCESSES * count($bearsamppBins->getServices()) + self::GAUGE_OTHERS,
            sprintf($bearsamppLang->getValue(Lang::EXIT_LEAVING_TEXT), APP_TITLE . ' ' . $bearsamppCore->getAppVersion())
        );

        $bearsamppWinbinder->setHandler($this->splash->getWbWindow(), $this, 'processWindow', 2000);
        $bearsamppWinbinder->mainLoop();
        $bearsamppWinbinder->reset();
    }

    /**
     * Processes the window events.
     *
     * This method is called by the WinBinder library to handle window events. It stops services,
     * deletes symbolic links, and kills other processes.
     *
     * @param resource $window The window resource.
     * @param int $id The ID of the event.
     * @param int $ctrl The control ID.
     * @param mixed $param1 Additional parameter 1.
     * @param mixed $param2 Additional parameter 2.
     */
    public function processWindow($window, $id, $ctrl, $param1, $param2)
    {
        global $bearsamppBins, $bearsamppLang, $bearsamppWinbinder;

        foreach ($bearsamppBins->getServices() as $sName => $service) {
            $name = $bearsamppBins->getApache()->getName() . ' ' . $bearsamppBins->getApache()->getVersion();
            if ($sName == BinMysql::SERVICE_NAME) {
                $name = $bearsamppBins->getMysql()->getName() . ' ' . $bearsamppBins->getMysql()->getVersion();
            } elseif ($sName == BinMailhog::SERVICE_NAME) {
                $name = $bearsamppBins->getMailhog()->getName() . ' ' . $bearsamppBins->getMailhog()->getVersion();
            } elseif ($sName == BinMariadb::SERVICE_NAME) {
                $name = $bearsamppBins->getMariadb()->getName() . ' ' . $bearsamppBins->getMariadb()->getVersion();
            } elseif ($sName == BinPostgresql::SERVICE_NAME) {
                $name = $bearsamppBins->getPostgresql()->getName() . ' ' . $bearsamppBins->getPostgresql()->getVersion();
            } elseif ($sName == BinMailhog::SERVICE_NAME) {
                $name = $bearsamppBins->getPostgresql()->getName() . ' ' . $bearsamppBins->getPostgresql()->getVersion();
            } elseif ($sName == BinMemcached::SERVICE_NAME) {
                $name = $bearsamppBins->getMemcached()->getName() . ' ' . $bearsamppBins->getMemcached()->getVersion();
            } elseif ($sName == BinFilezilla::SERVICE_NAME) {
                $name = $bearsamppBins->getFilezilla()->getName() . ' ' . $bearsamppBins->getFilezilla()->getVersion();
            }
            $name .= ' (' . $service->getName() . ')';

            $this->splash->incrProgressBar();
            $this->splash->setTextLoading(sprintf($bearsamppLang->getValue(Lang::EXIT_REMOVE_SERVICE_TEXT), $name));
            $service->delete();
        }

        // Purge "current" symlinks
        Symlinks::deleteCurrentSymlinks();

        $this->splash->incrProgressBar();
        $this->splash->setTextLoading($bearsamppLang->getValue(Lang::EXIT_STOP_OTHER_PROCESS_TEXT));
        Win32Ps::killBins(true);

        $bearsamppWinbinder->destroyWindow($window);
    }
}
