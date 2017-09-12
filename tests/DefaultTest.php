<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 5:51 PM
 */

namespace ClickhouseClient\Tests;

use PHPUnit\Framework\TestCase;

abstract class DefaultTest extends TestCase
{
    /** @var  array */
    protected $config;

    protected function setUp()
    {
        $this->config = [];
        $this->config['host'] = getenv('JF_CLICKHOUSE_HOST') ?: '127.0.0.1';
        $this->config['port'] = getenv('JF_CLICKHOUSE_PORT') ?: '8123';
        $this->config['protocol'] = getenv('JF_CLICKHOUSE_PROTOCOL') ?: 'http';
        $this->config['database'] = getenv('JF_CLICKHOUSE_DATABASE') ?: 'testdb';
        $this->config['user'] = getenv('JF_CLICKHOUSE_USER') ?: 'default';
        $this->config['password'] = getenv('JF_CLICKHOUSE_PASSWORD') ?: '';
    }
}
