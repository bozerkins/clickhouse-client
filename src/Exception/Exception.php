<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 2:55 PM
 */

namespace ClickhouseClient\Exception;


use ClickhouseClient\Connector\Config;
use ClickhouseClient\Connector\Request;
use ClickhouseClient\Connector\Response;
use Throwable;

class Exception extends \Exception
{
    /** @var  Response */
    private $response;

    /** @var  Request */
    private $request;

    /** @var  Config */
    private $config;

    /**
     * Exception constructor.
     * @param string $message
     * @param Config $config
     * @param Request $request
     * @param Response $response
     * @param Throwable|null $previous
     */
    public function __construct($message, Config $config, Request $request, Response $response, Throwable $previous = null)
    {
        $this->config = $config;
        $this->request = $request;
        $this->response = $response;

        $code = $response->getHttpCode();
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }
}