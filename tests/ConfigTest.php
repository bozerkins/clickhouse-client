<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 5:57 PM
 */

namespace ClickhouseClient\Tests;

use ClickhouseClient\Client\Client;
use ClickhouseClient\Client\Config;
use ClickhouseClient\Client\Format\JsonFormat;
use ClickhouseClient\Exception\Exception;
use PHPUnit\Framework\TestCase;

class ConfigTest extends DefaultTest
{
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
