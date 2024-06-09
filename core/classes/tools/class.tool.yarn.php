<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ToolYarn
 *
 * This class represents the Yarn tool module in the Bearsampp application. It extends the `Module` class
 * and provides specific functionalities for managing the Yarn tool, including loading configurations,
 * setting versions, and handling executable paths.
 */
class ToolYarn extends Module
{
    /**
     * Configuration key for the Yarn version in the root configuration.
     */
    const ROOT_CFG_VERSION = 'yarnVersion';

    /**
     * Configuration key for the Yarn executable in the local configuration.
     */
    const LOCAL_CFG_EXE = 'yarnExe';

    /**
     * @var string Path to the Yarn executable.
     */
    private $exe;

    /**
     * Constructor for the ToolYarn class.
     *
     * Initializes the class by logging its initialization and reloading its configuration.
     *
     * @param string $id The ID of the module.
     * @param string $type The type of the module.
     */
    public function __construct($id, $type) {
        Util::logInitClass($this);
        $this->reload($id, $type);
    }

    /**
     * Reloads the configuration for the Yarn tool.
     *
     * This method reloads the configuration for the Yarn tool, including setting the name, version,
     * and executable path. It also performs various checks to ensure the configuration is valid.
     *
     * @param string|null $id The ID of the module (optional).
     * @param string|null $type The type of the module (optional).
     */
    public function reload($id = null, $type = null) {
        global $bearsamppConfig, $bearsamppLang;
        Util::logReloadClass($this);

        $this->name = $bearsamppLang->getValue(Lang::YARN);
        $this->version = $bearsamppConfig->getRaw(self::ROOT_CFG_VERSION);
        parent::reload($id, $type);

        if ($this->bearsamppConfRaw !== false) {
            $this->exe = $this->symlinkPath . '/' . $this->bearsamppConfRaw[self::LOCAL_CFG_EXE];
        }

        if (!$this->enable) {
            Util::logInfo($this->name . ' is not enabled!');
            return;
        }
        if (!is_dir($this->currentPath)) {
            Util::logError(sprintf($bearsamppLang->getValue(Lang::ERROR_FILE_NOT_FOUND), $this->name . ' ' . $this->version, $this->currentPath));
        }
        if (!is_dir($this->symlinkPath)) {
            Util::logError(sprintf($bearsamppLang->getValue(Lang::ERROR_FILE_NOT_FOUND), $this->name . ' ' . $this->version, $this->symlinkPath));
            return;
        }
        if (!is_file($this->bearsamppConf)) {
            Util::logError(sprintf($bearsamppLang->getValue(Lang::ERROR_CONF_NOT_FOUND), $this->name . ' ' . $this->version, $this->bearsamppConf));
        }
        if (!is_file($this->exe)) {
            Util::logError(sprintf($bearsamppLang->getValue(Lang::ERROR_EXE_NOT_FOUND), $this->name . ' ' . $this->version, $this->exe));
        }
    }

    /**
     * Sets the version of the Yarn tool.
     *
     * This method updates the version of the Yarn tool in the configuration and reloads the module.
     *
     * @param string $version The new version to set.
     */
    public function setVersion($version) {
        global $bearsamppConfig;
        $this->version = $version;
        $bearsamppConfig->replace(self::ROOT_CFG_VERSION, $version);
        $this->reload();
    }

    /**
     * Gets the path to the Yarn executable.
     *
     * @return string The path to the Yarn executable.
     */
    public function getExe() {
        return $this->exe;
    }
}
