<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 9/12/17
 * Time: 10:32 PM
 */

namespace ClickhouseClient\Connector;


use ClickhouseClient\Client\Format\FormatInterface;

class Response
{
    /** @var  string */
    private $output;
    /** @var  array */
    private $curlinfo;
    /** @var  FormatInterface|null */
    private $format;

    /**
     * Response constructor.
     * @param $output
     * @param $curlinfo
     */
    public function __construct(string $output, array $curlinfo)
    {
        $this->output = $output;
        $this->curlinfo = $curlinfo;
    }

    /**
     * @return int
     */
    public function getHttpCode() : int
    {
        return $this->curlinfo['http_code'];
    }

    /**
     * @return array
     */
    public function getDetails()
    {
        return $this->curlinfo;
    }

    /**
     * @return FormatInterface|null
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param FormatInterface|null $format
     */
    public function setFormat(FormatInterface $format = null)
    {
        $this->format = $format;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        if ($this->format !== null) {
            return $this->format->decode($this->output);
        }
        return $this->output;
    }

    /**
     * @return mixed
     */
    public function getOutput()
    {
        return $this->output;
    }
}