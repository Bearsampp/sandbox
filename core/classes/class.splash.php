<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class Splash
 *
 * This class is responsible for creating and managing a splash screen with a progress bar.
 * It utilizes the WinBinder library to create the GUI elements and handle events.
 *
 * @package Bearsampp
 * @license GNU General Public License version 3 or later; see LICENSE.txt
 * @link    https://bearsampp.com
 * @link    https://github.com/Bearsampp
 */
class Splash
{
    /* Set progress bar "loading" modal size. */
    const WINDOW_WIDTH = 460;
    const WINDOW_HEIGHT = 90;

    private $wbWindow;
    private $wbImage;
    private $wbTextLoading;
    private $wbProgressBar;

    private $currentImg;

    /**
     * Splash constructor.
     *
     * Initializes the Splash class and sets the current image to null.
     */
    public function __construct()
    {
        Util::logInitClass($this);

        $this->currentImg = null;
    }

    /**
     * Initializes the splash screen with the specified title, gauge, and text.
     *
     * @param string $title The title of the splash screen.
     * @param int    $gauge The maximum value of the progress bar.
     * @param string $text  The initial text to display on the splash screen.
     */
    public function init($title, $gauge, $text)
    {
        global $bearsamppCore, $bearsamppWinbinder;

        $bearsamppWinbinder->reset();

        $screenArea = explode(' ', $bearsamppWinbinder->getSystemInfo(WinBinder::SYSINFO_WORKAREA));
        $screenWidth = intval($screenArea[2]);
        $screenHeight = intval($screenArea[3]);
        $xPos = $screenWidth - self::WINDOW_WIDTH;
        $yPos = $screenHeight - self::WINDOW_HEIGHT - 5;

        $this->wbWindow = $bearsamppWinbinder->createWindow(null, ToolDialog, $title, $xPos, $yPos, self::WINDOW_WIDTH, self::WINDOW_HEIGHT, WBC_TOP | WBC_READONLY, null);
        $this->wbImage = $bearsamppWinbinder->drawImage($this->wbWindow, $bearsamppCore->getResourcesPath() . '/homepage/img/bearsampp.bmp');
        $this->wbProgressBar = $bearsamppWinbinder->createProgressBar($this->wbWindow, $gauge + 1, 42, 24, 390, 15);

        $this->setTextLoading($text);
        $this->incrProgressBar();
    }

    /**
     * Sets the loading text on the splash screen.
     *
     * @param string $caption The text to display on the splash screen.
     */
    public function setTextLoading($caption)
    {
        global $bearsamppWinbinder;

        $bearsamppWinbinder->drawRect($this->wbWindow, 42, 0, self::WINDOW_WIDTH - 42, self::WINDOW_HEIGHT);
        $this->wbTextLoading = $bearsamppWinbinder->drawText($this->wbWindow, $caption, 42, 0, self::WINDOW_WIDTH - 44, 25);
    }

    /**
     * Increments the progress bar by the specified number of steps.
     *
     * @param int $nb The number of steps to increment the progress bar. Default is 1.
     */
    public function incrProgressBar($nb = 1)
    {
        global $bearsamppCore, $bearsamppWinbinder;

        for ($i = 0; $i < $nb; $i++) {
            $bearsamppWinbinder->drawImage($this->wbWindow, $bearsamppCore->getResourcesPath() . '/homepage/img/bearsampp.bmp', 4, 4, 32, 32);
            $bearsamppWinbinder->incrProgressBar($this->wbProgressBar);
        }

        $bearsamppWinbinder->wait();
        $bearsamppWinbinder->wait($this->wbWindow);
    }

    /**
     * Retrieves the WinBinder window object.
     *
     * @return mixed The WinBinder window object.
     */
    public function getWbWindow()
    {
        return $this->wbWindow;
    }
}
