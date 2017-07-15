<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 5:52 PM
 */

namespace JustFuse\ClickhouseClient\Tests;

use PHPUnit\Framework\TestCase;

class ClientTest extends DefaultTest
{
    public function testSomething()
    {
        $config = new \JustFuse\ClickhouseClient\Client\Config(
            ['host' => $this->config['host'], 'port' => $this->config['port'], 'protocol' => $this->config['protocol']],
            ['database' => $this->config['database']],
            ['user' => $this->config['user'], 'password' => $this->config['password']]
        );

        $client = new \JustFuse\ClickhouseClient\Client\Client(
            $config,
//            \JustFuse\ClickhouseClient\Client\Format\JsonFormat::class
            \JustFuse\ClickhouseClient\Client\Format\JsonEachRowFormat::class
        );

        $client->system('DROP TABLE IF EXISTS t');

        $client->system('CREATE TABLE IF NOT EXISTS t  (a UInt8) ENGINE = Memory');

        $client->writePlain('INSERT INTO t VALUES (1), (2), (3)');

        $client->writeRows('INSERT INTO t',
            [
                ['a' => 5],
                ['a' => 6],
                ['a' => 7]
            ]
        );

        $stream = fopen('php://memory','r+');
        fwrite($stream, '{"a":8}'.PHP_EOL.'{"a":9}'.PHP_EOL );
        rewind($stream);

        $client->writeStream('INSERT INTO t', $stream);

        $list = $client->query('SELECT * FROM t')->getContent();

        dump($list);

//        $client->system('DROP TABLE t');


        exit;
    }
}
