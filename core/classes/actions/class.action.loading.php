<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionLoading
 *
 * This class is responsible for managing the loading action within the Bearsampp application.
 * It controls the progress bar window, updates the progress bar, and handles the loading process.
 */
class ActionLoading
{
    /** @var int WINDOW_WIDTH The width of the progress bar window. */
    const WINDOW_WIDTH = 360;

    /** @var int WINDOW_HEIGHT The height of the progress bar window. */
    const WINDOW_HEIGHT = 90;

    /** @var int GAUGE The maximum value of the progress bar. */
    const GAUGE = 20;

    /** @var mixed $wbWindow The window object for the progress bar. */
    private $wbWindow;

    /** @var mixed $wbProgressBar The progress bar object. */
    private $wbProgressBar;

    /**
     * ActionLoading constructor.
     *
     * Initializes the loading action, creates the progress bar window, and starts the main loop.
     *
     * @param array $args The arguments passed to the constructor.
     */
    public function __construct($args)
    {
        global $bearsamppCore, $bearsamppLang, $bearsamppWinbinder;

        $bearsamppWinbinder->reset();
        $bearsamppCore->addLoadingPid(Win32Ps::getCurrentPid());

        // Screen information
        $screenArea = explode(' ', $bearsamppWinbinder->getSystemInfo(WinBinder::SYSINFO_WORKAREA));
        $screenWidth = intval($screenArea[2]);
        $screenHeight = intval($screenArea[3]);
        $xPos = $screenWidth - self::WINDOW_WIDTH;
        $yPos = $screenHeight - self::WINDOW_HEIGHT - 5;

        $this->wbWindow = $bearsamppWinbinder->createWindow(null, ToolDialog, null, $xPos, $yPos, self::WINDOW_WIDTH, self::WINDOW_HEIGHT, WBC_TOP, null);

        $bearsamppWinbinder->createLabel($this->wbWindow, $bearsamppLang->getValue(Lang::LOADING), 42, 2, 295, null, WBC_LEFT);
        $this->wbProgressBar = $bearsamppWinbinder->createProgressBar($this->wbWindow, self::GAUGE, 42, 20, 290, 15);

        $bearsamppWinbinder->setHandler($this->wbWindow, $this, 'processLoading', 10);
        $bearsamppWinbinder->mainLoop();
    }

    /**
     * Increments the progress bar by a specified number of steps.
     *
     * @param int $nb The number of steps to increment the progress bar by. Default is 1.
     */
    public function incrProgressBar($nb = 1)
    {
        global $bearsamppCore, $bearsamppWinbinder;

        for ($i = 0; $i < $nb; $i++) {
            $bearsamppWinbinder->incrProgressBar($this->wbProgressBar);
            $bearsamppWinbinder->drawImage($this->wbWindow, $bearsamppCore->getResourcesPath() . '/homepage/img/bearsampp.bmp', 4, 2, 32, 32);
        }

        $bearsamppWinbinder->wait();
        $bearsamppWinbinder->wait($this->wbWindow);
    }

    /**
     * Processes the loading action and updates the progress bar.
     *
     * @param mixed $window The window object.
     * @param int $id The ID of the control that triggered the event.
     * @param mixed $ctrl The control object.
     * @param mixed $param1 Additional parameter 1.
     * @param mixed $param2 Additional parameter 2.
     */
    public function processLoading($window, $id, $ctrl, $param1, $param2)
    {
        global $bearsamppRoot, $bearsamppWinbinder;

        switch ($id) {
            case IDCLOSE:
                Win32Ps::kill(Win32Ps::getCurrentPid());
                break;
        }

        while (true) {
            $bearsamppRoot->removeErrorHandling();
            $bearsamppWinbinder->resetProgressBar($this->wbProgressBar);
            usleep(100000);
            for ($i = 0; $i < self::GAUGE; $i++) {
                $this->incrProgressBar();
                usleep(100000);
            }
        }
    }
}
