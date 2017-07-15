<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 1:14 PM
 */

use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase
{
    /** @var  array */
    private $config;

    /** @var  \JustFuse\ClickhouseClient\Connector\Config */
    private $connectorConfig;

    /** @var  \JustFuse\ClickhouseClient\Connector\Connector */
    private $connector;

    protected function setUp()
    {
        $this->config = [];
        $this->config['host'] = getenv('JF_CLICKHOUSE_HOST') ?: '127.0.0.1';
        $this->config['port'] = getenv('JF_CLICKHOUSE_PORT') ?: '8123';
        $this->config['protocol'] = getenv('JF_CLICKHOUSE_PROTOCOL') ?: 'http';
        $this->config['database'] = getenv('JF_CLICKHOUSE_DATABASE') ?: 'default';
        $this->config['user'] = getenv('JF_CLICKHOUSE_USER') ?: 'default';
        $this->config['password'] = getenv('JF_CLICKHOUSE_PASSWORD') ?: '';

        $this->connectorConfig = new \JustFuse\ClickhouseClient\Connector\Config();
        $this->connectorConfig->setHost($this->config['host']);
        $this->connectorConfig->setPort($this->config['port']);
        $this->connectorConfig->setProtocol($this->config['protocol']);
        $this->connectorConfig->setUser($this->config['user']);
        $this->connectorConfig->setPassword($this->config['password']);

        $this->connector = new \JustFuse\ClickhouseClient\Connector\Connector();
        $this->connector->setConfig($this->connectorConfig);

    }

    public function testUserMisconfig()
    {
        $this->connectorConfig->setUser('test');
        $request = new \JustFuse\ClickhouseClient\Connector\Request();
        $response = $this->connector->perform($request);
//        dump($response);
    }

    public function testGetQuery()
    {
        $request = new \JustFuse\ClickhouseClient\Connector\Request();
        $request->setGet(['query' => 'SELECT 23']);
        $response = $this->connector->perform($request);
//        dump($response);
    }

    public function testPostRawQuery()
    {
        $request = new \JustFuse\ClickhouseClient\Connector\Request();
        $request->setGet(['database' => 'system']);
        $request->setPostRaw('SELECT number FROM numbers LIMIT 10');
        $response = $this->connector->perform($request);
//        dump($response);
    }

    public function testPostStreamQuery()
    {
        $stream = fopen('php://memory','r+');
        fwrite($stream, 'SELECT number FROM numbers LIMIT 10');
        rewind($stream);

        $request = new \JustFuse\ClickhouseClient\Connector\Request();
        $request->setGet(['database' => 'system']);
        $request->setPostStream($stream);
        $response = $this->connector->perform($request);
//        dump($response);
    }

    public function testSomething()
    {

    }
}
