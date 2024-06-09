<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class Tools
 *
 * This class manages various tool modules within the Bearsampp application.
 * It provides methods to retrieve and update the configuration of these tools.
 */
class Tools
{
    /**
     * Constant representing the type of tools.
     */
    const TYPE = 'tools';

    /**
     * @var ToolComposer|null $composer Instance of the ToolComposer class.
     */
    private $composer;

    /**
     * @var ToolConsoleZ|null $consolez Instance of the ToolConsoleZ class.
     */
    private $consolez;

    /**
     * @var ToolGhostscript|null $ghostscript Instance of the ToolGhostscript class.
     */
    private $ghostscript;

    /**
     * @var ToolGit|null $git Instance of the ToolGit class.
     */
    private $git;

    /**
     * @var ToolNgrok|null $ngrok Instance of the ToolNgrok class.
     */
    private $ngrok;

    /**
     * @var ToolPerl|null $perl Instance of the ToolPerl class.
     */
    private $perl;

    /**
     * @var ToolPython|null $python Instance of the ToolPython class.
     */
    private $python;

    /**
     * @var ToolRuby|null $ruby Instance of the ToolRuby class.
     */
    private $ruby;

    /**
     * @var ToolXdc|null $xdc Instance of the ToolXdc class.
     */
    private $xdc;

    /**
     * @var ToolYarn|null $yarn Instance of the ToolYarn class.
     */
    private $yarn;

    /**
     * Constructor for the Tools class.
     */
    public function __construct()
    {
    }

    /**
     * Updates the configuration of all tools.
     * Logs the update process and calls the update method on each tool instance.
     */
    public function update()
    {
        Util::logInfo('Update tools config');
        foreach ($this->getAll() as $tool) {
            $tool->update();
        }
    }

    /**
     * Retrieves all tool instances.
     *
     * @return array An array of tool instances.
     */
    public function getAll()
    {
        return array(
            $this->getComposer(),
            $this->getConsoleZ(),
            $this->getGhostscript(),
            $this->getGit(),
            $this->getNgrok(),
            $this->getPerl(),
            $this->getPython(),
            $this->getRuby(),
            $this->getXdc(),
            $this->getYarn(),
        );
    }

    /**
     * Retrieves the ToolComposer instance.
     *
     * @return ToolComposer The ToolComposer instance.
     */
    public function getComposer()
    {
        if ($this->composer == null) {
            $this->composer = new ToolComposer('composer', self::TYPE);
        }
        return $this->composer;
    }

    /**
     * Retrieves the ToolConsoleZ instance.
     *
     * @return ToolConsoleZ The ToolConsoleZ instance.
     */
    public function getConsoleZ()
    {
        if ($this->consolez == null) {
            $this->consolez = new ToolConsoleZ('consolez', self::TYPE);
        }
        return $this->consolez;
    }

    /**
     * Retrieves the ToolGhostscript instance.
     *
     * @return ToolGhostscript The ToolGhostscript instance.
     */
    public function getGhostscript()
    {
        if ($this->ghostscript == null) {
            $this->ghostscript = new ToolGhostscript('ghostscript', self::TYPE);
        }
        return $this->ghostscript;
    }

    /**
     * Retrieves the ToolGit instance.
     *
     * @return ToolGit The ToolGit instance.
     */
    public function getGit()
    {
        if ($this->git == null) {
            $this->git = new ToolGit('git', self::TYPE);
        }
        return $this->git;
    }

    /**
     * Retrieves the ToolGit instance for Git GUI.
     *
     * @return ToolGit The ToolGit instance for Git GUI.
     */
    public function getGitGui()
    {
        if ($this->git == null) {
            $this->git = new ToolGit('git-gui', self::TYPE);
        }
        return $this->git;
    }

    /**
     * Retrieves the ToolNgrok instance.
     *
     * @return ToolNgrok The ToolNgrok instance.
     */
    public function getNgrok()
    {
        if ($this->ngrok == null) {
            $this->ngrok = new ToolNgrok('ngrok', self::TYPE);
        }
        return $this->ngrok;
    }

    /**
     * Retrieves the ToolPerl instance.
     *
     * @return ToolPerl The ToolPerl instance.
     */
    public function getPerl()
    {
        if ($this->perl == null) {
            $this->perl = new ToolPerl('perl', self::TYPE);
        }
        return $this->perl;
    }

    /**
     * Retrieves the ToolPython instance.
     *
     * @return ToolPython The ToolPython instance.
     */
    public function getPython()
    {
        if ($this->python == null) {
            $this->python = new ToolPython('python', self::TYPE);
        }
        return $this->python;
    }

    /**
     * Retrieves the ToolRuby instance.
     *
     * @return ToolRuby The ToolRuby instance.
     */
    public function getRuby()
    {
        if ($this->ruby == null) {
            $this->ruby = new ToolRuby('ruby', self::TYPE);
        }
        return $this->ruby;
    }

    /**
     * Retrieves the ToolXdc instance.
     *
     * @return ToolXdc The ToolXdc instance.
     */
    public function getXdc()
    {
        if ($this->xdc == null) {
            $this->xdc = new ToolXdc('xdc', self::TYPE);
        }
        return $this->xdc;
    }

    /**
     * Retrieves the ToolYarn instance.
     *
     * @return ToolYarn The ToolYarn instance.
     */
    public function getYarn()
    {
        if ($this->yarn == null) {
            $this->yarn = new ToolYarn('yarn', self::TYPE);
        }
        return $this->yarn;
    }
}
