<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 5:57 PM
 */

namespace ClickhouseClient;

use ClickhouseClient\Client\Client;
use ClickhouseClient\Client\Config;
use ClickhouseClient\Client\Format\JsonFormat;
use ClickhouseClient\Exception\Exception;
use PHPUnit\Framework\TestCase;

class ConfigTest extends DefaultTest
{
    /**
     * @throws Exception
     */
    public function testPingConfig()
    {
        $config = new Config(
            ['host' => $this->config['host'], 'port' => $this->config['port'], 'protocol' => $this->config['protocol']],
            ['database' => $this->config['database']],
            ['user' => $this->config['user'], 'password' => $this->config['password']]
        );

        $client = new Client($config, JsonFormat::class);
        $this->assertEquals(
            1,
            $client->query('SELECT 1')->getContent()['data'][0][1]
        );
    }

    /**
     * @throws Exception
     */
    public function testWrongPasswordConfig()
    {
        $config = new Config(
            ['host' => $this->config['host'], 'port' => $this->config['port'], 'protocol' => $this->config['protocol']],
            ['database' => $this->config['database']],
            ['user' => $this->config['user'], 'password' => $this->config['password'] . md5(rand(1000, 2000))]
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Code: 193, e.displayText() = DB::Exception: Wrong password for user default, e.what() = DB::Exception');
        $client = new Client($config, JsonFormat::class);
        $client->query('SELECT 1');
    }

    /**
     * @throws Exception
     */
    public function testPostConfigCreationChanges()
    {
        $config = new Config(['host' => $this->config['host'], 'port' => $this->config['port'], 'protocol' => $this->config['protocol']]);
        $config->setUser($this->config['user']);
        $config->setPassword($this->config['password']);
        $config->change('database', $this->config['database']);


        $client = new Client($config, JsonFormat::class);
        $this->assertEquals(
            1,
            $client->query('SELECT 1')->getContent()['data'][0][1]
        );
    }

    /**
     * @throws Exception
     */
    public function testDatabaseChange()
    {
        $config = new Config(
            ['host' => $this->config['host'], 'port' => $this->config['port'], 'protocol' => $this->config['protocol']],
            ['database' => $this->config['database']],
            ['user' => $this->config['user'], 'password' => $this->config['password']]
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Code: 81, e.displayText() = DB::Exception: Database `new-database` doesn\'t exist, e.what() = DB::Exception');
        $client = new Client($config, JsonFormat::class);
        $client->config()->change('database', 'new-database');
        $client->query('SELECT 1');
    }

    /**
     * @throws Exception
     */
    public function testHandlingTimeouts()
    {
        $config = new Config(
            ['host' => $this->config['host'], 'port' => $this->config['port'], 'protocol' => $this->config['protocol']],
            ['database' => $this->config['database']],
            ['user' => $this->config['user'], 'password' => $this->config['password']],
            [CURLOPT_TIMEOUT_MS => 30]
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/^Operation timed out after \d+ milliseconds with/');

        $client = new Client($config, JsonFormat::class);
        $client->query('SELECT * FROM system.numbers LIMIT 100000000');
    }
}
