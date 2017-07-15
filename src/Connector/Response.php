<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 1:44 PM
 */

namespace JustFuse\ClickhouseClient\Connector;

use JustFuse\ClickhouseClient\Client\Format\FormatInterface;

class Response
{
    /** @var  string */
    private $content;

    /** @var  array */
    private $details;

    /** @var  FormatInterface|null */
    private $format;

    /**
     * Response constructor.
     * @param string $content
     * @param array $details
     * @param FormatInterface|null $format
     */
    public function __construct(string $content, array $details, FormatInterface $format = null)
    {
        $this->setContent($content);
        $this->setDetails($details);
        $this->setFormat($format);
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        if ($this->hasFormat()) {
            return $this->getFormat()->decode($this->content);
        }
        return $this->content;
    }

    /**
     * @return string
     */
    public function getContentRaw() : string
    {
        return $this->content;
    }

    /**
     * @param string $output
     */
    private function setContent(string $output)
    {
        $this->content = $output;
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

    /**
     * @return FormatInterface
     */
    private function getFormat() : FormatInterface
    {
        return $this->format;
    }

    /**
     * @param FormatInterface|null $format
     */
    private function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * @return bool
     */
    private function hasFormat()
    {
        return $this->format !== null;
    }
}