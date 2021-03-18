<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;

/**
 * 模拟登录发布任务信息
 * Class SimulatePostTask
 * @package Jcsp\SocialSdk\Model
 */
class SimulatePostTask
{
    // 任务发布状态常量
    /** @var int 未知 */
    const TASK_STATUS_UNKNOWN = 0;
    /** @var int 发布成功 */
    const TASK_STATUS_SUCCESS = 1;
    /** @var int 第三方平台审核中 */
    const TASK_STATUS_PLATFORM_REVIEWING = 2;
    /** @var int 发布失败 */
    const TASK_STATUS_FAIL = 3;
    /** @var int 发布成功，但任务是以前曾经成功发布过 */
    const TASK_STATUS_ALREADY_POST = 4;

    /**
     * 任务id
     * @var string
     */
    private $taskId = '';

    /**
     * 任务发布状态
     * @var int
     */
    private $taskStatus = self::TASK_STATUS_UNKNOWN;

    /**
     * 任务提示信息（给开发人员看）
     * @var string
     */
    private $msg = '';

    /**
     * 任务提示信息（给运营人员或客户看的）
     * @var string
     */
    private $info = '';

    /**
     * 错误截图链接
     * @var string
     */
    private $errScreenShotUrl = '';

    /**
     * 分享链接（发布成功才有）
     * @var string
     */
    private $postUrl = '';

    /**
     * 任务回调url
     * @var string
     */
    private $callbackUrl = '';

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
    public function getTaskStatus(): int
    {
        return $this->taskStatus;
    }

    /**
     * @param int $taskStatus
     */
    public function setTaskStatus(int $taskStatus): void
    {
        $this->taskStatus = $taskStatus;
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
    public function getInfo(): string
    {
        return $this->info;
    }

    /**
     * @param string $info
     */
    public function setInfo(string $info): void
    {
        $this->info = $info;
    }

    /**
     * @return string
     */
    public function getErrScreenShotUrl(): string
    {
        return $this->errScreenShotUrl;
    }

    /**
     * @param string $errScreenShotUrl
     */
    public function setErrScreenShotUrl(string $errScreenShotUrl): void
    {
        $this->errScreenShotUrl = $errScreenShotUrl;
    }

    /**
     * @return string
     */
    public function getPostUrl(): string
    {
        return $this->postUrl;
    }

    /**
     * @param string $postUrl
     */
    public function setPostUrl(string $postUrl): void
    {
        $this->postUrl = $postUrl;
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