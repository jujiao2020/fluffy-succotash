<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;


/**
 * 模拟登录视频发布任务信息
 * Class SimulateVideoPostTask
 * @package Jcsp\SocialSdk\Model
 */
class SimulateVideoPostTask extends SimulatePostTask
{
    /**
     * 发布的标题
     * @var string
     */
    private $title = '';

    /**
     * 发布的描述
     * @var string
     */
    private $description;

    /**
     * 发布到的账号
     * @var string
     */
    private $account = '';

    /**
     * 原始视频url
     * @var string
     */
    private $originVideoUrl = '';

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
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
    public function getOriginVideoUrl(): string
    {
        return $this->originVideoUrl;
    }

    /**
     * @param string $originVideoUrl
     */
    public function setOriginVideoUrl(string $originVideoUrl): void
    {
        $this->originVideoUrl = $originVideoUrl;
    }

}