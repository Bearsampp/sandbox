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
 * Class Tools
 *
 * This class manages various tool modules in the Bearsampp application.
 * It provides methods to retrieve and update the configuration of these tools. * @since 2022.2.16
     
 */
class Tools
{
    /**
     * The type of the tools. * @since 2022.2.16
     
     */
    const TYPE = 'tools';

    /**
     * @var ToolComposer|null The Composer tool instance. * @since 2022.2.16
     
     */
    private $composer;

    /**
     * @var ToolBruno|null The Bruno tool instance. * @since 2022.2.16
     
     */
    private $bruno;

    /**
     * @var ToolPowerShell|null The PowerShell tool instance. * @since 2022.2.16
     
     */
    private $powershell;

    /**
     * @var ToolGhostscript|null The Ghostscript tool instance. * @since 2022.2.16
     
     */
    private $ghostscript;

    /**
     * @var ToolGit|null The Git tool instance. * @since 2022.2.16
     
     */
    private $git;

    /**
     * @var ToolNgrok|null The Ngrok tool instance. * @since 2022.2.16
     
     */
    private $ngrok;

    /**
     * @var ToolPerl|null The Perl tool instance. * @since 2022.2.16
     
     */
    private $perl;

    /**
     * @var ToolPython|null The Python tool instance. * @since 2022.2.16
     
     */
    private $python;

    /**
     * @var ToolRuby|null The Ruby tool instance. * @since 2022.2.16
     
     */
    private $ruby;

    /**
     * Constructor for the Tools class. * @since 2022.2.16
     
     */
    public function __construct()
    {
    }

    /**
     * Updates the configuration of all tools. * @since 2022.2.16
     
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
     * @return array An array of all tool instances. * @since 2022.2.16
     
     */
    public function getAll()
    {
        return array(
            $this->getBruno(),
            $this->getComposer(),
            $this->getPowerShell(),
            $this->getGhostscript(),
            $this->getGit(),
            $this->getNgrok(),
            $this->getPerl(),
            $this->getPython(),
            $this->getRuby(),
        );
    }

    /**
     * Retrieves the Bruno tool instance.
     *
     * @return ToolBruno The Bruno tool instance. * @since 2022.2.16
     
     */
    public function getBruno()
    {
        if ($this->bruno == null) {
            $this->bruno = new ToolBruno('bruno', self::TYPE);
        }
        return $this->bruno;
    }

    /**
     * Retrieves the Composer tool instance.
     *
     * @return ToolComposer The Composer tool instance. * @since 2022.2.16
     
     */
    public function getComposer()
    {
        if ($this->composer == null) {
            $this->composer = new ToolComposer('composer', self::TYPE);
        }
        return $this->composer;
    }

    /**
     * Retrieves the PowerShell tool instance.
     *
     * @return ToolPowerShell The PowerShell tool instance. * @since 2022.2.16
     
     */
    public function getPowerShell()
    {
        if ($this->powershell == null) {
            $this->powershell = new ToolPowerShell('powershell', self::TYPE);
        }
        return $this->powershell;
    }

    /**
     * Retrieves the Ghostscript tool instance.
     *
     * @return ToolGhostscript The Ghostscript tool instance. * @since 2022.2.16
     
     */
    public function getGhostscript()
    {
        if ($this->ghostscript == null) {
            $this->ghostscript = new ToolGhostscript('ghostscript', self::TYPE);
        }
        return $this->ghostscript;
    }

    /**
     * Retrieves the Git tool instance.
     *
     * @return ToolGit The Git tool instance. * @since 2022.2.16
     
     */
    public function getGit()
    {
        if ($this->git == null) {
            $this->git = new ToolGit('git', self::TYPE);
        }
        return $this->git;
    }

    /**
     * Retrieves the Git GUI tool instance.
     *
     * @return ToolGit The Git GUI tool instance. * @since 2022.2.16
     
     */
    public function getGitGui()
    {
        if ($this->git == null) {
            $this->git = new ToolGit('git-gui', self::TYPE);
        }
        return $this->git;
    }

    /**
     * Retrieves the Ngrok tool instance.
     *
     * @return ToolNgrok The Ngrok tool instance. * @since 2022.2.16
     
     */
    public function getNgrok()
    {
        if ($this->ngrok == null) {
            $this->ngrok = new ToolNgrok('ngrok', self::TYPE);
        }
        return $this->ngrok;
    }

    /**
     * Retrieves the Perl tool instance.
     *
     * @return ToolPerl The Perl tool instance. * @since 2022.2.16
     
     */
    public function getPerl()
    {
        if ($this->perl == null) {
            $this->perl = new ToolPerl('perl', self::TYPE);
        }
        return $this->perl;
    }

    /**
     * Retrieves the Python tool instance.
     *
     * @return ToolPython The Python tool instance. * @since 2022.2.16
     
     */
    public function getPython()
    {
        if ($this->python == null) {
            $this->python = new ToolPython('python', self::TYPE);
        }
        return $this->python;
    }

    /**
     * Retrieves the Ruby tool instance.
     *
     * @return ToolRuby The Ruby tool instance. * @since 2022.2.16
     
     */
    public function getRuby()
    {
        if ($this->ruby == null) {
            $this->ruby = new ToolRuby('ruby', self::TYPE);
        }
        return $this->ruby;
    }
}
