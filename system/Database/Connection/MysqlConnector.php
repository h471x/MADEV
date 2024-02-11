<?php

namespace MADEV\Database\Connection;

use Exception;

/**
 * Une classe qui permet de se connecter à la base de données MySQL.
 *
 * @package MADEV\Database\Connection
 * @author  Kevin Ramarozatovo <kevinramarozatovo@gmail.com>
 */
class MysqlConnector extends DbConnector
{
    private static $instance;

    private function __construct()
    {
        try {
            parent::loadConfig();
            $this->setHost(parent::$config['MySQL']['host']);
            $this->setPort(parent::$config['MySQL']['port']);
            $this->setDbName(parent::$config['MySQL']['dbname']);
            $this->setUser(parent::$config['MySQL']['user']);
            $this->setPassword(parent::$config['MySQL']['password']);
        } catch (Exception $e) {
            echo 'Exception lors de l\'instanciation de MysqlConnector : ' . $e->getMessage();
            die();
        }
    }

    /**
     * Va permettre à la classe MysqlConnector de ne partager qu'une même instance unique
     * durant tout le programme.
     *
     * @return MysqlConnector
     */
    public static function getInstance()
    {
        if (self::$instance === null) self::$instance = new MysqlConnector();
        return self::$instance;
    }

    /**
     * @inheritDoc
     */
    protected function getDSN()
    {
        return "mysql:host=$this->host;port=$this->port;dbname=$this->dbName";
    }
}
