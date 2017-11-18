<?php
/**
 * Created by PhpStorm.
 * User: bogdans
 * Date: 7/15/17
 * Time: 4:00 PM
 */

namespace ClickhouseClient\Client;

use Symfony\Component\OptionsResolver\OptionsResolver;

class Config
{
    /** @var  array */
    private $basics;
    /** @var  array */
    private $settings;
    /** @var  array */
    private $credentials;
    /** @var  array */
    private $curlOptions;

    /**
     * Config constructor.
     * @param array $basics
     * @param array $settings
     * @param array $credentials
     * @param array $curlOptions
     */
    public function __construct(array $basics = [], array $settings = [], array $credentials = [], array $curlOptions = [])
    {
        // resolve basic options
        $resolver = new OptionsResolver();
        $this->configureBasicOptions($resolver);
        $this->basics = $resolver->resolve($basics);

        // resolve credentials
        $resolver = new OptionsResolver();
        $this->configureCredentials($resolver);
        $this->credentials = $resolver->resolve($credentials);

        // set settings
        $this->settings = $settings;

        // set curl options
        $this->curlOptions = $curlOptions;
    }

    /**
     * @param OptionsResolver $resolver
     */
    private function configureBasicOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'host' => '127.0.0.1',
            'port' => '8123',
            'protocol' => 'http',
        ]);

        $resolver->setAllowedValues('protocol', ['http', 'https']);
    }

    /**
     * @param OptionsResolver $resolver
     */
    private function configureCredentials(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'user' => 'default',
            'password' => ''
        ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    private function configureCurlOptions(OptionsResolver $resolver)
    {
        //todo: implement or remove this method
    }

    /**
     * Change setting value (or set a new one)
     *
     * @param $key
     * @param $value
     */
    public function change($key, $value)
    {
        $this->settings[$key] = $value;
    }

    /**
     * Remove setting value (if setting is set)
     * @param $key
     * @return bool
     */
    public function remove($key)
    {
        if (array_key_exists($key, $this->settings)) {
            unset($this->settings[$key]);
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public function getBasics(): array
    {
        return $this->basics;
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @return array
     */
    public function getCredentials(): array
    {
        return $this->credentials;
    }

    /**
     * @return array
     */
    public function getCurlOptions(): array
    {
        return $this->curlOptions;
    }
}
