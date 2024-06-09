<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class ActionSwitchPhpParam
 *
 * This class is responsible for switching PHP configuration parameters on or off.
 * It modifies the PHP configuration file (`php.ini`) based on the provided arguments.
 *
 * Constants:
 * - SWITCH_ON: Represents the 'on' state for a PHP setting.
 * - SWITCH_OFF: Represents the 'off' state for a PHP setting.
 *
 * Constructor:
 * - __construct($args): Initializes the class with the provided arguments and performs the switch operation.
 *
 * @param array $args An array of arguments where:
 *                    - $args[0]: The PHP setting to be switched.
 *                    - $args[1]: The desired state ('on' or 'off') for the PHP setting.
 *
 * Global Variables:
 * - $bearsamppLang: Used for retrieving language-specific messages.
 * - $bearsamppBins: Used for accessing PHP binary and configuration details.
 * - $bearsamppWinbinder: Used for displaying error messages.
 *
 * The constructor performs the following steps:
 * 1. Checks if the required arguments are provided and not empty.
 * 2. Verifies if the specified PHP setting exists.
 * 3. Retrieves the current values for the PHP setting.
 * 4. Reads the current content of the PHP configuration file.
 * 5. Modifies the configuration file content based on the desired state ('on' or 'off').
 * 6. Writes the modified content back to the PHP configuration file.
 *
 * If the specified PHP setting does not exist, an error message is displayed.
 *
 * Example usage:
 * $action = new ActionSwitchPhpParam(['display_errors', 'on']);
 *
 * @package Bearsampp
 * @license GNU General Public License version 3 or later; see LICENSE.txt
 * @author Bear
 * @link https://bearsampp.com
 */
class ActionSwitchPhpParam
{
    const SWITCH_ON = 'on';
    const SWITCH_OFF = 'off';

    /**
     * Constructor to initialize the class with arguments and perform the switch operation.
     *
     * @param array $args An array of arguments where:
     *                    - $args[0]: The PHP setting to be switched.
     *                    - $args[1]: The desired state ('on' or 'off') for the PHP setting.
     */
    public function __construct($args)
    {
        global $bearsamppLang, $bearsamppBins, $bearsamppWinbinder;

        if (isset($args[0]) && !empty($args[0]) && isset($args[1]) && !empty($args[1])) {
            if (!$bearsamppBins->getPhp()->isSettingExists($args[0])) {
                $bearsamppWinbinder->messageBoxError(
                    sprintf($bearsamppLang->getValue(Lang::SWITCH_PHP_SETTING_NOT_FOUND), $args[0], $bearsamppBins->getPhp()->getVersion()),
                    $bearsamppLang->getValue(Lang::SWITCH_PHP_SETTING_TITLE)
                );
                return;
            }

            $settingsValues = $bearsamppBins->getPhp()->getSettingsValues();
            if (isset($settingsValues[$args[0]])) {
                $onContent = $args[0] . ' = ' . $settingsValues[$args[0]][0];
                $offContent = $args[0] . ' = ' . $settingsValues[$args[0]][1];

                $phpiniContent = file_get_contents($bearsamppBins->getPhp()->getConf());
                if ($args[1] == self::SWITCH_ON) {
                    $phpiniContent = str_replace($offContent, $onContent, $phpiniContent);
                } elseif ($args[1] == self::SWITCH_OFF) {
                    $phpiniContent = str_replace($onContent, $offContent, $phpiniContent);
                }

                file_put_contents($bearsamppBins->getPhp()->getConf(), $phpiniContent);
            }
        }
    }
}
