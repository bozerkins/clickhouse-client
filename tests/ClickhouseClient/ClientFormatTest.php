<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 5:52 PM
 */

namespace ClickhouseClient;

use ClickhouseClient\Client\Client;
use ClickhouseClient\Client\Config;
use ClickhouseClient\Client\Format;

class ClientFormatTest extends DefaultTest
{
    /** @var  Config */
    protected $clientConfig;

    /**
     * @throws \Exception
     */
    protected function setUp()
    {
        parent::setUp();

        $this->clientConfig = new Config(
            ['host' => $this->config['host'], 'port' => $this->config['port'], 'protocol' => $this->config['protocol']],
            ['database' => $this->config['database']],
            ['user' => $this->config['user'], 'password' => $this->config['password']]
        );
    }

    /**
     * @throws Exception\Exception
     * @throws \Exception
     */
    public function testWorkflowDefaultFormat()
    {
        $client = new Client($this->clientConfig);

        $client->system('DROP TABLE IF EXISTS t');

        $client->system('CREATE TABLE IF NOT EXISTS t  (a UInt8) ENGINE = Memory');

        $client->write('INSERT INTO t VALUES (1), (2), (3)');

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

        $list = $client->query('SELECT * FROM t ORDER BY a ASC')->getContent();

        $this->assertEquals(
            "[{\"a\":1},{\"a\":2},{\"a\":3},{\"a\":5},{\"a\":6},{\"a\":7},{\"a\":8},{\"a\":9}]",
            json_encode($list['data'])
        );

        $client->system('DROP TABLE t');
    }

    /**
     * @throws Exception\Exception
     * @throws \Exception
     */
    public function testWorkflowJsonEachRow()
    {
        $client = new Client($this->clientConfig);

        $client->system('DROP TABLE IF EXISTS t');

        $client->system('CREATE TABLE IF NOT EXISTS t  (a UInt8) ENGINE = Memory');

        $client->write('INSERT INTO t VALUES (1), (2), (3)');

        $client->writeRows('INSERT INTO t',
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

        $client->writeStream(
            'INSERT INTO t',
            $stream,
            Format\JsonEachRowFormat::class
        );

        $list = $client->query(
            'SELECT * FROM t ORDER BY a ASC',
            Format\JsonEachRowFormat::class
        )->getContent();

        $this->assertEquals(
            "[{\"a\":1},{\"a\":2},{\"a\":3},{\"a\":5},{\"a\":6},{\"a\":7},{\"a\":8},{\"a\":9}]",
            json_encode($list)
        );

        $client->system('DROP TABLE t');
    }
}
