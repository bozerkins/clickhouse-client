<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 5:52 PM
 */

namespace ClickhouseClient\Tests;

use ClickhouseClient\Client\Client;
use ClickhouseClient\Client\Config;
use ClickhouseClient\Client\Format;
use PHPUnit\Framework\TestCase;

class ClientTest extends DefaultTest
{
    /** @var  Client */
    protected $client;

    protected function setUp()
    {
        parent::setUp();

        $config = new Config(
            ['host' => $this->config['host'], 'port' => $this->config['port'], 'protocol' => $this->config['protocol']],
            ['database' => $this->config['database']],
            ['user' => $this->config['user'], 'password' => $this->config['password']]
        );

        $this->client = new Client(
            $config,
            Format\JsonFormat::class
        );
    }

    public function testPing()
    {
        $response = $this->client->ping();
        $this->assertEquals(
            "Ok.\n",
            $response->getContent()
        );
    }

    public function testSystemQuery()
    {
        $dbs = $this->client->query('SHOW DATABASES')->getContent()['data'];
        $this->assertTrue(is_array($dbs));

        $found = false;
        foreach($dbs as $db) {
            if ($db['name'] === 'default') {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testWorkflowDefaultFormat()
    {
        $this->client->system('DROP TABLE IF EXISTS t');

        $this->client->system('CREATE TABLE IF NOT EXISTS t  (a UInt8) ENGINE = Memory');

        $this->client->writePlain('INSERT INTO t VALUES (1), (2), (3)');

        $this->client->writeRows('INSERT INTO t',
            [
                ['a' => 5],
                ['a' => 6],
                ['a' => 7]
            ]
        );

        $stream = fopen('php://memory','r+');
        fwrite($stream, '{"a":8}'.PHP_EOL.'{"a":9}'.PHP_EOL );
        rewind($stream);

        $this->client->writeStream('INSERT INTO t', $stream);

        $list = $this->client->query('SELECT * FROM t ORDER BY a ASC')->getContent();

        $this->assertEquals(
            "[{\"a\":1},{\"a\":2},{\"a\":3},{\"a\":5},{\"a\":6},{\"a\":7},{\"a\":8},{\"a\":9}]",
            json_encode($list['data'])
        );

        $this->client->system('DROP TABLE t');
    }

    public function testWorkflowJsonEachRow()
    {
        $this->client->system('DROP TABLE IF EXISTS t');

        $this->client->system('CREATE TABLE IF NOT EXISTS t  (a UInt8) ENGINE = Memory');

        $this->client->writePlain('INSERT INTO t VALUES (1), (2), (3)');

        $this->client->writeRows('INSERT INTO t',
            [
                ['a' => 5],
                ['a' => 6],
                ['a' => 7]
            ],
            Format\JsonEachRowFormat::class
        );

        $stream = fopen('php://memory','r+');
        fwrite($stream, '{"a":8}'.PHP_EOL.'{"a":9}'.PHP_EOL );
        rewind($stream);

        $this->client->writeStream(
            'INSERT INTO t',
            $stream,
            Format\JsonEachRowFormat::class
        );

        $list = $this->client->query(
            'SELECT * FROM t ORDER BY a ASC',
            Format\JsonEachRowFormat::class
        )->getContent();

        $this->assertEquals(
            "[{\"a\":1},{\"a\":2},{\"a\":3},{\"a\":5},{\"a\":6},{\"a\":7},{\"a\":8},{\"a\":9}]",
            json_encode($list)
        );

        $this->client->system('DROP TABLE t');
    }
}
