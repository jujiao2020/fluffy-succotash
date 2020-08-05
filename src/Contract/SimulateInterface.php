<?php declare(strict_types=1);


namespace Jcsp\SocialSdk\Contract;


use Jcsp\SocialSdk\Model\SimulateVideoPostParams;
use Jcsp\SocialSdk\Model\SimulateAccount;
use Jcsp\SocialSdk\Model\SimulatePostTask;
use Jcsp\SocialSdk\Model\SimulateVideoPostTask;

interface SimulateInterface
{

    /**
     * 模拟登录发布视频
     * @param SimulateVideoPostParams $params
     * @return SimulatePostTask
     */
    public function simPostVideo(SimulateVideoPostParams $params): SimulatePostTask;

    /**
     * 处理模拟登录发布回调处理
     * @param array $requestParams
     * @return SimulatePostTask
     */
    public function handleSimPostCallback(array $requestParams): SimulatePostTask;

    /**
     * 查询模拟登录视频发布的任务状态
     * @param string $taskId
     * @return SimulateVideoPostTask
     */
    public function queryTaskInfo(string $taskId): SimulateVideoPostTask;

    /**
     * 获取社媒发布官方账号列表
     * @return SimulateAccount[]
     */
    public function getAccountList(): array;

}