<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;


class SimPostVideoResult
{
    /**
     * 状态码（204：已经提交过了，200：提交成功，其他值：错误）
     * @var int
     */
    private $status = 0;

    /**
     * 接口信息
     * @var string
     */
    private $msg = '';

    /**
     * 提示信息（给运营人员或客户看的）
     * @var string
     */
    private $info = '';

    /**
     * 任务id
     * @var string
     */
    private $taskId = '';

    /**
     * 分享链接（以前分享成功过的会有）
     * @var string
     */
    private $url = '';

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

}