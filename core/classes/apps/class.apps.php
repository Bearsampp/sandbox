<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

/**
 * Class Apps
 *
 * This class manages various application modules within the Bearsampp application.
 * It provides methods to retrieve and update configurations for different tools such as PhpMyAdmin and PhpPgAdmin. * @since 2022.2.16
     
 */
class Apps
{
    /**
     * The type of the module. * @since 2022.2.16
     
     */
    const TYPE = 'apps';

    /**
     * @var AppPhpmyadmin|null Instance of the PhpMyAdmin module. * @since 2022.2.16
     
     */
    private $phpmyadmin;

    /**
     * @var AppPhppgadmin|null Instance of the PhpPgAdmin module. * @since 2022.2.16
     
     */
    private $phppgadmin;

    /**
     * Constructor for the Apps class. * @since 2022.2.16
     
     */
    public function __construct()
    {
    }

    /**
     * Reloads the configuration for all application modules.
     *
     * This method logs the reload process and iterates through all the apps to reload their configurations. * @since 2022.2.16
     
     */
    public function reload()
    {
        Util::logInfo('Reload apps');
        foreach ($this->getAll() as $app) {
            $app->reload();
        }
    }

    /**
     * Updates the configuration for all application modules.
     *
     * This method logs the update process and iterates through all the tools to update their configurations. * @since 2022.2.16
     
     */
    public function update()
    {
        Util::logInfo('Update apps config');
        foreach ($this->getAll() as $tool) {
            $tool->update();
        }
    }

    /**
     * Retrieves all application modules.
     *
     * @return array An array containing instances of all application modules. * @since 2022.2.16
     
     */
    public function getAll()
    {
        return array(
            $this->getPhpmyadmin(),
            $this->getPhppgadmin()
        );
    }

    /**
     * Retrieves the PhpMyAdmin module instance.
     *
     * If the instance is not already created, it initializes a new AppPhpmyadmin object.
     *
     * @return AppPhpmyadmin The instance of the PhpMyAdmin module. * @since 2022.2.16
     
     */
    public function getPhpmyadmin()
    {
        if ($this->phpmyadmin == null) {
            $this->phpmyadmin = new AppPhpmyadmin('phpmyadmin', self::TYPE);
        }
        return $this->phpmyadmin;
    }

    /**
     * Retrieves the PhpPgAdmin module instance.
     *
     * If the instance is not already created, it initializes a new AppPhppgadmin object.
     *
     * @return AppPhppgadmin The instance of the PhpPgAdmin module. * @since 2022.2.16
     
     */
    public function getPhppgadmin()
    {
        if ($this->phppgadmin == null) {
            $this->phppgadmin = new AppPhppgadmin('phppgadmin', self::TYPE);
        }
        return $this->phppgadmin;
    }
}
