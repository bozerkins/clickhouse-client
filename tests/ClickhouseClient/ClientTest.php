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

class ClientTest extends DefaultTest
{
    /** @var  Client */
    protected $client;

    /**
     * @throws \Exception
     */
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

    /**
     * @throws Exception\Exception
     */
    public function testPingQuery()
    {
        $response = $this->client->ping();
        $this->assertEquals("Ok.\n", $response->getContent());
    }

    /**
     * @throws Exception\Exception
     */
    public function testSimpleQuery()
    {
        $response = $this->client->query("SELECT * FROM system.numbers LIMIT 5");
        $this->assertTrue(is_array($response->getContent()) && array_key_exists('data', $response->getContent()));
    }

    /**
     * @throws Exception\Exception
     */
    public function testStreamQuery()
    {
        $stream = fopen('php://memory', 'r+');

       $this->client->queryStream("SELECT * FROM system.numbers LIMIT 5", $stream, Format\TabSeparatedFormat::class);

        rewind($stream);

        for ($i = 0; $i < 5; $i++) {
            $iString = (string)$i;
            $this->assertEquals($iString . PHP_EOL, fgets($stream));
        }
    }

    /**
     * @throws Exception\Exception
     */
    public function testClosureQuery()
    {
        $lines = '';
        $closure = function ($line) use (&$lines) {
            $lines .= $line;
        };

        $this->client->queryClosure("SELECT * FROM system.numbers LIMIT 5", $closure, Format\JsonEachRowFormat::class);

        $this->assertEquals('{"number":"0"}{"number":"1"}{"number":"2"}{"number":"3"}{"number":"4"}', $lines);
    }

    /**
     * @throws Exception\Exception
     */
    public function testPing()
    {
        $response = $this->client->ping();
        $this->assertEquals(
            "Ok.\n",
            $response->getContent()
        );
    }

    /**
     * @throws Exception\Exception
     */
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
}
