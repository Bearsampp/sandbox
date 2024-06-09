<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ToolPython
 *
 * This class represents a Python tool module in the Bearsampp application. It extends the abstract `Module` class
 * and provides specific functionality for managing Python versions and executables.
 */
class ToolPython extends Module
{
    /**
     * Configuration key for the root Python version.
     */
    const ROOT_CFG_VERSION = 'pythonVersion';

    /**
     * Configuration key for the local Python executable.
     */
    const LOCAL_CFG_EXE = 'pythonExe';

    /**
     * Configuration key for the local Python CP executable.
     */
    const LOCAL_CFG_CP_EXE = 'pythonCpExe';

    /**
     * Configuration key for the local Python IDLE executable.
     */
    const LOCAL_CFG_IDLE_EXE = 'pythonIdleExe';

    /**
     * @var string Path to the Python executable.
     */
    private $exe;

    /**
     * @var string Path to the Python CP executable.
     */
    private $cpExe;

    /**
     * @var string Path to the Python IDLE executable.
     */
    private $idleExe;

    /**
     * ToolPython constructor.
     *
     * Initializes the ToolPython instance and reloads its configuration.
     *
     * @param string $id The module ID.
     * @param string $type The module type.
     */
    public function __construct($id, $type) {
        Util::logInitClass($this);
        $this->reload($id, $type);
    }

    /**
     * Reloads the module configuration.
     *
     * This method reloads the module configuration, including paths to executables and configuration files.
     * It also logs errors if certain files or directories are not found.
     *
     * @param string|null $id The module ID (optional).
     * @param string|null $type The module type (optional).
     */
    public function reload($id = null, $type = null) {
        global $bearsamppConfig, $bearsamppLang;
        Util::logReloadClass($this);

        $this->name = $bearsamppLang->getValue(Lang::PYTHON);
        $this->version = $bearsamppConfig->getRaw(self::ROOT_CFG_VERSION);
        parent::reload($id, $type);

        if ($this->bearsamppConfRaw !== false) {
            $this->exe = $this->symlinkPath . '/' . $this->bearsamppConfRaw[self::LOCAL_CFG_EXE];
            $this->cpExe = $this->symlinkPath . '/' . $this->bearsamppConfRaw[self::LOCAL_CFG_CP_EXE];
            $this->idleExe = $this->symlinkPath . '/' . $this->bearsamppConfRaw[self::LOCAL_CFG_IDLE_EXE];
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
        if (!is_file($this->cpExe)) {
            Util::logError(sprintf($bearsamppLang->getValue(Lang::ERROR_EXE_NOT_FOUND), $this->name . ' ' . $this->version, $this->cpExe));
        }
        if (!is_file($this->idleExe)) {
            Util::logError(sprintf($bearsamppLang->getValue(Lang::ERROR_EXE_NOT_FOUND), $this->name . ' ' . $this->version, $this->idleExe));
        }
    }

    /**
     * Sets the Python version for the module.
     *
     * This method updates the Python version in the configuration and reloads the module.
     *
     * @param string $version The new Python version.
     */
    public function setVersion($version) {
        global $bearsamppConfig;
        $this->version = $version;
        $bearsamppConfig->replace(self::ROOT_CFG_VERSION, $version);
        $this->reload();
    }

    /**
     * Gets the path to the Python executable.
     *
     * @return string The path to the Python executable.
     */
    public function getExe() {
        return $this->exe;
    }

    /**
     * Gets the path to the Python CP executable.
     *
     * @return string The path to the Python CP executable.
     */
    public function getCpExe() {
        return $this->cpExe;
    }

    /**
     * Gets the path to the Python IDLE executable.
     *
     * @return string The path to the Python IDLE executable.
     */
    public function getIdleExe() {
        return $this->idleExe;
    }
}
