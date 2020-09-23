<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;


/**
 * 模拟登录用户账号绑定参数
 * Class SimulateUserBindParams
 * @package Jcsp\SocialSdk\Model
 */
class SimulateAccountBindParams
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
     * 密码
     * @var string
     */
    private $pwd = '';

    /**
     * 任务id（重新绑定的时候，允许不提供密码，只提供任务id）
     * @var string
     */
    private $taskId = '';

    /**
     * 社媒英文名称
     * @var string
     */
    private $socialMediaName = '';

    /**
     * 电话号码，用于校验账号
     * @var string
     */
    private $phone = '';

    /**
     * 绑定流程回调url
     * @var string
     */
    private $callbackUrl = '';

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
    public function getPwd(): string
    {
        return $this->pwd;
    }

    /**
     * @param string $pwd
     */
    public function setPwd(string $pwd): void
    {
        $this->pwd = $pwd;
    }

    /**
     * @return string
     */
    public function getTaskId(): string
    {
        return $this->taskId;
    }

    /**
     * @param string $taskId
     */
    public function setTaskId(string $taskId): void
    {
        $this->taskId = $taskId;
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

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return string
     */
    public function getCallbackUrl(): string
    {
        return $this->callbackUrl;
    }

    /**
     * @param string $callbackUrl
     */
    public function setCallbackUrl(string $callbackUrl): void
    {
        $this->callbackUrl = $callbackUrl;
    }

}