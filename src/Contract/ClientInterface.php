<?php

namespace Jcsp\SocialSdk\Contract;

/**
 * Interface ClientInterface
 * @package Jcsp\SocialSdk\Contract
 */
interface ClientInterface
{

    public function init();

    /**
     * @param mixed|array $config
     * @param null $value
     * @return mixed
     */
    public function setConfig($config,$value = null);

    /**
     * @param string|null $key
     * @return mixed
     */
    public function getConfig(string $key = null);

}
