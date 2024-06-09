<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionAbout
 *
 * This class is responsible for creating and managing the "About" window in the Bearsampp application.
 * It initializes the window, sets up various UI elements such as labels, hyperlinks, and buttons,
 * and handles user interactions with these elements.
 */
class ActionAbout
{
    /**
     * @var mixed $wbWindow The main window object for the "About" window.
     */
    private $wbWindow;

    /**
     * @var mixed $wbImage The image object displayed in the "About" window.
     */
    private $wbImage;

    /**
     * @var mixed $wbLinkHomepage The hyperlink object for the homepage link.
     */
    private $wbLinkHomepage;

    /**
     * @var mixed $wbLinkDonate The hyperlink object for the donate link.
     */
    private $wbLinkDonate;

    /**
     * @var mixed $wbLinkGithub The hyperlink object for the GitHub link.
     */
    private $wbLinkGithub;

    /**
     * @var mixed $wbBtnOk The button object for the OK button.
     */
    private $wbBtnOk;

    /**
     * Constant for gauge save value.
     */
    const GAUGE_SAVE = 2;

    /**
     * Constructor for the ActionAbout class.
     *
     * Initializes the "About" window, sets up UI elements, and starts the main event loop.
     *
     * @param array $args Arguments passed to the constructor.
     */
    public function __construct($args)
    {
        global $bearsamppCore, $bearsamppLang, $bearsamppWinbinder;

        $bearsamppWinbinder->reset();
        $this->wbWindow = $bearsamppWinbinder->createAppWindow($bearsamppLang->getValue(Lang::ABOUT_TITLE), 500, 250, WBC_NOTIFY, WBC_KEYDOWN | WBC_KEYUP);

        $aboutText = sprintf($bearsamppLang->getValue(Lang::ABOUT_TEXT), APP_TITLE . ' ' . $bearsamppCore->getAppVersion(), date('Y'), APP_AUTHOR_NAME);
        $bearsamppWinbinder->createLabel($this->wbWindow, $aboutText, 80, 20, 470, 120);

        $bearsamppWinbinder->createLabel($this->wbWindow, $bearsamppLang->getValue(Lang::WEBSITE) . ' :', 80, 105, 470, 15);
        $this->wbLinkHomepage = $bearsamppWinbinder->createHyperLink($this->wbWindow, Util::getWebsiteUrlNoUtm(), 180, 105, 300, 15, WBC_LINES);

        $bearsamppWinbinder->createLabel($this->wbWindow, $bearsamppLang->getValue(Lang::DONATE) . ' :', 80, 125, 470, 15);
        $this->wbLinkDonate = $bearsamppWinbinder->createHyperLink($this->wbWindow, Util::getWebsiteUrlNoUtm('donate'), 180, 125, 300, 15, WBC_LINES | WBC_RIGHT);

        $bearsamppWinbinder->createLabel($this->wbWindow, $bearsamppLang->getValue(Lang::GITHUB) . ' :', 80, 145, 470, 15);
        $this->wbLinkGithub = $bearsamppWinbinder->createHyperLink($this->wbWindow, Util::getGithubUserUrl(), 180, 145, 300, 15, WBC_LINES | WBC_RIGHT);

        $this->wbBtnOk = $bearsamppWinbinder->createButton($this->wbWindow, $bearsamppLang->getValue(Lang::BUTTON_OK), 390, 180);

        $this->wbImage = $bearsamppWinbinder->drawImage($this->wbWindow, $bearsamppCore->getResourcesPath() . '/homepage/img/about.bmp');

        $bearsamppWinbinder->setHandler($this->wbWindow, $this, 'processWindow');
        $bearsamppWinbinder->mainLoop();
        $bearsamppWinbinder->reset();
    }

    /**
     * Processes window events.
     *
     * Handles user interactions with the "About" window, such as clicking hyperlinks or the OK button.
     *
     * @param mixed $window The window object.
     * @param int $id The ID of the control that triggered the event.
     * @param mixed $ctrl The control object that triggered the event.
     * @param mixed $param1 Additional parameter 1.
     * @param mixed $param2 Additional parameter 2.
     */
    public function processWindow($window, $id, $ctrl, $param1, $param2)
    {
        global $bearsamppConfig, $bearsamppWinbinder;

        switch ($id) {
            case $this->wbLinkHomepage[WinBinder::CTRL_ID]:
                $bearsamppWinbinder->exec($bearsamppConfig->getBrowser(), Util::getWebsiteUrl());
                break;
            case $this->wbLinkDonate[WinBinder::CTRL_ID]:
                $bearsamppWinbinder->exec($bearsamppConfig->getBrowser(), Util::getWebsiteUrl('donate'));
                break;
            case $this->wbLinkGithub[WinBinder::CTRL_ID]:
                $bearsamppWinbinder->exec($bearsamppConfig->getBrowser(), Util::getGithubUserUrl());
                break;
            case IDCLOSE:
            case $this->wbBtnOk[WinBinder::CTRL_ID]:
                $bearsamppWinbinder->destroyWindow($window);
                break;
        }
    }
}
