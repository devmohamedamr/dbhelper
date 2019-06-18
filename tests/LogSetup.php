<?php
namespace Tests;

use vielhuber\dbhelper\dbhelper;

trait LogSetup
{
    public static $db;
    public static $credentials;

    public static function setUpBeforeClass()
    {
        self::$db = new dbhelper([
            'enable_logging' => true,
            'logging_table' => 'logs',
            'exclude' => [
                'tables' => ['test2'],
                'columns' => ['test' => ['col4']]
            ],
            'delete_older' => 12,
            'updated_by' => 42
        ]);
        self::$credentials = self::getCredentials();
        self::$db->connect_with_create(
            self::$credentials->driver,
            self::$credentials->engine,
            self::$credentials->host,
            self::$credentials->username,
            self::$credentials->password,
            self::$credentials->database,
            self::$credentials->port
        );
    }

    public static function tearDownAfterClass()
    {
        self::$db->disconnect_with_delete();
    }

    function setUp()
    {
        self::$db->clear(); // if something failed
        self::$db->query('
            CREATE TABLE IF NOT EXISTS test
            (
              id SERIAL PRIMARY KEY,
              col1 varchar(255),
              col2 TEXT,
              col3 int,
              col4 varchar(255)
            )
        ');
        self::$db->query('
            CREATE TABLE IF NOT EXISTS test2
            (
              id SERIAL PRIMARY KEY,
              col1 varchar(255),
              col2 TEXT,
              col3 int,
              col4 varchar(255)
            )
        ');
        self::$db->setup_logging();
        self::$db->enable_auto_inject();
    }

    function tearDown()
    {
        self::$db->clear();
    }
}
