<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;


/**
 * 绑定账号返回结果
 * Class SimulateBindAccountResult
 * @package Jcsp\SocialSdk\Model
 */
class SimulateBindAccountResult extends CommonResult
{

    /**
     * 任务id
     * @var string
     */
    private $taskId = '';

    /**
     * 绑定信息
     * @var SimulateAccountBindInfo|null
     */
    private $simulateAccountBindInfo = null;

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
     * @return SimulateAccountBindInfo|null
     */
    public function getSimulateAccountBindInfo(): ?SimulateAccountBindInfo
    {
        return $this->simulateAccountBindInfo;
    }

    /**
     * @param SimulateAccountBindInfo|null $simulateAccountBindInfo
     */
    public function setSimulateAccountBindInfo(?SimulateAccountBindInfo $simulateAccountBindInfo): void
    {
        $this->simulateAccountBindInfo = $simulateAccountBindInfo;
    }

}