<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ToolGhostscript
 *
 * This class represents the Ghostscript tool module in the Bearsampp application.
 * It extends the abstract Module class and provides specific functionalities for managing
 * the Ghostscript tool, including loading configurations, setting versions, and retrieving executable paths.
 */
class ToolGhostscript extends Module
{
    /**
     * Configuration key for the Ghostscript version.
     */
    const ROOT_CFG_VERSION = 'ghostscriptVersion';

    /**
     * Configuration key for the Ghostscript executable path.
     */
    const LOCAL_CFG_EXE = 'ghostscriptExe';

    /**
     * Configuration key for the Ghostscript console executable path.
     */
    const LOCAL_CFG_EXE_CONSOLE = 'ghostscriptExeConsole';

    /**
     * @var string Path to the Ghostscript executable.
     */
    private $exe;

    /**
     * @var string Path to the Ghostscript console executable.
     */
    private $exeConsole;

    /**
     * Constructor for the ToolGhostscript class.
     *
     * @param string $id The ID of the module.
     * @param string $type The type of the module.
     */
    public function __construct($id, $type) {
        Util::logInitClass($this);
        $this->reload($id, $type);
    }

    /**
     * Reloads the module configuration and updates the internal state.
     *
     * @param string|null $id The ID of the module (optional).
     * @param string|null $type The type of the module (optional).
     */
    public function reload($id = null, $type = null) {
        global $bearsamppConfig, $bearsamppLang;
        Util::logReloadClass($this);

        $this->name = $bearsamppLang->getValue(Lang::GHOSTSCRIPT);
        $this->version = $bearsamppConfig->getRaw(self::ROOT_CFG_VERSION);
        parent::reload($id, $type);

        if ($this->bearsamppConfRaw !== false) {
            $this->exe = $this->symlinkPath . '/' . $this->bearsamppConfRaw[self::LOCAL_CFG_EXE];
            $this->exeConsole = $this->symlinkPath . '/' . $this->bearsamppConfRaw[self::LOCAL_CFG_EXE_CONSOLE];
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
        if (!is_file($this->exeConsole)) {
            Util::logError(sprintf($bearsamppLang->getValue(Lang::ERROR_EXE_NOT_FOUND), $this->name . ' ' . $this->version, $this->exeConsole));
        }
    }

    /**
     * Sets the version of the Ghostscript tool and updates the configuration.
     *
     * @param string $version The version to set.
     */
    public function setVersion($version) {
        global $bearsamppConfig;
        $this->version = $version;
        $bearsamppConfig->replace(self::ROOT_CFG_VERSION, $version);
        $this->reload();
    }

    /**
     * Retrieves the path to the Ghostscript executable.
     *
     * @return string The path to the Ghostscript executable.
     */
    public function getExe() {
        return $this->exe;
    }

    /**
     * Retrieves the path to the Ghostscript console executable.
     *
     * @return string The path to the Ghostscript console executable.
     */
    public function getExeConsole() {
        return $this->exeConsole;
    }
}
