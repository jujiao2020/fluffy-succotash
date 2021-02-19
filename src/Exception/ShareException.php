<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Exception;


/**
 * 分享时发生异常
 * Class ShareException
 * @package Jcsp\SocialSdk\Exception
 */
class ShareException extends SocialSdkException
{
    /**
     * 是否是账号授权问题导致分享失败
     * @var bool
     */
    private $unauthorized = false;

    /**
     * 开发者看的信息
     * @var string
     */
    private $devMsg = '';

    /**
     * @return bool
     */
    public function isUnauthorized(): bool
    {
        return $this->unauthorized;
    }

    /**
     * @param bool $unauthorized
     * @return ShareException
     */
    public function setUnauthorized(bool $unauthorized): self
    {
        $this->unauthorized = $unauthorized;
        return $this;
    }

    /**
     * @return string
     */
    public function getDevMsg(): string
    {
        return $this->devMsg;
    }

    /**
     * @param string $devMsg
     * @return ShareException
     */
    public function setDevMsg(string $devMsg): self
    {
        $this->devMsg = $devMsg;
        return $this;
    }

    public function __toString()
    {
        return "Msg: {$this->getMessage()}\nDevMsg: {$this->getDevMsg()}\n" . parent::__toString();
    }

}