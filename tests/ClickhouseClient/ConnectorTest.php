<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 5:51 PM
 */

namespace ClickhouseClient;

use ClickhouseClient\Client\Config;
use ClickhouseClient\Connector\Connector;

class ConnectorTest extends DefaultTest
{
    /** @var  Connector */
    private $connector;

    /**
     *
     */
    protected function setUp()
    {
        parent::setUp();

        $config = new Config(
            ['host' => $this->config['host'], 'port' => $this->config['port'], 'protocol' => $this->config['protocol']],
            ['database' => $this->config['database']],
            ['user' => $this->config['user'], 'password' => $this->config['password']]
        );
        $this->connector = new Connector($config);
    }

    /**
     * @throws Exception\Exception
     */
    public function testPing()
    {
        $response = $this->connector->performRequest(
            $this->connector->createResource()
        );
        $this->assertEquals("Ok.\n", $response->getContent());
    }

    /**
     * @throws Exception\Exception
     */
    public function testSelect()
    {
        $response = $this->connector->performRequest(
            $this->connector->createResource(['query' => 'SELECT 1'])
        );
        $this->assertEquals("1\n", $response->getContent());

        $response = $this->connector->performRequest(
            $this->connector->createResource(['query' => 'SELECT 1 FORMAT JSONEachRow'])
        );
        $this->assertEquals("{\"1\":1}\n", $response->getContent());
    }

    /**
     * @throws Exception\Exception
     */
    public function testWorkflow()
    {
        $this->connector->performRequest(
            $this->connector->createPostRawResource(
                [], 'DROP TABLE IF EXISTS t'
            )
        );

        $this->connector->performRequest(
            $this->connector->createPostRawResource(
                [], 'CREATE TABLE IF NOT EXISTS t  (a UInt8) ENGINE = Memory'
            )
        );

        $this->connector->performRequest(
            $this->connector->createPostRawResource(
                [], 'INSERT INTO t VALUES (1), (2), (3)'
            )
        );

        $this->connector->performRequest(
            $this->connector->createPostRawResource(
                ['query' => 'INSERT INTO t FORMAT JSONEachRow'], '{"a":5}' . PHP_EOL . '{"a":6}' . PHP_EOL . '{"a":7}' . PHP_EOL
            )
        );


        $stream = fopen('php://memory', 'r+');
        fwrite($stream, '{"a":8}' . PHP_EOL . '{"a":9}' . PHP_EOL);
        rewind($stream);

        $this->connector->performRequest(
            $this->connector->createPostStreamResource(
                ['query' => 'INSERT INTO t FORMAT JSONEachRow'], $stream
            )
        );

        $response = $this->connector->performRequest(
            $this->connector->createResource(
                ['query' => 'SELECT * FROM t ORDER BY a ASC']
            )
        );

        $this->assertEquals(
            "1\n2\n3\n5\n6\n7\n8\n9\n",
            $response->getContent()
        );

        $this->connector->performRequest(
            $this->connector->createPostRawResource(
                [], 'DROP TABLE t'
            )
        );

    }
}
