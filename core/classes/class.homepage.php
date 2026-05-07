<?php
/*
 *
 *  * Copyright (c) 2021-2024 Bearsampp
 *  * License:  GNU General Public License version 3 or later; see LICENSE.txt
 *  * Website: https://bearsampp.com
 *  * Github: https://github.com/Bearsampp
 *
 */

/**
 * Class Homepage
 *
 * This class handles the homepage functionalities of the Bearsampp application.
 * It manages the page navigation, resource paths, and content refresh operations.
 */
class Homepage
{
    const PAGE_INDEX = 'index';
    const PAGE_PHPINFO = 'phpinfo';

    private $page;

    /**
     * @var array List of valid pages for the homepage.
     */
    private $pageList = array(
        self::PAGE_INDEX,
        self::PAGE_PHPINFO,
    );

    /**
     * Homepage constructor.
     * Initializes the homepage class and sets the current page based on the query parameter.
     */
    public function __construct()
    {
        Log::initClass($this);

        $page = UtilInput::cleanGetVar('p');
        $this->page = !empty($page) && in_array($page, $this->pageList) ? $page : self::PAGE_INDEX;
    }

    /**
     * Gets the current page.
     *
     * @return string The current page.
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Constructs the page query string based on the provided query.
     *
     * @param string $query The query string to construct.
     * @return string The constructed page query string.
     */
    public function getPageQuery($query)
    {
        if (empty($query)) {
            return '';
        }

        if (in_array($query, $this->pageList)) {
            return $query !== self::PAGE_INDEX ? '?p=' . $query : 'index.php';
        }

        return '';
    }

    /**
     * Constructs the full URL for the given page query.
     *
     * @param string $query The query string to construct the URL for.
     * @return string The constructed page URL.
     */
    public function getPageUrl($query)
    {
        global $bearsamppRoot;
        return $bearsamppRoot->getLocalUrl($this->getPageQuery($query));
    }

    /**
     * Gets the path to the homepage directory.
     *
     * @return string The homepage directory path.
     */
    public function getHomepagePath()
    {
        return Path::getHomepagePath();
    }

    /**
     * Gets the URL alias for the images directory.
     *
     * @return string The images directory URL alias.
     */
    public function getImagesAlias()
    {
        return $this->getResourceAlias(false) . '/img/';
    }

    /**
     * Gets the URL alias for the icons directory.
     *
     * @return string The icons directory URL alias.
     */
    public function getIconsAlias()
    {
        return $this->getResourceAlias(false) . '/img/icons/';
    }

    /**
     * Gets the URL alias for the resources directory.
     *
     * @return string The resources directory URL alias.
     */
    public function getResourceAlias()
    {
        global $bearsamppCore;
        return md5(APP_TITLE);
    }

    /**
     * Gets the URL to the resources directory.
     *
     * @return string The resources directory URL.
     */
    public function getResourcesUrl()
    {
        global $bearsamppRoot;
        return $bearsamppRoot->getLocalUrl($this->getResourceAlias());
    }

    /**
     * Refreshes the alias content by updating the alias configuration file.
     *
     * @return bool True if the alias content was successfully refreshed, false otherwise.
     */
    public function refreshAliasContent()
    {
        global $bearsamppBins;

        $result = $bearsamppBins->getApache()->getAliasContent(
            $this->getResourceAlias(),
            $this->getHomepagePath()
        );

        return file_put_contents($this->getHomepagePath() . '/alias.conf', $result) !== false;
    }

    /**
     * Refreshes the commons JavaScript content by updating the _commons.js file.
     */
    public function refreshCommonsJsContent()
    {
        Util::replaceInFile($this->getHomepagePath() . '/js/_commons.js', array(
            '/^\s\surl:.*/' => '  url: "' . $this->getResourceAlias() . '/ajax.php"',
            '/AJAX_URL.*=.*/' => 'const AJAX_URL = "' . $this->getResourceAlias() . '/ajax.php"',
        ));
    }
}
