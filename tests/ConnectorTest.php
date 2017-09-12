<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 5:51 PM
 */

namespace ClickhouseClient\Tests;

use ClickhouseClient\Client\Format\JsonFormat;
use ClickhouseClient\Connector\Config;
use ClickhouseClient\Connector\Connector;
use ClickhouseClient\Connector\Request;
use PHPUnit\Framework\TestCase;

class ConnectorTest extends DefaultTest
{
    /** @var  Connector */
    private $connector;

    protected function setUp()
    {
        parent::setUp();

        $config = new Config();
        $config->setHost($this->config['host']);
        $config->setPort($this->config['port']);
        $config->setProtocol($this->config['protocol']);
        $config->setUser($this->config['user']);
        $config->setPassword($this->config['password']);

        $this->connector = new Connector($config);
    }

    public function testPing()
    {
        $response = $this->connector->perform(new Request());
        $this->assertEquals("Ok.\n", $response->getContent());
    }

    public function testSelect()
    {
        $request = new Request();
        $request->setGet(['query' => 'SELECT 1']);
        $response = $this->connector->perform($request);
        $this->assertEquals("1\n", $response->getContent());

        $request = new Request();
        $request->setGet(['query' => 'SELECT 1 FORMAT JSONEachRow']);
        $response = $this->connector->perform($request);
        $this->assertEquals("{\"1\":1}\n", $response->getContent());
    }

    public function testWorkflow()
    {
        $request = new Request();
        $request->setPostRaw('DROP TABLE IF EXISTS t');
        $this->connector->perform($request);

        $request = new Request();
        $request->setPostRaw('CREATE TABLE IF NOT EXISTS t  (a UInt8) ENGINE = Memory');
        $this->connector->perform($request);

        $request = new Request();
        $request->setPostRaw('INSERT INTO t VALUES (1), (2), (3)');
        $this->connector->perform($request);

        $request = new Request();
        $request->setGet(['query' => 'INSERT INTO t FORMAT JSONEachRow']);
        $request->setPostRaw('{"a":5}'.PHP_EOL.'{"a":6}'.PHP_EOL.'{"a":7}'.PHP_EOL);
        $this->connector->perform($request);


        $stream = fopen('php://memory','r+');
        fwrite($stream, '{"a":8}'.PHP_EOL.'{"a":9}'.PHP_EOL );
        rewind($stream);

        $request = new Request();
        $request->setGet(['query' => 'INSERT INTO t FORMAT JSONEachRow']);
        $request->setPostStream($stream);
        $this->connector->perform($request);

        $request = new Request();
        $request->setGet(['query' => 'SELECT * FROM t ORDER BY a ASC']);
        $response = $this->connector->perform($request);

        $this->assertEquals(
            "1\n2\n3\n5\n6\n7\n8\n9\n",
            $response->getContent()
        );
        $request = new Request();
        $request->setPostRaw('DROP TABLE t');
        $this->connector->perform($request);

    }
}
