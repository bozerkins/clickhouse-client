<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 4:00 PM
 */

namespace ClickhouseClient\Client;


use ClickhouseClient\Client\Format\FormatInterface;
use ClickhouseClient\Client\Format\JsonFormat;
use ClickhouseClient\Connector\Connector;

class Client
{
    /** @var  Config */
    private $config;

    /** @var  Connector */
    private $connector;

    /** @var  FormatInterface */
    private $format;

    /**
     * Client constructor.
     * @param Config $config
     * @param string $defaultFormatClass
     * @throws \Exception
     */
    public function __construct(Config $config, string $defaultFormatClass = JsonFormat::class)
    {
        $this->config = $config;

        $connectorConfig = new \ClickhouseClient\Connector\Config();
        $connectorConfig->setHost($this->config->getBasics()['host']);
        $connectorConfig->setPort($this->config->getBasics()['port']);
        $connectorConfig->setProtocol($this->config->getBasics()['protocol']);
        $connectorConfig->setUser($this->config->getCredentials()['user']);
        $connectorConfig->setPassword($this->config->getCredentials()['password']);
        if ($this->config->getCurlOptions()) {
            $connectorConfig->setDefaultCurlOptions($this->config->getCurlOptions());
        }
        $this->connector = new Connector($connectorConfig);

        $this->format = null;
        if ($defaultFormatClass) {
            $this->isValidFormatClass($defaultFormatClass);
            $this->format = new $defaultFormatClass();
        }
    }

    /**
     * @param string $formatClass
     * @throws \RuntimeException
     */
    private function isValidFormatClass(string $formatClass)
    {
        if (!in_array(FormatInterface::class, class_implements($formatClass))) {
            throw new \RuntimeException('Default format class received (' . $formatClass . ') does not implement ' . FormatInterface::class);
        }
    }

    /**
     * @param string|null $formatClass
     * @return FormatInterface
     */
    private function defineFormat(string $formatClass = null)
    {
        if ($formatClass) {
            $this->isValidFormatClass($formatClass);
            return new $formatClass;
        }
        return new $this->format;
    }

    /**
     * @return \ClickhouseClient\Connector\Response
     * @throws \ClickhouseClient\Exception\Exception
     */
    public function ping()
    {
        $response = $this->connector->performRequest(
            $this->connector->createResource()
        );
        return $response;
    }

    /**
     * @param string $sql
     * @param string|null $formatClass
     * @return \ClickhouseClient\Connector\Response
     * @throws \ClickhouseClient\Exception\Exception
     */
    public function query(string $sql, string $formatClass = null)
    {
        $format = $this->defineFormat($formatClass);

        // add format to SQL
        $sql = rtrim(trim($sql), ';');
        $sql .= ' FORMAT ' . $format->queryFormat();

        $response = $this->connector->performRequest(
            $this->connector->createResource(
                array_merge(
                    $this->config->getSettings(),
                    ['query' => $sql]
                )
            )
        );

        $response->setFormat($format);

        return $response;
    }

    /**
     * @param string $sql
     * @param $stream
     * @param string|null $formatClass
     * @return \ClickhouseClient\Connector\Response
     * @throws \ClickhouseClient\Exception\Exception
     */
    public function queryStream(string $sql, $stream, string $formatClass = null)
    {
        $format = $this->defineFormat($formatClass);

        // add format to SQL
        $sql = rtrim(trim($sql), ';');
        $sql .= ' FORMAT ' . $format->queryFormat();

        $ch = $this->connector->createResource(
            array_merge(
                $this->config->getSettings(),
                ['query' => $sql]
            )
        );

        //give curl the file pointer so that it can write to it
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FILE, $stream);

        $response = $this->connector->performRequest($ch);

        return $response;

    }

    /**
     * @param string $sql
     * @param \Closure $closure
     * @param string|null $formatClass
     * @return \ClickhouseClient\Connector\Response
     * @throws \ClickhouseClient\Exception\Exception
     */
    public function queryClosure(string $sql, \Closure $closure, string $formatClass = null)
    {
        $format = $this->defineFormat($formatClass);

        // add format to SQL
        $sql = rtrim(trim($sql), ';');
        $sql .= ' FORMAT ' . $format->queryFormat();

        $ch = $this->connector->createResource(
            array_merge(
                $this->config->getSettings(),
                ['query' => $sql]
            )
        );

        $buffer = '';

        //give curl the file pointer so that it can write to it
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $str) use ($closure, &$buffer) {

            // explode lines into array
            $lines = explode("\n", $buffer . $str);

            // get last element
            $lastLine = end($lines);

            // check if is normal string
            if (substr($lastLine, -1) !== "\n") {
                // remove from lines array
                array_pop($lines);
                // add to buffer
                $buffer = $lastLine;
            } else {
                // clear buffer
                $buffer = '';
            }

            foreach($lines as $line) {
                $closure($line);
            }

            return strlen($str);
        });

        $response = $this->connector->performRequest($ch);

        return $response;
    }

    /**
     * @param string $sql
     * @return \ClickhouseClient\Connector\Response
     * @throws \ClickhouseClient\Exception\Exception
     */
    public function write(string $sql)
    {
        $response = $this->connector->performRequest(
            $this->connector->createPostRawResource(
                $this->config->getSettings(),
                $sql
            )
        );
        return $response;
    }

    /**
     * @param string $sql
     * @param array $rows
     * @param string|null $formatClass
     * @return \ClickhouseClient\Connector\Response
     * @throws \ClickhouseClient\Exception\Exception
     */
    public function writeRows(string $sql, array $rows, string $formatClass = null)
    {
        // set default format
        $format = $this->defineFormat($formatClass);

        // add format to SQL
        $sql = rtrim(trim($sql), ';');
        $sql .= ' FORMAT ' . $format->insertFormat();

        // encode rows
        $rowsEncoded = '';
        foreach($rows as $row) {
            $rowsEncoded .= $this->format->encode($row) . PHP_EOL;
        }

        $response = $this->connector->performRequest(
            $this->connector->createPostRawResource(
                array_merge(
                    $this->config->getSettings(),
                    ['query' => $sql]
                ),
                $rowsEncoded
            )
        );
        return $response;
    }

    /**
     * @param string $sql
     * @param $resource
     * @param string|null $formatClass
     * @return \ClickhouseClient\Connector\Response
     * @throws \ClickhouseClient\Exception\Exception
     */
    public function writeStream(string $sql, $resource, string $formatClass = null)
    {
        // set default format
        $format = $this->defineFormat($formatClass);

        // add format to SQL
        $sql = rtrim(trim($sql), ';');
        $sql .= ' FORMAT ' . $format->insertFormat();

        $response = $this->connector->performRequest(
            $this->connector->createPostStreamResource(
                array_merge(
                    $this->config->getSettings(),
                    ['query' => $sql]
                ),
                $resource
            )
        );

        return $response;
    }

    /**
     * @param string $sql
     * @return \ClickhouseClient\Connector\Response
     * @throws \ClickhouseClient\Exception\Exception
     */
    public function system(string $sql)
    {
        $response = $this->connector->performRequest(
            $this->connector->createPostRawResource(
                $this->config->getSettings(),
                $sql
            )
        );
        return $response;
    }
}
