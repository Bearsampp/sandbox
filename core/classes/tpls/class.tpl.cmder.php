<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class TplCmder
 *
 * This class is responsible for updating the ConEmu configuration file for Cmder.
 * It reads the template user-ConEmu.xml and updates only the Tasks section
 * with dynamically generated tasks for installed tools.
 *
 * APPROACH: Load template → Update Tasks section → Save
 */
class TplCmder
{
    /**
     * Private constructor to prevent instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Processes the Cmder/ConEmu configuration file.
     *
     * This method loads the template user-ConEmu.xml from the module,
     * updates the Tasks section with dynamically generated tasks,
     * and writes the result to the runtime configuration location.
     */
    public static function process()
    {
        global $bearsamppTools;

        // Path to template in module
        $templatePath = $bearsamppTools->getCmder()->getCurrentPath() . '/config/user-ConEmu.xml';

        // Path to runtime config
        $configPath = $bearsamppTools->getCmder()->getConf();

        // Check if template exists
        if (!file_exists($templatePath)) {
            Util::logError('Cmder template user-ConEmu.xml not found at: ' . $templatePath);
            return;
        }

        // Load template XML
        $xml = @simplexml_load_file($templatePath);
        if ($xml === false) {
            $errors = libxml_get_errors();
            $errorMsg = 'Failed to parse Cmder template XML at: ' . $templatePath;
            foreach ($errors as $error) {
                $errorMsg .= "\n" . $error->message;
            }
            libxml_clear_errors();
            Util::logError($errorMsg);
            return;
        }

        // Set LoadCfgFile to use XML instead of registry
        $loadCfgNodes = $xml->xpath('//value[@name="LoadCfgFile"]');
        if (!empty($loadCfgNodes)) {
            $loadCfgNodes[0]['data'] = $configPath;
            Util::logError('Set LoadCfgFile to: ' . $configPath);
        }

        // Set TabConsole to use %s placeholder for title
        $tabConsoleNodes = $xml->xpath('//value[@name="TabConsole"]');
        if (!empty($tabConsoleNodes)) {
            $tabConsoleNodes[0]['data'] = '%s';
            Util::logError('Set TabConsole to: %s');
        }

        // Navigate to Tasks section
        Util::logError('Searching for Tasks section in XML...');
        $tasksNode = $xml->xpath('//key[@name="Tasks"]');
        Util::logError('XPath result count: ' . count($tasksNode));

        if (empty($tasksNode)) {
            Util::logError('Tasks section not found in Cmder template');
            Util::logError('XML root element: ' . $xml->getName());
            return;
        }

        Util::logError('Tasks section found, accessing first element...');
        $tasksNode = $tasksNode[0];
        Util::logError('Tasks node type: ' . get_class($tasksNode));

        // Remove existing task keys (SimpleXML way)
        $keysToRemove = array();
        foreach ($tasksNode->key as $key) {
            $keysToRemove[] = $key;
        }
        foreach ($keysToRemove as $key) {
            $dom = dom_import_simplexml($key);
            $dom->parentNode->removeChild($dom);
        }

        // Generate new tasks
        $tasks = self::generateTasks();
        Util::logError('Generated ' . count($tasks) . ' tasks');

        // Update task count
        foreach ($tasksNode->value as $value) {
            if ((string)$value['name'] === 'Count') {
                $value['data'] = count($tasks);
                Util::logError('Updated task count to: ' . count($tasks));
                break;
            }
        }

        // Add new tasks to XML
        foreach ($tasks as $index => $taskData) {
            $taskNum = $index + 1;
            $taskKey = $tasksNode->addChild('key');
            $taskKey->addAttribute('name', 'Task' . $taskNum);
            $taskKey->addAttribute('modified', date('Y-m-d H:i:s'));
            $taskKey->addAttribute('build', '230724');

            $taskKey->addChild('value')->addAttribute('name', 'Name');
            $taskKey->value[0]->addAttribute('type', 'string');
            $taskKey->value[0]->addAttribute('data', $taskData['name']);

            $taskKey->addChild('value')->addAttribute('name', 'Flags');
            $taskKey->value[1]->addAttribute('type', 'dword');
            $taskKey->value[1]->addAttribute('data', '00000000');

            $taskKey->addChild('value')->addAttribute('name', 'Hotkey');
            $taskKey->value[2]->addAttribute('type', 'dword');
            $taskKey->value[2]->addAttribute('data', '00000000');

            $taskKey->addChild('value')->addAttribute('name', 'GuiArgs');
            $taskKey->value[3]->addAttribute('type', 'string');
            $taskKey->value[3]->addAttribute('data', isset($taskData['guiargs']) ? $taskData['guiargs'] : '');

            $taskKey->addChild('value')->addAttribute('name', 'Cmd1');
            $taskKey->value[4]->addAttribute('type', 'string');
            $taskKey->value[4]->addAttribute('data', $taskData['cmd']);

            $taskKey->addChild('value')->addAttribute('name', 'Active');
            $taskKey->value[5]->addAttribute('type', 'long');
            $taskKey->value[5]->addAttribute('data', '0');

            $taskKey->addChild('value')->addAttribute('name', 'Count');
            $taskKey->value[6]->addAttribute('type', 'long');
            $taskKey->value[6]->addAttribute('data', '1');
        }

        // Save updated XML
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        // Add XML declaration and comment
        $result = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $result .= '<!-- Config file for Bearsampp Cmder -->' . PHP_EOL;
        $result .= preg_replace('/^<\?xml.*\?>\n/', '', $dom->saveXML());

        Util::logError('Saving config to: ' . $configPath);
        $bytesWritten = file_put_contents($configPath, $result);
        Util::logError('Bytes written: ' . $bytesWritten);
    }

