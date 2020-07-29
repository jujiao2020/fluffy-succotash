<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;


class SimPostVideoTask
{
    // 发布状态常量
    const TASK_STATUS_UNKNOWN = 0;
    const TASK_STATUS_SUCCESS = 1;
    const TASK_STATUS_PLATFORM_REVIEWING = 2;
    const TASK_STATUS_FAIL = 3;

    /**
     * 任务id
     * @var string
     */
    private $taskId = '';

    /**
     * 状态
     * @var int
     */
    private $status = 0;

    /**
     * 提示信息
     * @var string
     */
    private $msg = '';

    /**
     * 提示信息（给运营人员或客户看的）
     * @var string
     */
    private $info = '';

    /**
     * 状态
     * @var string
     */
    private $url = '';

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
     * 回调地址
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
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

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