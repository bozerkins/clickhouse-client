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

    /**
     * @throws Exception\Exception
     * @throws \Exception
     */
    public function testWorkflowTabSeparated()
    {
        $client = new Client($this->clientConfig);

        $client->system('DROP TABLE IF EXISTS t');
        $client->system('CREATE TABLE IF NOT EXISTS t  (a UInt8, b String) ENGINE = Memory');
        $client->write('INSERT INTO t VALUES (1, \'one\'), (2, \'two\'), (3, \'three\')');
        $client->writeRows('INSERT INTO t',
            [
                ['a' => 5, 'b' => 'five'],
                ['a' => 6, 'b' => 'six'],
                ['a' => 7, 'b' => 'seven']
            ],
            Format\TabSeparatedFormat::class
        );

        $stream = fopen('php://memory','r+');
        fwrite($stream, "8\teight".PHP_EOL."9\tnine".PHP_EOL );
        rewind($stream);

        $client->writeStream(
            'INSERT INTO t',
            $stream,
            Format\TabSeparatedFormat::class
        );

        $list = $client->query(
            'SELECT * FROM t ORDER BY a ASC',
            Format\TabSeparatedFormat::class
        )->getContent();

        $this->assertEquals(
            '[{"a":"1","b":"one"},{"a":"2","b":"two"},{"a":"3","b":"three"},'.
            '{"a":"5","b":"five"},{"a":"6","b":"six"},{"a":"7","b":"seven"},'.
            '{"a":"8","b":"eight"},{"a":"9","b":"nine"}]',
            json_encode($list)
        );

        $client->system('DROP TABLE t');
    }



    /**
     * @throws Exception\Exception
     * @throws \Exception
     */
    public function testWorkflowCSV()
    {
        $client = new Client($this->clientConfig);

        $client->system('DROP TABLE IF EXISTS t');
        $client->system('CREATE TABLE IF NOT EXISTS t  (a UInt8, b String) ENGINE = Memory');
        $client->write('INSERT INTO t VALUES (1, \'one\'), (2, \'two\'), (3, \'three\')');
        $client->writeRows('INSERT INTO t',
            [
                ['a' => 5, 'b' => 'five'],
                ['a' => 6, 'b' => 'six'],
                ['a' => 7, 'b' => 'seven']
            ],
            Format\CSVFormat::class
        );

        $stream = fopen('php://memory','r+');
        fwrite($stream, "8,eight".PHP_EOL."9,nine".PHP_EOL );
        rewind($stream);

        $client->writeStream(
            'INSERT INTO t',
            $stream,
            Format\CSVFormat::class
        );

        $list = $client->query(
            'SELECT * FROM t ORDER BY a ASC',
            Format\CSVFormat::class
        )->getContent();

        $this->assertEquals(
            '[{"a":"1","b":"one"},{"a":"2","b":"two"},{"a":"3","b":"three"},'.
            '{"a":"5","b":"five"},{"a":"6","b":"six"},{"a":"7","b":"seven"},'.
            '{"a":"8","b":"eight"},{"a":"9","b":"nine"}]',
            json_encode($list)
        );

        $client->system('DROP TABLE t');
    }



    /**
     * @throws Exception\Exception
     * @throws \Exception
     */
    public function testWorkflowPretty()
    {
        $client = new Client($this->clientConfig);

        $client->system('DROP TABLE IF EXISTS t');
        $client->system('CREATE TABLE IF NOT EXISTS t  (a UInt8, b String) ENGINE = Memory');
        $client->write('INSERT INTO t VALUES (1, \'one\'), (2, \'two\'), (3, \'three\')');

        $exception = null;
        try {
            $client->writeRows('INSERT INTO t',
                [
                    ['a' => 5, 'b' => 'five'],
                    ['a' => 6, 'b' => 'six'],
                    ['a' => 7, 'b' => 'seven']
                ],
                Format\PrettyFormat::class
            );
        } catch(\RuntimeException $exception) {
            $this->assertContains("PrettyFormat does not support data insert", $exception->getMessage());
        }
        $this->assertInstanceOf(\RuntimeException::class, $exception);

        $stream = fopen('php://memory','r+');
        fwrite($stream, "8,eight".PHP_EOL."9,nine".PHP_EOL );
        rewind($stream);

        $exception = null;
        try {
            $client->writeStream(
                'INSERT INTO t',
                $stream,
                Format\PrettyFormat::class
            );
        } catch(\RuntimeException $exception) {
            $this->assertContains("PrettyFormat does not support data insert", $exception->getMessage());
        }
        $this->assertInstanceOf(\RuntimeException::class, $exception);

        $list = $client->query(
            'SELECT * FROM t ORDER BY a ASC',
            Format\PrettyFormat::class
        )->getContent();

        $this->assertEquals(
            '["\u250f\u2501\u2501\u2501\u2533\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2513\n\u2503 \u001b[1ma\u001b[0m'.
            ' \u2503 \u001b[1mb    \u001b[0m \u2503\n\u2521\u2501\u2501\u2501\u2547\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2529'.
            '\n\u2502 1 \u2502 one   \u2502\n\u251c\u2500\u2500\u2500\u253c\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2524\n\u2502'.
            ' 2 \u2502 two   \u2502\n\u251c\u2500\u2500\u2500\u253c\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2524\n\u2502 3 \u2502'.
            ' three \u2502\n\u2514\u2500\u2500\u2500\u2534\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2518\n"]',
            json_encode($list)
        );

        $client->system('DROP TABLE t');
    }
}
