<?php declare(strict_types=1);


namespace Jcsp\SocialSdk\Contract;


use Jcsp\SocialSdk\Model\SimPostVideoParams;
use Jcsp\SocialSdk\Model\SimPostVideoResult;
use Jcsp\SocialSdk\Model\SimPostVideoTask;
use Jcsp\SocialSdk\Model\SimulateAccount;

interface SimulateInterface
{

    /**
     * 模拟登录发布视频
     * @param SimPostVideoParams $params
     * @return SimPostVideoResult
     */
    public function simPostVideo(SimPostVideoParams $params): SimPostVideoResult;

    /**
     * 查询模拟登录视频发布的任务状态
     * @param string $taskId
     * @return SimPostVideoTask
     */
    public function queryTaskInfo(string $taskId): SimPostVideoTask;

    /**
     * 获取社媒发布官方账号列表
     * @return SimulateAccount[]
     */
    public function getAccountList(): array;

}