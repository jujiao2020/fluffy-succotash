<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;


/**
 * 模拟登录发布账号绑定信息
 * Class SimulateUserBindInfo
 * @package Jcsp\SocialSdk\Model
 */
class SimulateAccountBindInfo
{

    // 绑定状态常量
    /** @var int 未知 */
    const BIND_STATUS_UNKNOWN = 0;
    /** @var int 绑定成功 */
    const BIND_STATUS_SUCCESS = 1;
    /** @var int 绑定失败 */
    const BIND_STATUS_FAIL = 2;
    /** @var int 需要提供验证信息 */
    const BIND_STATUS_NEED_VERIFICATION = 3;
    /** @var int 验证信息处理中 */
    const BIND_STATUS_VERIFICATION_HANDLING = 4;

    /**
     * 用户id
     * @var string
     */
    private $userId = 0;

    /**
     * 社媒英文名称
     * @var string
     */
    private $socialMediaName = '';

    /**
     * 用户社媒登陆账号
     * @var string
     */
    private $account = '';

    /**
     * 社媒id
     * @var string
     */
    private $socialId = '';

    /**
     * 显示名称
     * @var string
     */
    private $displayName = '';

    /**
     * 社媒页面链接
     * @var string
     */
    private $pageUrl = '';

    /**
     * 头像链接
     * @var string
     */
    private $headImgUrl = '';

    /**
     * 状态
     * @var int
     */
    private $status = 0;

    /**
     * 校验类型，1：验证码
     * @var int
     */
    private $verifyType = 1;

    /**
     * 校验提示信息
     * @var string
     */
    private $verifyTips = "";

    /**
     * 人机验证url
     * @var string
     */
    private $verifyUrl = "";

    /**
     * 信息
     * @var string
     */
    private $msg = "";

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
    public function getSocialId(): string
    {
        return $this->socialId;
    }

    /**
     * @param string $socialId
     */
    public function setSocialId(string $socialId): void
    {
        $this->socialId = $socialId;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     */
    public function setDisplayName(string $displayName): void
    {
        $this->displayName = $displayName;
    }

    /**
     * @return string
     */
    public function getPageUrl(): string
    {
        return $this->pageUrl;
    }

    /**
     * @param string $pageUrl
     */
    public function setPageUrl(string $pageUrl): void
    {
        $this->pageUrl = $pageUrl;
    }

    /**
     * @return string
     */
    public function getHeadImgUrl(): string
    {
        return $this->headImgUrl;
    }

    /**
     * @param string $headImgUrl
     */
    public function setHeadImgUrl(string $headImgUrl): void
    {
        $this->headImgUrl = $headImgUrl;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getVerifyType(): int
    {
        return $this->verifyType;
    }

    /**
     * @param int $verifyType
     */
    public function setVerifyType(int $verifyType): void
    {
        $this->verifyType = $verifyType;
    }

    /**
     * @return string
     */
    public function getVerifyTips(): string
    {
        return $this->verifyTips;
    }

    /**
     * @param string $verifyTips
     */
    public function setVerifyTips(string $verifyTips): void
    {
        $this->verifyTips = $verifyTips;
    }

    /**
     * @return string
     */
    public function getMsg(): string
    {
        return $this->msg;
    }

    /**
     * @param string $msg
     */
    public function setMsg(string $msg): void
    {
        $this->msg = $msg;
    }

    /**
     * @return string
     */
    public function getVerifyUrl(): string
    {
        return $this->verifyUrl;
    }

    /**
     * @param string $verifyUrl
     */
    public function setVerifyUrl(string $verifyUrl): void
    {
        $this->verifyUrl = $verifyUrl;
    }

}