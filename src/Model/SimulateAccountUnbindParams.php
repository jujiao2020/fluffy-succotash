<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;


/**
 * 模拟登录用户账号解绑参数
 * Class SimulateUserUnbindParams
 * @package Jcsp\SocialSdk\Model
 */
class SimulateAccountUnbindParams
{
    /**
     * 用户id
     * @var string
     */
    private $userId = 0;

    /**
     * 用户社媒登陆账号
     * @var string
     */
    private $account = '';

    /**
     * 社媒英文名称
     * @var string
     */
    private $socialMediaName = '';

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     */
    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getAccount(): string
    {
        return $this->account;
    }

    /**
     * @param string $account
     */
    public function setAccount(string $account): void
    {
        $this->account = $account;
    }

    /**
     * @return string
     */
    public function getSocialMediaName(): string
    {
        return $this->socialMediaName;
    }

    /**
     * @param string $socialMediaName
     */
    public function setSocialMediaName(string $socialMediaName): void
    {
        $this->socialMediaName = $socialMediaName;
    }

}