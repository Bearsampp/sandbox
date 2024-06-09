<?php
/*
 * Copyright (c) 2021-2024 Bearsampp
 * License:  GNU General Public License version 3 or later; see LICENSE.txt
 * Author: Bear
 * Website: https://bearsampp.com
 * Github: https://github.com/Bearsampp
 */

class Bins
{
    const TYPE = 'bins';

    private $mailhog;
    private $memcached;
    private $apache;
    private $php;
    private $mysql;
    private $mariadb;
    private $postgresql;
    private $nodejs;
    private $filezilla;

    /**
     * Constructor for the Bins class.
     * Initializes the class and logs the initialization.
     */
    public function __construct()
    {
        Util::logInitClass($this);
    }

    /**
     * Reloads all bin modules.
     * Logs the reload action and calls the reload method on each bin module.
     */
    public function reload()
    {
        Util::logInfo('Reload bins');
        foreach ($this->getAll() as $bin) {
            $bin->reload();
        }
    }

    /**
     * Updates the configuration for all bin modules.
     * Logs the update action and calls the update method on each bin module.
     */
    public function update()
    {
        Util::logInfo('Update bins config');
        foreach ($this->getAll() as $bin) {
            $bin->update();
        }
    }

    /**
     * Retrieves all bin modules.
     *
     * @return array An array of all bin modules.
     */
    public function getAll()
    {
        return array(
            $this->getMailhog(),
            $this->getMemcached(),
            $this->getApache(),
            $this->getFilezilla(),
            $this->getMariadb(),
            $this->getPostgresql(),
            $this->getMysql(),
            $this->getPhp(),
            $this->getNodejs(),
        );
    }

    /**
     * Retrieves the Mailhog bin module.
     *
     * @return BinMailhog The Mailhog bin module.
     */
    public function getMailhog()
    {
        if ($this->mailhog == null) {
            $this->mailhog = new BinMailhog('mailhog', self::TYPE);
        }
        return $this->mailhog;
    }

    /**
     * Retrieves the Memcached bin module.
     *
     * @return BinMemcached The Memcached bin module.
     */
    public function getMemcached()
    {
        if ($this->memcached == null) {
            $this->memcached = new BinMemcached('memcached', self::TYPE);
        }
        return $this->memcached;
    }

    /**
     * Retrieves the Apache bin module.
     *
     * @return BinApache The Apache bin module.
     */
    public function getApache()
    {
        if ($this->apache == null) {
            $this->apache = new BinApache('apache', self::TYPE);
        }
        return $this->apache;
    }

    /**
     * Retrieves the PHP bin module.
     *
     * @return BinPhp The PHP bin module.
     */
    public function getPhp()
    {
        if ($this->php == null) {
            $this->php = new BinPhp('php', self::TYPE);
        }
        return $this->php;
    }

    /**
     * Retrieves the MySQL bin module.
     *
     * @return BinMysql The MySQL bin module.
     */
    public function getMysql()
    {
        if ($this->mysql == null) {
            $this->mysql = new BinMysql('mysql', self::TYPE);
        }
        return $this->mysql;
    }

    /**
     * Retrieves the MariaDB bin module.
     *
     * @return BinMariadb The MariaDB bin module.
     */
    public function getMariadb()
    {
        if ($this->mariadb == null) {
            $this->mariadb = new BinMariadb('mariadb', self::TYPE);
        }
        return $this->mariadb;
    }

    /**
     * Retrieves the PostgreSQL bin module.
     *
     * @return BinPostgresql The PostgreSQL bin module.
     */
    public function getPostgresql()
    {
        if ($this->postgresql == null) {
            $this->postgresql = new BinPostgresql('postgresql', self::TYPE);
        }
        return $this->postgresql;
    }

    /**
     * Retrieves the Node.js bin module.
     *
     * @return BinNodejs The Node.js bin module.
     */
    public function getNodejs()
    {
        if ($this->nodejs == null) {
            $this->nodejs = new BinNodejs('nodejs', self::TYPE);
        }
        return $this->nodejs;
    }

    /**
     * Retrieves the FileZilla bin module.
     *
     * @return BinFilezilla The FileZilla bin module.
     */
    public function getFilezilla()
    {
        if ($this->filezilla == null) {
            $this->filezilla = new BinFilezilla('filezilla', self::TYPE);
        }
        return $this->filezilla;
    }

    /**
     * Retrieves the logs path for the FileZilla bin module.
     *
     * @return array An array containing the logs path for the FileZilla bin module.
     */
    public function getLogsPath()
    {
        return array(
            $this->getFilezilla()->getLogsPath(),
        );
    }

    /**
     * Retrieves the services for all enabled bin modules.
     *
     * @return array An associative array where the keys are service names and the values are the corresponding services.
     */
    public function getServices()
    {
        $result = array();

        if ($this->getMailhog()->isEnable()) {
            $result[BinMailhog::SERVICE_NAME] = $this->getMailhog()->getService();
        }
        if ($this->getMemcached()->isEnable()) {
            $result[BinMemcached::SERVICE_NAME] = $this->getMemcached()->getService();
        }
        if ($this->getApache()->isEnable()) {
            $result[BinApache::SERVICE_NAME] = $this->getApache()->getService();
        }
        if ($this->getMysql()->isEnable()) {
            $result[BinMysql::SERVICE_NAME] = $this->getMysql()->getService();
        }
        if ($this->getMariadb()->isEnable()) {
            $result[BinMariadb::SERVICE_NAME] = $this->getMariadb()->getService();
        }
        if ($this->getPostgresql()->isEnable()) {
            $result[BinPostgresql::SERVICE_NAME] = $this->getPostgresql()->getService();
        }
        if ($this->getFilezilla()->isEnable()) {
            $result[BinFilezilla::SERVICE_NAME] = $this->getFilezilla()->getService();
        }

        return $result;
    }
}
