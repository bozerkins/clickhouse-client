<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 1:35 PM
 */

namespace ClickhouseClient\Connector;


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
     * @param Request $request
     * @param FormatInterface|null $format
     * @return Response
     * @throws Exception
     */
    public function perform(Request $request, FormatInterface $format = null) : Response
    {
        // create curl resource
        $ch = curl_init();

        // set default curl options
        foreach($this->config->getDefaultCurlOptions() as $key => $option) {
            curl_setopt($ch, $key, $option);
        }

        // set url
        curl_setopt(
            $ch,
            CURLOPT_URL,
            sprintf(
                '%s://%s:%s%s',
                $this->config->getProtocol(),
                $this->config->getHost(),
                $this->config->getPort(),
                ($request->hasGet() ? '?' . http_build_query($request->accessGet()) : '')
            )
        );

        // set post parameters
        if ($request->hasPost() || $request->hasPostRaw() || $request->hasPostStream()) {
            $postQuery = '';
            if ($request->hasPostStream()) {
                curl_setopt($ch, CURLOPT_UPLOAD, 1);
                curl_setopt($ch, CURLOPT_INFILE, $request->getPostStream());
                curl_setopt($ch, CURLOPT_INFILESIZE, ftell($request->getPostStream()));
            }
            if ($request->hasPostRaw()) {
                $postQuery = $request->accessPostRaw();
            }
            if ($request->hasPost()) {
                $postQuery = http_build_query($request->accessPost());
            }

            curl_setopt($ch, CURLOPT_POST, 1);
            if ($postQuery !== '') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postQuery);
            }
        }

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // set headers
        $headers = [];
        $headers[] = "X-ClickHouse-User: " . $this->config->getUser();
        $headers[] = "X-ClickHouse-Key: " . $this->config->getPassword();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // $output contains the output string
        $output = curl_exec($ch);
        // create response
        $response = new Response($output, curl_getinfo($ch), $format);

        // process curl error
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        if ($curlError || $curlErrno) {
            throw new Exception($curlError . ':' . $curlErrno, $this->config, $request, $response);
        }
        // close curl resource to free up system resources
        curl_close($ch);
        // process http error
        if ($response->getHttpCode() !== 200) {
            if ($response->getHttpCode() === 0 && empty($output)) {
                throw new Exception('Could not connect', $this->config, $request, $response);
            }
            throw new Exception($output, $this->config, $request, $response);
        }

        // return proper response
        return $response;
    }
}