    /**
     * Generates array of tasks for installed tools
     *
     * @return array Array of task data with 'name' and 'cmd' keys
     */
    private static function generateTasks()
    {
        global $bearsamppTools, $bearsamppBins;

        $tasks = array();

        // Task 1: Default CMD (always included)
        $tasks[] = array(
            'name' => '{cmd::Cmder}',
            'cmd' => 'cmd.exe /k "%ConEmuDir%\..\init.bat" -new_console:t:"Cmder"'
        );

        // Task 2: PowerShell
        if (Util::getPowerShellPath() !== false) {
            $tasks[] = array(
                'name' => '{PowerShell::Cmder}',
                'cmd' => 'PowerShell.exe -ExecutionPolicy Bypass -NoLogo -NoProfile -NoExit -Command "Invoke-Expression \'. \'\'%ConEmuDir%\..\init.ps1\'\'\'"'
            );
        }

        // Task 3: Git Bash
        if ($bearsamppTools->getGit() && file_exists($bearsamppTools->getGit()->getExe())) {
            $gitBash = str_replace('cmd/git.exe', 'bin/bash.exe', $bearsamppTools->getGit()->getExe());
            if (!file_exists($gitBash)) {
                $gitBash = '%ProgramFiles%\Git\bin\bash.exe';
            }
            $tasks[] = array(
                'name' => '{Bash::Git}',
                'cmd' => '"' . $gitBash . '" --login -i'
            );
        }

        // Task 4: Ruby
        if ($bearsamppTools->getRuby() && file_exists($bearsamppTools->getRuby()->getExe())) {
            $tasks[] = array(
                'name' => '{Ruby}',
                'guiargs' => '-new_console:t:"Ruby Console"',
                'cmd' => 'cmd.exe /k "%ConEmuDir%\..\init.bat &amp; &quot;' . $bearsamppTools->getRuby()->getExe() . '&quot; -v"'
            );
        }

        // Task 5: Python
        if ($bearsamppTools->getPython() && file_exists($bearsamppTools->getPython()->getExe())) {
            $tasks[] = array(
                'name' => '{Python}',
                'cmd' => 'cmd.exe /k "%ConEmuDir%\..\init.bat &amp; &quot;' . $bearsamppTools->getPython()->getExe() . '&quot; --version"'
            );
        }

        // Task 6: Perl
        if ($bearsamppTools->getPerl() && file_exists($bearsamppTools->getPerl()->getExe())) {
            $tasks[] = array(
                'name' => '{Perl}',
                'cmd' => 'cmd.exe /k "%ConEmuDir%\..\init.bat &amp; &quot;' . $bearsamppTools->getPerl()->getExe() . '&quot; -v"'
            );
        }

        // Task 7: Node.js
        if ($bearsamppBins->getNodejs() && file_exists($bearsamppBins->getNodejs()->getLaunch())) {
            $tasks[] = array(
                'name' => '{Node}',
                'cmd' => 'cmd.exe /k "%ConEmuDir%\..\init.bat &amp; &quot;' . $bearsamppBins->getNodejs()->getLaunch() . '&quot;"'
            );
        }

        // Task 8: Composer
        if ($bearsamppTools->getComposer() && file_exists($bearsamppTools->getComposer()->getExe())) {
            $tasks[] = array(
                'name' => '{Composer}',
                'cmd' => 'cmd.exe /k "%ConEmuDir%\..\init.bat &amp; &quot;' . $bearsamppTools->getComposer()->getExe() . '&quot; -V"'
            );
        }

        // Task 9: PEAR
        if ($bearsamppBins->getPhp() && file_exists($bearsamppBins->getPhp()->getPearExe())) {
            $tasks[] = array(
                'name' => '{PEAR}',
                'cmd' => 'cmd.exe /k "%ConEmuDir%\..\init.bat &amp; &quot;' . $bearsamppBins->getPhp()->getPearExe() . '&quot; -V"'
            );
        }

        // Task 10: MySQL
        if ($bearsamppBins->getMysql() && file_exists($bearsamppBins->getMysql()->getCliExe())) {
            $tasks[] = array(
                'name' => '{MySQL}',
                'cmd' => 'cmd.exe /k "%ConEmuDir%\..\init.bat &amp; &quot;' . $bearsamppBins->getMysql()->getCliExe() . '&quot; -u' .
                    $bearsamppBins->getMysql()->getRootUser() .
                    ($bearsamppBins->getMysql()->getRootPwd() ? ' -p' : '') . '"'
            );
        }

        // Task 11: MariaDB
        if ($bearsamppBins->getMariadb() && file_exists($bearsamppBins->getMariadb()->getCliExe())) {
            $tasks[] = array(
                'name' => '{MariaDB}',
                'cmd' => 'cmd.exe /k "%ConEmuDir%\..\init.bat &amp; &quot;' . $bearsamppBins->getMariadb()->getCliExe() . '&quot; -u' .
                    $bearsamppBins->getMariadb()->getRootUser() .
                    ($bearsamppBins->getMariadb()->getRootPwd() ? ' -p' : '') . '"'
            );
        }

        // Task 12: PostgreSQL
        if ($bearsamppBins->getPostgresql() && file_exists($bearsamppBins->getPostgresql()->getCliExe())) {
            $tasks[] = array(
                'name' => '{PostgreSQL}',
                'cmd' => 'cmd.exe /k "%ConEmuDir%\..\init.bat &amp; &quot;' . $bearsamppBins->getPostgresql()->getCliExe() . '&quot;' .
                    ' -h 127.0.0.1' .
                    ' -p ' . $bearsamppBins->getPostgresql()->getPort() .
                    ' -U ' . $bearsamppBins->getPostgresql()->getRootUser() .
                    ' -d postgres"'
            );
        }

        // Task 13: Ghostscript
        if ($bearsamppTools->getGhostscript() && file_exists($bearsamppTools->getGhostscript()->getExeConsole())) {
            $tasks[] = array(
                'name' => '{Ghostscript}',
                'cmd' => 'cmd.exe /k "%ConEmuDir%\..\init.bat &amp; &quot;' . $bearsamppTools->getGhostscript()->getExeConsole() . '&quot; -v"'
            );
        }

        // Task 14: Ngrok
        if ($bearsamppTools->getNgrok() && file_exists($bearsamppTools->getNgrok()->getExe())) {
            $tasks[] = array(
                'name' => '{Ngrok}',
                'cmd' => 'cmd.exe /k "%ConEmuDir%\..\init.bat &amp; &quot;' . $bearsamppTools->getNgrok()->getExe() . '&quot; version"'
            );
        }

        return $tasks;
    }
}
