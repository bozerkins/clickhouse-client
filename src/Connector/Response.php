<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 1:44 PM
 */

namespace JustFuse\ClickhouseClient\Connector;

class Response
{
    /** @var  string */
    private $output;

    /** @var  array */
    private $details;

    /**
     * Response constructor.
     * @param string $output
     * @param array $details
     */
    public function __construct(string $output, array $details)
    {
        $this->setOutput($output);
        $this->setDetails($details);
    }

    /**
     * @return string
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * @param string $output
     */
    private function setOutput(string $output)
    {
        $this->output = $output;
    }

    /**
     * @return array
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @return int
     */
    public function getHttpCode() : int
    {
        return $this->details['http_code'];
    }

    /**
     * @param array $details
     */
    private function setDetails(array $details)
    {
        $this->details = $details;
    }
}