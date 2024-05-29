<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Manages the homepage content and URLs for the Bearsampp application.
 * This class handles the dynamic loading of pages, resources, and configurations
 * specific to the homepage based on user interactions and settings.
 */
class Homepage
{
    /**
     * Constants for identifying specific pages.
     */
    const PAGE_INDEX = 'index';
    const PAGE_PHPINFO = 'phpinfo';
    const PAGE_STDL_APC = 'apc.php';

    /**
     * @var string Current page identifier.
     */
    private $page;

    /**
     * @var array List of standard pages within the application.
     */
    private $pageList = array(
        self::PAGE_INDEX,
        self::PAGE_PHPINFO,
    );

    /**
     * @var array List of standard pages that include additional functionality.
     */
    private $pageStdl = array(
        self::PAGE_STDL_APC
    );

    /**
     * Constructor for the Homepage class.
     * Initializes the class and sets the current page based on the 'p' GET parameter.
     */
    public function __construct()
    {
        Util::logInitClass($this);

        $page = Util::cleanGetVar('p');
        $this->page = !empty($page) && in_array($page, $this->pageList) ? $page : self::PAGE_INDEX;
    }

    /**
     * Retrieves the current page identifier.
     *
     * @return string The current page identifier.
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Constructs a query string for a given page.
     *
     * @param string $query The page identifier to construct the query for.
     * @return string The constructed query string.
     */
    public function getPageQuery($query)
    {
        $request = '';
        if (!empty($query) && in_array($query, $this->pageList) && $query != self::PAGE_INDEX) {
            $request = '?p=' . $query;
        }
        elseif (!empty($query) && in_array($query, $this->pageStdl)) {
            $request = $query;
        }
        elseif (!empty($query) && self::PAGE_INDEX) {
            $request = "index.php";
        }
        return $request;
    }

    /**
     * Constructs a full URL for a given page query.
     *
     * @param string $query The page query to construct the URL for.
     * @return string The constructed URL.
     */
    public function getPageUrl($query)
    {
        global $bearsamppRoot;
        return $bearsamppRoot->getLocalUrl($this->getPageQuery($query));
    }

    /**
     * Retrieves the path to the homepage directory.
     *
     * @return string The path to the homepage directory.
     */
    public function getHomepagePath()
    {
        global $bearsamppCore;
        return $bearsamppCore->getResourcesPath(false) . '/homepage';
    }

    /**
     * Retrieves the path to the images directory used in the homepage.
     *
     * @return string The path to the images directory.
     */
    public function getImagesPath()
    {
        return $this->getResourcesPath(false) . '/img/';
    }

    /**
     * Retrieves the path to the icons directory used in the homepage.
     *
     * @return string The path to the icons directory.
     */
    public function getIconsPath()
    {
        return $this->getResourcesPath(false) . '/img/icons/';
    }

    /**
     * Retrieves the path to the resources directory.
     * The path is hashed for security reasons.
     *
     * @return string The hashed path to the resources directory.
     */
    public function getResourcesPath()
    {
        global $bearsamppCore;
        return md5(APP_TITLE);
    }

    /**
     * Retrieves the URL to the resources directory.
     *
     * @return string The URL to the resources directory.
     */
    public function getResourcesUrl()
    {
        global $bearsamppRoot;
        return $bearsamppRoot->getLocalUrl($this->getResourcesPath());
    }

    /**
     * Refreshes the content of the alias configuration file based on current settings.
     *
     * @return bool True if the file was successfully written, false otherwise.
     */
    public function refreshAliasContent()
    {
        global $bearsamppBins;

        $result = $bearsamppBins->getApache()->getAliasContent(
            $this->getResourcesPath(),
            $this->getHomepagePath());

        return file_put_contents($this->getHomepagePath() . '/alias.conf', $result) !== false;
    }

    /**
     * Updates the content of the common JavaScript file with dynamic paths.
     */
    public function refreshCommonsJsContent()
    {
        Util::replaceInFile($this->getHomepagePath() . '/js/_commons.js', array(
            '/^\s\surl:.*/' => '  url: "' . $this->getResourcesPath() . '/ajax.php"',
            '/AJAX_URL.*=.*/' => 'const AJAX_URL = "' . $this->getResourcesPath() . '/ajax.php"',
        ));
    }
}
