<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;


/**
 * 提交验证信息，用于某些社媒账号绑定
 * Class SimulateAccountBindVerificationParams
 * @package Jcsp\SocialSdk\Model
 */
class SimulateAccountBindVerificationParams
{

    /**
     * 社媒英文名称
     * @var string
     */
    private $socialMediaName = '';

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
     * 校验信息，通常是校验码
     * @var string
     */
    private $verificationString = '';

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
    public function getVerificationString(): string
    {
        return $this->verificationString;
    }

    /**
     * @param string $verificationString
     */
    public function setVerificationString(string $verificationString): void
    {
        $this->verificationString = $verificationString;
    }

}