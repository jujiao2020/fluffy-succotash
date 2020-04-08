<?php

namespace Jcsp\SocialSdk\Contract;

/**
 * Interface ClientInterface
 * @package Jcsp\SocialSdk\Contract
 */
interface SimulateInterface
{

    /**
     * @param string $videoUrl
     * @param string $videoName
     * @param array $params
     * @return array
     */
    public function postVideo(string $videoUrl,string $videoName ='',array $params =[]):array;

}
