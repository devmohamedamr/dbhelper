<?php
namespace Tests;

use vielhuber\dbhelper\dbhelper;

trait BasicSetup
{
    public static $db;
    public static $credentials;

    public static function setUpBeforeClass()
    {
        self::$db = new dbhelper();
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
        self::$db->create_table('test', [
            'id' =>
                (self::$credentials->engine === 'sqlite' ? 'INTEGER' : 'SERIAL') . ' PRIMARY KEY',
            'col1' => 'varchar(255)',
            'col2' => 'varchar(255)',
            'col3' => 'varchar(255)'
        ]);
    }

    function tearDown()
    {
        self::$db->clear();
    }
}
