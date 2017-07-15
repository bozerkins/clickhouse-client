<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 1:43 PM
 */

namespace JustFuse\ClickhouseClient\Connector;


class Request
{
    /** @var  array */
    private $get = [];
    /** @var array  */
    private $post = [];
    /** @var  string */
    private $postRaw = '';
    /** @var mixed */
    private $postStream = null;

    /**
     * @return array
     */
    public function accessGet(): array
    {
        return $this->get;
    }

    /**
     * @param array $get
     */
    public function setGet(array $get)
    {
        $this->get = $get;
    }

    /**
     * @return bool
     */
    public function hasGet() : bool
    {
        return !empty($this->get);
    }

    /**
     * @return array
     */
    public function accessPost(): array
    {
        return $this->post;
    }

    /**
     * @param array $post
     */
    public function setPost(array $post)
    {
        $this->post = $post;
    }

    /**
     * @return bool
     */
    public function hasPost() : bool
    {
        return !empty($this->post);
    }

    /**
     * @return string
     */
    public function accessPostRaw(): string
    {
        return $this->postRaw;
    }

    /**
     * @param string $postRaw
     */
    public function setPostRaw(string $postRaw)
    {
        $this->postRaw = $postRaw;
    }

    /**
     * @return bool
     */
    public function hasPostRaw() : bool
    {
        return !empty($this->postRaw);
    }

    /**
     * @return mixed
     */
    public function getPostStream()
    {
        return $this->postStream;
    }

    /**
     * @param mixed $postStream
     */
    public function setPostStream($postStream)
    {
        $this->postStream = $postStream;
    }

    /**
     * @return bool
     */
    public function hasPostStream() : bool
    {
        return !empty($this->postStream);
    }
}