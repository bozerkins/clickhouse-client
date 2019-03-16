<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 5:51 PM
 */

namespace ClickhouseClient;

use PHPUnit\Framework\TestCase;

abstract class DefaultTest extends TestCase
{
    /** @var  array */
    protected $config;

    /**
     *
     */
    protected function setUp()
    {
        $this->config = [];
        $this->config['host'] = getenv('CLICKHOUSE_HOST') ?: '127.0.0.1';
        $this->config['port'] = getenv('CLICKHOUSE_PORT') ?: '8123';
        $this->config['protocol'] = getenv('CLICKHOUSE_PROTOCOL') ?: 'http';
        $this->config['database'] = getenv('CLICKHOUSE_DATABASE') ?: 'default';
        $this->config['user'] = getenv('CLICKHOUSE_USER') ?: 'default';
        $this->config['password'] = getenv('CLICKHOUSE_PASSWORD') ?: '';
    }
}
