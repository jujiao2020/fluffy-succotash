<?php declare(strict_types=1);


namespace Jcsp\SocialSdk;



use Jcsp\SocialSdk\Contract\ClientInterface;

/**
 * Class AbstractRateLimiter
 *
 * @since 2.0`
 */
abstract class AbstractClient implements ClientInterface
{
    protected $config = [];

    public function init(){

    }

    /**
     * @param array|mixed $config
     * @param null $value
     * @return void
     */
    public function setConfig($config, $value = null)
    {
        if (is_array($config)){
            $this->config = $config;
        }else{
            $this->config[$config] = $value;
        }

    }

    public function getConfig(string $key = null)
    {
        return is_null($key)?$this->config:($this->config[$key]??null);
    }
}