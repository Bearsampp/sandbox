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
 * Class TplAppTools
 *
 * This class provides methods to generate menu items and actions for managing various tools
 * within the Bearsampp application. It includes functionalities for accessing tools like Git, Python,
 * Composer, Ghostscript, Ngrok, Pear, Perl, Ruby and more.
 */
class TplAppTools
{
    // Constants for menu and action identifiers
    const MENU = 'tools';
    const ACTION_GEN_SSL_CERTIFICATE = 'genSslCertificate';

    /**
     * Generates the main Tools menu with options to access various tools.
     *
     * @global object $bearsamppLang Provides language support for retrieving language-specific values.
     *
     * @return array The generated menu items and actions for Tools.
     */
    public static function process()
    {
        global $bearsamppLang;

        return TplApp::getMenu($bearsamppLang->getValue(Lang::TOOLS), self::MENU, get_called_class());
    }

    /**
     * Generates the Tools menu with options for accessing various tools like Git, Python, Composer, etc.
     *
     * @global object $bearsamppLang Provides language support for retrieving language-specific values.
     * @global object $bearsamppCore Provides access to core functionalities and configurations.
     * @global object $bearsamppTools Provides access to various tools and their configurations.
     *
     * @return string The generated menu items and actions for Tools.
     */
    public static function getMenuTools()
    {
        global $bearsamppLang, $bearsamppCore, $bearsamppTools;
        $resultItems = $resultActions = '';

        // Git
        $tplGit = TplAppGit::process();
        $resultItems .= $tplGit[TplApp::SECTION_CALL] . PHP_EOL;
        $resultActions .= $tplGit[TplApp::SECTION_CONTENT] . PHP_EOL;

        // Python
        $tplPython = TplAppPython::process();
        $resultItems .= $tplPython[TplApp::SECTION_CALL] . PHP_EOL;
        $resultActions .= $tplPython[TplApp::SECTION_CONTENT] . PHP_EOL;

        // Bruno postman IDE
        $resultItems .= TplAestan::getItemExe(
                $bearsamppLang->getValue(Lang::BRUNO),
                $bearsamppTools->getBruno()->getExe(),
                TplAestan::GLYPH_BRUNO
            ) . PHP_EOL;

        // Composer - use wrapper batch file
        $resultItems .= TplAestan::getItemExe(
            $bearsamppLang->getValue(Lang::COMPOSER),
            $bearsamppTools->getCmder()->getCurrentPath() . '/launch-composer.bat',
            TplAestan::GLYPH_COMPOSER,
            ''
        ) . PHP_EOL;

        // Ghostscript - use wrapper batch file
        $resultItems .= TplAestan::getItemExe(
            $bearsamppLang->getValue(Lang::GHOSTSCRIPT),
            $bearsamppTools->getCmder()->getCurrentPath() . '/launch-ghostscript.bat',
            TplAestan::GLYPH_GHOSTSCRIPT,
            ''
        ) . PHP_EOL;

        // Ngrok - use wrapper batch file
        $resultItems .= TplAestan::getItemExe(
            $bearsamppLang->getValue(Lang::NGROK),
            $bearsamppTools->getCmder()->getCurrentPath() . '/launch-ngrok.bat',
            TplAestan::GLYPH_NGROK,
            ''
        ) . PHP_EOL;

        // Pear - use wrapper batch file
        $resultItems .= TplAestan::getItemExe(
            $bearsamppLang->getValue(Lang::PEAR),
            $bearsamppTools->getCmder()->getCurrentPath() . '/launch-pear.bat',
            TplAestan::GLYPH_PEAR,
            ''
        ) . PHP_EOL;

        // Perl - use wrapper batch file
        $resultItems .= TplAestan::getItemExe(
            $bearsamppLang->getValue(Lang::PERL),
            $bearsamppTools->getCmder()->getCurrentPath() . '/launch-perl.bat',
            TplAestan::GLYPH_PERL,
            ''
        ) . PHP_EOL;

        // Ruby - use wrapper batch file to avoid AeTrayMenu quoting issues
        $resultItems .= TplAestan::getItemExe(
            $bearsamppLang->getValue(Lang::RUBY),
            $bearsamppTools->getCmder()->getCurrentPath() . '/launch-ruby.bat',
            TplAestan::GLYPH_RUBY,
            ''
        ) . PHP_EOL;

        // Line Separator
        $resultItems .= TplAestan::getItemSeparator() . PHP_EOL;

        // Console
        $resultItems .= TplAestan::getItemCmder(
            $bearsamppLang->getValue(Lang::CONSOLE),
            TplAestan::GLYPH_CMDER
        ) . PHP_EOL;

        // HostsEditor
        $resultItems .= TplAestan::getItemExe(
            $bearsamppLang->getValue(Lang::HOSTSEDITOR),
            $bearsamppCore->getHostsEditorExe(),
            TplAestan::GLYPH_HOSTSEDITOR
        ) . PHP_EOL;

        // Pwgen password manager
        $resultItems .= TplAestan::getItemExe(
                $bearsamppLang->getValue(Lang::PWGEN),
                $bearsamppCore->getPwgenExe(),
                TplAestan::GLYPH_PWGEN
            ) . PHP_EOL;

        // Generate SSL Certificate
        $tplGenSslCertificate = TplApp::getActionMulti(
            self::ACTION_GEN_SSL_CERTIFICATE, null,
            array($bearsamppLang->getValue(Lang::MENU_GEN_SSL_CERTIFICATE), TplAestan::GLYPH_SSL_CERTIFICATE),
            false, get_called_class()
        );
        $resultItems .= $tplGenSslCertificate[TplApp::SECTION_CALL] . PHP_EOL;
        $resultActions .= $tplGenSslCertificate[TplApp::SECTION_CONTENT];

        return $resultItems . PHP_EOL . $resultActions;
    }

    /**
     * Generates the action to generate an SSL certificate.
     *
     * @return string The generated action to generate an SSL certificate.
     */
    public static function getActionGenSslCertificate()
    {
        return TplApp::getActionRun(Action::GEN_SSL_CERTIFICATE);
    }
}
