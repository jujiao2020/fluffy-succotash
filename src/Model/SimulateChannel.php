<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;

/**
 * 模拟登录发布任务信息
 * Class SimulateChannel
 * @package Jcsp\SocialSdk\Model
 */
class SimulateChannel extends BaseModel
{

    /**
     * 任务id
     * @var string
     */
    private $taskId = '';

    /**
     * 用户id
     * @var int
     */
    private $userId = '';

    /**
     * 用户账号
     * @var string
     */
    private $account = '';

    /**
     * channel 的社媒 id
     * @var string
     */
    private $socialId = '';

    /**
     * 显示名称
     * @var string
     */
    private $displayName = '';

    /**
     * channel 图片链接
     * @var string
     */
    private $imgUrl = '';

    /**
     * channel 链接
     * @var string
     */
    private $pageUrl = '';

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
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId(int $userId): void
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
    public function getImgUrl(): string
    {
        return $this->imgUrl;
    }

    /**
     * @param string $imgUrl
     */
    public function setImgUrl(string $imgUrl): void
    {
        $this->imgUrl = $imgUrl;
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

}
