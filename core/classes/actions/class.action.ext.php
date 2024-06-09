<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionExt
 *
 * This class handles external actions such as starting, stopping, reloading, and refreshing services.
 * It processes command line arguments to determine the action to be performed and executes the corresponding method.
 * The class also logs the status and response of each action.
 *
 * Constants:
 * - START: Represents the 'start' action.
 * - STOP: Represents the 'stop' action.
 * - RELOAD: Represents the 'reload' action.
 * - REFRESH: Represents the 'refresh' action.
 * - STATUS_ERROR: Represents an error status.
 * - STATUS_WARNING: Represents a warning status.
 * - STATUS_SUCCESS: Represents a success status.
 *
 * Properties:
 * - $status: Holds the current status of the action.
 * - $logs: Holds the logs generated during the action execution.
 *
 * Methods:
 * - __construct($args): Initializes the class with command line arguments and processes the action.
 * - getProcs(): Returns the list of available actions.
 * - addLog($data): Adds a log entry.
 * - setStatus($status): Sets the current status.
 * - sendLogs(): Sends the logs as a JSON-encoded response.
 * - procStart($args): Handles the 'start' action.
 * - procStop($args): Handles the 'stop' action.
 * - procReload($args): Handles the 'reload' action.
 * - procRefresh($args): Handles the 'refresh' action.
 */
class ActionExt
{
    const START = 'start';
    const STOP = 'stop';
    const RELOAD = 'reload';
    const REFRESH = 'refresh';

    const STATUS_ERROR = 2;
    const STATUS_WARNING = 1;
    const STATUS_SUCCESS = 0;

    private $status = self::STATUS_SUCCESS;
    private $logs = '';

    /**
     * Constructor for the ActionExt class.
     *
     * @param array $args Command line arguments.
     */
    public function __construct($args)
    {
        if (!isset($args[0]) || empty($args[0])) {
            $this->addLog('No args defined');
            $this->addLog('Available args:');
            foreach ($this->getProcs() as $proc) {
                $this->addLog('- ' . $proc);
            }
            $this->setStatus(self::STATUS_ERROR);
            $this->sendLogs();
            return;
        }

        $action = $args[0];

        $newArgs = array();
        foreach ($args as $key => $arg) {
            if ($key > 0) {
                $newArgs[] = $arg;
            }
        }

        $method = 'proc' . ucfirst($action);
        if (!method_exists($this, $method)) {
            $this->addLog('Unknown arg: ' . $action);
            $this->addLog('Available args:');
            foreach ($this->getProcs() as $procName => $procDesc) {
                $this->addLog('- ' . $procName . ': ' . $procDesc);
            }
            $this->setStatus(self::STATUS_ERROR);
            $this->sendLogs();
            return;
        }

        call_user_func(array($this, $method), $newArgs);
        $this->sendLogs();
    }

    /**
     * Returns the list of available actions.
     *
     * @return array List of available actions.
     */
    private function getProcs()
    {
        return array(
            self::START,
            self::STOP,
            self::RELOAD,
            self::REFRESH
        );
    }

    /**
     * Adds a log entry.
     *
     * @param string $data Log entry data.
     */
    private function addLog($data)
    {
        $this->logs .= $data . "\n";
    }

    /**
     * Sets the current status.
     *
     * @param int $status Status code.
     */
    private function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Sends the logs as a JSON-encoded response.
     */
    private function sendLogs()
    {
        echo json_encode(array(
            'status' => $this->status,
            'response' => $this->logs
        ));
    }

    /**
     * Handles the 'start' action.
     *
     * @param array $args Command line arguments.
     */
    private function procStart($args)
    {
        global $bearsamppRoot, $bearsamppWinbinder;

        if (!Util::isLaunched()) {
            $this->addLog('Starting ' . APP_TITLE);
            $bearsamppWinbinder->exec($bearsamppRoot->getExeFilePath(), null, false);
        } else {
            $this->addLog(APP_TITLE . ' already started');
            $this->setStatus(self::STATUS_WARNING);
        }
    }

    /**
     * Handles the 'stop' action.
     *
     * @param array $args Command line arguments.
     */
    private function procStop($args)
    {
        global $bearsamppBins;

        if (Util::isLaunched()) {
            $this->addLog('Remove services');
            foreach ($bearsamppBins->getServices() as $sName => $service) {
                if ($service->delete()) {
                    $this->addLog('- ' . $sName . ': OK');
                } else {
                    $this->addLog('- ' . $sName . ': KO');
                    $this->setStatus(self::STATUS_ERROR);
                }
            }

            $this->addLog('Stop ' . APP_TITLE);
            Batch::exitAppStandalone();
        } else {
            $this->addLog(APP_TITLE . ' already stopped');
            $this->setStatus(self::STATUS_WARNING);
        }
    }

    /**
     * Handles the 'reload' action.
     *
     * @param array $args Command line arguments.
     */
    private function procReload($args)
    {
        global $bearsamppRoot, $bearsamppBins, $bearsamppWinbinder;

        if (!Util::isLaunched()) {
            $this->addLog(APP_TITLE . ' is not started.');
            $bearsamppWinbinder->exec($bearsamppRoot->getExeFilePath(), null, false);
            $this->addLog('Start ' . APP_TITLE);
            $this->setStatus(self::STATUS_WARNING);
            return;
        }

        $this->addLog('Remove services');
        foreach ($bearsamppBins->getServices() as $sName => $service) {
            if ($service->delete()) {
                $this->addLog('- ' . $sName . ': OK');
            } else {
                $this->addLog('- ' . $sName . ': KO');
                $this->setStatus(self::STATUS_ERROR);
            }
        }

        Win32Ps::killBins();

        $this->addLog('Start services');
        foreach ($bearsamppBins->getServices() as $sName => $service) {
            $service->create();
            if ($service->start()) {
                $this->addLog('- ' . $sName . ': OK');
            } else {
                $this->addLog('- ' . $sName . ': KO');
                $this->setStatus(self::STATUS_ERROR);
            }
        }
    }

    /**
     * Handles the 'refresh' action.
     *
     * @param array $args Command line arguments.
     */
    private function procRefresh($args)
    {
        global $bearsamppAction;

        if (!Util::isLaunched()) {
            $this->addLog(APP_TITLE . ' is not started.');
            $this->setStatus(self::STATUS_ERROR);
            return;
        }

        $bearsamppAction->call(Action::RELOAD);
    }
}
