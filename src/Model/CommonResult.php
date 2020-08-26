<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;


/**
 * 通用返回
 * Class CommonResult
 * @package Jcsp\SocialSdk\Model
 */
class CommonResult
{

    /**
     * 是否成功（200：成功，其他：失败）
     * @var int
     */
    private $status = 0;

    /**
     * 提示信息
     * @var string
     */
    private $msg = '';

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
     * 结果是否成功
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->status == 200;
    }

}