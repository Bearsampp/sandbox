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
 * This class handles the loading action, including the creation and management of a progress bar window.
 */
class ActionLoading
{
    /** @var int The width of the progress bar window. */
    const WINDOW_WIDTH = 360;

    /** @var int The height of the progress bar window. */
    const WINDOW_HEIGHT = 90;

    /** @var int The maximum value of the progress bar. */
    const GAUGE = 20;

    /** @var mixed The window object created by WinBinder. */
    private $wbWindow;

    /** @var mixed The progress bar object created by WinBinder. */
    private $wbProgressBar;

    /**
     * ActionLoading constructor.
     *
     * Initializes the loading action, creates the progress bar window, and starts the main loop.
     *
     * @param array $args The arguments passed to the constructor.
     */
    public function __construct($args = [])
    {
        global $bearsamppCore, $bearsamppLang, $bearsamppWinbinder;

        try {
            $bearsamppWinbinder->reset();
            $bearsamppCore->addLoadingPid(Win32Ps::getCurrentPid());

            // Screen information
            $screenArea = explode(' ', $bearsamppWinbinder->getSystemInfo(WinBinder::SYSINFO_WORKAREA));
            $screenWidth = intval($screenArea[2] ?? 1024);
            $screenHeight = intval($screenArea[3] ?? 768);
            $xPos = $screenWidth - self::WINDOW_WIDTH;
            $yPos = $screenHeight - self::WINDOW_HEIGHT - 5;

            // Set default caption
            $caption = 'Loading...';
            if (isset($bearsamppLang) && method_exists($bearsamppLang, 'getValue')) {
                $langValue = $bearsamppLang->getValue(Lang::LOADING);
                if (!empty($langValue)) {
                    $caption = $langValue;
                }
            }
            
            // Create the window and progress bar
            // Use 0 instead of null for the parent window parameter
            $this->wbWindow = $bearsamppWinbinder->createWindow(0, ToolDialog, $caption, $xPos, $yPos, self::WINDOW_WIDTH, self::WINDOW_HEIGHT, WBC_TOP, null);
            
            if (!$this->wbWindow) {
                throw new Exception("Failed to create loading window");
            }
            
            $bearsamppWinbinder->createLabel($this->wbWindow, $caption, 42, 2, 295, null, WBC_LEFT);
            $this->wbProgressBar = $bearsamppWinbinder->createProgressBar($this->wbWindow, self::GAUGE, 42, 20, 290, 15);

            if (!$this->wbProgressBar) {
                throw new Exception("Failed to create progress bar");
            }

            // Set the handler and start the main loop
            $bearsamppWinbinder->setHandler($this->wbWindow, $this, 'processLoading', 10);
            $bearsamppWinbinder->mainLoop();
        } catch (Exception $e) {
            // Log the error
            if (isset($bearsamppCore) && method_exists($bearsamppCore, 'addLog')) {
                $bearsamppCore->addLog('ActionLoading error: ' . $e->getMessage());
            }
            
            // Terminate gracefully
            if (function_exists('Win32Ps::kill')) {
                Win32Ps::kill(Win32Ps::getCurrentPid());
            } else {
                exit(1);
            }
        }
    }

    /**
     * Increments the progress bar by a specified number of steps.
     *
     * @param int $nb The number of steps to increment the progress bar by. Default is 1.
     */
    public function incrProgressBar($nb = 1)
    {
        global $bearsamppCore, $bearsamppWinbinder;

        if (!$this->wbProgressBar || !$this->wbWindow) {
            return false;
        }

        try {
            for ($i = 0; $i < $nb; $i++) {
                $bearsamppWinbinder->incrProgressBar($this->wbProgressBar);
                
                $imagePath = $bearsamppCore->getResourcesPath() . '/homepage/img/bearsampp.bmp';
                if (file_exists($imagePath)) {
                    $bearsamppWinbinder->drawImage($this->wbWindow, $imagePath, 4, 2, 32, 32);
                }
            }

            $bearsamppWinbinder->wait();
            $bearsamppWinbinder->wait($this->wbWindow);
            return true;
        } catch (Exception $e) {
            if (isset($bearsamppCore) && method_exists($bearsamppCore, 'addLog')) {
                $bearsamppCore->addLog('Progress bar error: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Processes the loading action, including handling window events and updating the progress bar.
     *
     * @param mixed $window The window object.
     * @param int $id The ID of the event.
     * @param mixed $ctrl The control object.
     * @param mixed $param1 The first parameter of the event.
     * @param mixed $param2 The second parameter of the event.
     */
    public function processLoading($window, $id, $ctrl, $param1, $param2)
    {
        global $bearsamppRoot, $bearsamppWinbinder, $bearsamppCore;

        switch ($id) {
            case IDCLOSE:
                if (function_exists('Win32Ps::kill')) {
                    Win32Ps::kill(Win32Ps::getCurrentPid());
                } else {
                    exit(0);
                }
                break;
        }

        try {
            while (true) {
                if (method_exists($bearsamppRoot, 'removeErrorHandling')) {
                    $bearsamppRoot->removeErrorHandling();
                }
                
                if ($this->wbProgressBar) {
                    $bearsamppWinbinder->resetProgressBar($this->wbProgressBar);
                }
                
                usleep(100000);
                
                for ($i = 0; $i < self::GAUGE; $i++) {
                    $this->incrProgressBar();
                    usleep(100000);
                }
            }
        } catch (Exception $e) {
            if (isset($bearsamppCore) && method_exists($bearsamppCore, 'addLog')) {
                $bearsamppCore->addLog('Process loading error: ' . $e->getMessage());
            }
        }
    }
}
