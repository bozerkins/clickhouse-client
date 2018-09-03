<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 1:35 PM
 */

namespace ClickhouseClient\Connector;


use ClickhouseClient\Client\Config;
use ClickhouseClient\Client\Format\FormatInterface;
use ClickhouseClient\Exception\Exception;

class Connector
{
    /** @var  Config */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param array $query
     * @return resource
     */
    public function createResource(array $query = [])
    {
        // create curl resource
        $ch = curl_init();

        // set default curl options
        foreach ($this->config->getCurlOptions() as $key => $option) {
            curl_setopt($ch, $key, $option);
        }

        // set url
        curl_setopt(
            $ch,
            CURLOPT_URL,
            $this->config->getCurlUrl() . ($query ? '?' . http_build_query($query) : '')
        );

        // set headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->config->getCredentialHeaders());

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        return $ch;
    }

    /**
     * @param array $query
     * @param string $post
     * @return resource
     */
    public function createPostRawResource(array $query, string $post)
    {
        $ch = $this->createResource($query);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        return $ch;
    }

    /**
     * @param array $query
     * @param $resource
     * @return resource
     */
    public function createPostStreamResource(array $query, $resource)
    {
        $ch = $this->createResource($query);

        curl_setopt($ch, CURLOPT_UPLOAD, true);
        curl_setopt($ch, CURLOPT_INFILE, $resource);
        curl_setopt($ch, CURLOPT_POST, true);

        return $ch;
    }

    /**
     * @param $resource
     * @param FormatInterface|null $format
     * @return Response
     * @throws Exception
     */
    public function performRequest($resource, FormatInterface $format = null)
    {
        // $output contains the output string
        $output = curl_exec($resource);
        // create response
        $response = new Response($output, curl_getinfo($resource));
        $response->setFormat($format);

        // process curl error
        $curlError = curl_error($resource);
        $curlErrno = curl_errno($resource);
        if ($curlError || $curlErrno) {
            throw new Exception($curlError . ':' . $curlErrno, $this->config, $response);
        }
        // close curl resource to free up system resources
        curl_close($resource);
        // process http error
        if ($response->getHttpCode() !== 200) {
            if ($response->getHttpCode() === 0 && empty($output)) {
                throw new Exception('Could not connect', $this->config, $response);
            }
            throw new Exception($output, $this->config, $response);
        }

        // return proper response
        return $response;
    }
}
