<?php
/*
 * Copyright (c) 2-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: @author@
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */
/**
 * Class ActionCheckVersion
 *
 * This class is responsible for checking the current version of the application and displaying a window
 * with the latest version information if an update is available. It also handles the user interaction with
 * the window, such as clicking on links or buttons.
 *
 * @package Bearsampp
 */
class ActionCheckVersion
{
    const DISPLAY_OK = 'displayOk';

    private $wbWindow;

    private $wbImage;
    private $wbLinkChangelog;
    private $wbLinkFull;
    private $wbBtnOk;

    private $currentVersion;
    private $latestVersion;

    private $latestVersionUrl;

    /**
     * Constructor for the ActionCheckVersion class.
     * Initializes the class, checks for the latest version of the application, and displays update information if necessary.
     *
     * @param array $args Arguments that may affect the display behavior, such as forcing an "OK" message box.
     */
    public function __construct($args)
    {
        global $bearsamppCore, $bearsamppLang, $bearsamppWinbinder, $appGithubHeader;

        if (!file_exists($bearsamppCore->getExec())) {
            Util::startLoading();
            $this->currentVersion = $bearsamppCore->getAppVersion();

            // Assuming getLatestVersion now returns an array with version and URL
            $latestVersionData = Util::getLatestVersion(APP_GITHUB_LATEST_URL, APP_GITHUB_TOKEN, $appGithubHeader);

            if ($latestVersionData != null) {
                $bearsamppLatestVersion = $latestVersionData['version'];
                $this->latestVersionUrl = $latestVersionData['url']; // URL of the latest version
                if (version_compare($this->currentVersion, $bearsamppLatestVersion, '<')) {
                    $this->showVersionUpdateWindow($bearsamppLang, $bearsamppWinbinder, $bearsamppCore, $bearsamppLatestVersion);
                } elseif (isset($args[0]) && !empty($args[0]) && $args[0] == self::DISPLAY_OK) {
                    $this->showVersionOkMessageBox($bearsamppLang, $bearsamppWinbinder);
                }
            }
        }
    }

    /**
     * Displays a window with information about the available application update.
     * This window includes links to download the new version and an OK button.
     *
     * @param Lang $lang Language processing object for retrieving language-specific values.
     * @param WinBinder $winbinder WinBinder object for creating GUI elements.
     * @param Core $core Core application object for accessing application-specific paths and settings.
     * @param string $bearsamppLatestVersion The latest version string of the application.
     */
    private function showVersionUpdateWindow($lang, $winbinder, $core, $bearsamppLatestVersion)
    {
        $labelFullLink = $lang->getValue(Lang::DOWNLOAD) . ' ' . APP_TITLE . ' ' . $bearsamppLatestVersion;

        $winbinder->reset();
        $this->wbWindow = $winbinder->createAppWindow($lang->getValue(Lang::CHECK_VERSION_TITLE), 480, 170, WBC_NOTIFY, WBC_KEYDOWN | WBC_KEYUP);

        $winbinder->createLabel($this->wbWindow, $lang->getValue(Lang::CHECK_VERSION_AVAILABLE_TEXT), 80, 35, 470, 120);

        $this->wbLinkFull = $winbinder->createHyperLink($this->wbWindow, $labelFullLink, 80, 87, 300, 20, WBC_LINES | WBC_RIGHT);

        $this->wbBtnOk = $winbinder->createButton($this->wbWindow, $lang->getValue(Lang::BUTTON_OK), 380, 103);
        $this->wbImage = $winbinder->drawImage($this->wbWindow, $core->getResourcesPath() . '/icons/about.bmp');

        Util::stopLoading();
        $winbinder->setHandler($this->wbWindow, $this, 'processWindow');
        $winbinder->mainLoop();
        $winbinder->reset();
    }

    /**
     * Displays a message box indicating that the current version is the latest.
     * This is typically called when there are no updates available.
     *
     * @param Lang $lang Language processing object for retrieving language-specific values.
     * @param WinBinder $winbinder WinBinder object for creating GUI elements.
     */
    private function showVersionOkMessageBox($lang, $winbinder)
    {
        Util::stopLoading();
        $winbinder->messageBoxInfo(
            $lang->getValue(Lang::CHECK_VERSION_LATEST_TEXT),
            $lang->getValue(Lang::CHECK_VERSION_TITLE)
        );
    }

    /**
     * Handles user interactions within the version update window.
     * Processes events like clicking on links or buttons.
     *
     * @param resource $window The handle to the window.
     * @param int $id The control ID of the event source.
     * @param mixed $ctrl The control object of the event source.
     * @param mixed $param1 Additional parameter providing event-specific information.
     * @param mixed $param2 Additional parameter providing event-specific information.
     */
    public function processWindow($window, $id, $ctrl, $param1, $param2)
    {
        global $bearsamppConfig, $bearsamppWinbinder;

        switch ($id) {
            case $this->wbLinkFull[WinBinder::CTRL_ID]:
                $bearsamppWinbinder->exec($bearsamppConfig->getBrowser(), $this->latestVersionUrl);
                break;
            case IDCLOSE:
            case $this->wbBtnOk[WinBinder::CTRL_ID]:
                $bearsamppWinbinder->destroyWindow($window);
                break;
        }
    }
}
