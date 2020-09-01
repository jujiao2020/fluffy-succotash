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
     * 任务id
     * @var string
     */
    private $taskId = '';

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