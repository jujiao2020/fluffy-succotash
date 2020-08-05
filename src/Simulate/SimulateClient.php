<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Simulate;


use Jcsp\SocialSdk\Contract\LoggerInterface;
use Jcsp\SocialSdk\Contract\SimulateInterface;
use Jcsp\SocialSdk\Exception\SocialSdkException;
use Jcsp\SocialSdk\Model\SimulatePostTask;
use Jcsp\SocialSdk\Model\SimulateVideoPostParams;
use Jcsp\SocialSdk\Model\SimulateAccount;
use Jcsp\SocialSdk\Model\SimulateVideoPostTask;

class SimulateClient implements SimulateInterface
{

    /**
     * 日志处理
     * logger
     * @var LoggerInterface
     */
    private $logger;

    /**
     * 配置
     * @var array
     */
    private $config;

    /**
     * SimulateClient constructor.
     * @param LoggerInterface $logger
     * @param array $config
     */
    public function __construct(LoggerInterface $logger, array $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * 模拟登录发布视频
     * @param SimulateVideoPostParams $params
     * @return SimulatePostTask
     * @throws SocialSdkException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function simPostVideo(SimulateVideoPostParams $params): SimulatePostTask
    {
        // 校验
        if (empty($params->getSocialMediaName())) {
            throw new SocialSdkException("社媒名称不能为空");
        }
        if (strlen($params->getTitle()) == 0) {
            throw new SocialSdkException("分享标题不能为空");
        }

        // 构造参数
        $requestParams = [
            'video_url' => $params->getVideoUrl(),
            'title' => $params->getTitle(), // 100字符以内
            'desc' => $params->getDescription(), // 视频描述，5000字以内
            'callback' => $params->getCallbackUrl(),
            'media' => strtolower($params->getSocialMediaName()),
            'user' => $params->getAccount(),
        ];

        // 请求链接
        $endpoint = $this->config['post_video_endpoint'] ?? '';
        if (empty($endpoint)) {
            throw new SocialSdkException('发布链接不能为空');
        }

        // 发起请求
        $client = new \GuzzleHttp\Client();
        $res = $client->request('POST', $endpoint, [
            'form_params' => $requestParams,
            'timeout' => 30,
        ]);

        // 处理响应，响应格式：
        // {"status": 200, "msg": "success", "info": "success", "task_id": "1bfb11ecfbd6d29494282d965a189fa"}
        // {"status": 204, "msg": "video is exists", "post_url": "https://www.xxx.com/video/x7v9ybc", "info": "任务已存在，请勿重复提交"}
        $resBody = "";
        try {
            $resBody = $res->getBody()->getContents();
        } catch (\Exception $ex) {
        }
        $resData = json_decode($resBody, true);
        $hasError = $res->getStatusCode() != 200 || empty($resData) || !is_array($resData) || !in_array((int)($resData['status'] ?? 0), [200, 204]);

        // 写日志
        $logStr = "请求url：{$endpoint}\n请求参数：\n" . var_export($requestParams, true) . "\n响应状态码：{$res->getStatusCode()}，响应结果：\n{$resBody}";
        $this->writeLog(empty($apiResult['error']) ? 'info' : 'error', $logStr, 'sim_post_video');

        // 分析结果
        if ($hasError) {
            throw new SocialSdkException("调用模拟登录发布接口失败：$resBody");
        }
        $msg = $resData['msg'] ?? "调用模拟登录发布接口失败：" . $resData['error'];

        // 构造数据
        $task = new SimulatePostTask();
        $task->setTaskId($resData['task_id'] ?? '');
        $task->setTaskStatus($this->calTaskStatus((int)($resData['status'] ?? 0)));
        $task->setMsg($msg);
        $task->setInfo((string)($resData['info'] ?? ''));
        $task->setPostUrl($resData['post_url'] ?? '');
        $task->setCallbackUrl($params->getCallbackUrl());
        return $task;
    }

    /**
     * 处理模拟登录发布回调处理
     * @param array $requestParams
     * @return SimulatePostTask
     */
    public function handleSimPostCallback(array $requestParams): SimulatePostTask
    {
        // 计算任务状态
        $taskStatus = $this->calTaskStatus((int)($requestParams['status'] ?? 0));

        // 写日志
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $logStr = "访问路径: {$requestUri}， 请求参数：\n" . var_export($requestParams, true);
        $this->writeLog($taskStatus == SimulatePostTask::TASK_STATUS_SUCCESS ? 'info' : 'warn', $logStr, 'handle_sim_post_callback');

        // 构造数据
        $task = new SimulatePostTask();
        $task->setTaskId($requestParams['task_id'] ?? '');
        $task->setTaskStatus($taskStatus);
        $task->setMsg((string)($requestParams['msg'] ?? ''));
        $task->setInfo((string)($requestParams['info'] ?? ''));
        $task->setPostUrl((string)($requestParams['url'] ?? ''));
        $task->setCallbackUrl('');
        return $task;
    }

    /**
     * 查询模拟登录视频发布的任务状态
     * @param string $taskId
     * @return SimulateVideoPostTask
     * @throws SocialSdkException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function queryTaskInfo(string $taskId): SimulateVideoPostTask
    {
        // 构造参数
        $params = [
            'task_id' => $taskId,
        ];

        // 请求链接
        $endpoint = $this->config['query_post_task_endpoint'] ?? '';
        if (empty($endpoint)) {
            throw new SocialSdkException('任务状态链接不能为空');
        }

        // 发起请求
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $endpoint, [
            'query' => $params,
            'timeout' => 30,
        ]);
        $resBody = "";
        try {
            $resBody = $res->getBody()->getContents();
        } catch (\Exception $ex) {
        }
        $resData = json_decode($resBody, true);
        $hasError = $res->getStatusCode() != 200 || empty($resData) || !is_array($resData) || ($resData['status'] ?? 0) != 200;

        // 写日志
        $logStr = "task_id: {$taskId}, \n请求url：{$endpoint}\n请求参数：\n" . var_export($params, true) . "\n响应状态码：{$res->getStatusCode()}，响应结果：\n{$resBody}";
        $this->writeLog($hasError ? 'error' : 'info', $logStr, 'query_task_info');

        // 分析结果
        if ($hasError) {
            throw new SocialSdkException("调用模拟登录发布任务查询接口发生异常：$resBody");
        }

        // 获取任务信息
        $taskInfo = $resData['list'] ?? [];

        // 计算任务状态
        $taskStatus = $this->calTaskStatus2((int)($taskInfo['status'] ?? 0));

        // 构造数据
        $task = new SimulateVideoPostTask();
        $task->setTaskId($taskInfo['task_id'] ?? '');
        $task->setTaskStatus($taskStatus);
        $task->setMsg((string)($taskInfo['msg'] ?? ''));
        $task->setInfo((string)($taskInfo['info'] ?? ''));
        $task->setPostUrl($taskInfo['video_ytb_url'] ?? '');
        $task->setCallbackUrl((string)($taskInfo['callback_url'] ?? ''));
        $task->setTitle((string)($taskInfo['title'] ?? ''));
        $task->setDescription((string)($taskInfo['descs'] ?? ''));
        $task->setAccount((string)($taskInfo['user'] ?? ''));
        $task->setOriginVideoUrl((string)($taskInfo['uploadpath'] ?? ''));
        return $task;
    }

    /**
     * 获取社媒发布官方账号列表
     * @return SimulateAccount[]
     * @throws SocialSdkException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAccountList(): array
    {
        // 请求链接
        $endpoint = $this->config['get_account_list_endpoint'] ?? '';
        if (empty($endpoint)) {
            throw new SocialSdkException('任务状态链接不能为空');
        }

        // 调用接口
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $endpoint, [
            'timeout' => 30,
        ]);
        $resBody = "";
        try {
            $resBody = $res->getBody()->getContents();
        } catch (\Exception $ex) {
        }
        $resData = json_decode($resBody, true);
        $hasError = $res->getStatusCode() != 200 || empty($resData) || !is_array($resData) || ($resData['status'] ?? 0) != 200;

        // 写日志
        $logStr = "请求url：{$endpoint}\n响应状态码：{$res->getStatusCode()}，响应结果：\n{$resBody}";
        $this->writeLog($hasError ? 'error' : 'info', $logStr, 'get_account_list');

        // 校验响应数据
        if ($hasError) {
            throw new SocialSdkException('调用社媒分享官方账号接口发生异常：' . $resBody);
        }

        // 构造数据
        $list = $resData['list'] ?? [];
        $newList = [];
        foreach ($list as $account) {
            $obj = new SimulateAccount();
            $obj->setUser((string)($account['user'] ?? ''));
            $obj->setMedia((string)($account['media'] ?? ''));
            $obj->setChannelUrl((string)($account['channel_url'] ?? ''));
            $newList[] = $obj;
        }
        return $newList;
    }

    /**
     * 计算任务状态
     * @param int $status
     * @return int
     */
    private function calTaskStatus(int $status): int
    {
        if ($status == 200) {
            $taskStatus = SimulatePostTask::TASK_STATUS_SUCCESS;
        } elseif ($status == 204) {
            $taskStatus = SimulatePostTask::TASK_STATUS_ALREADY_POST;
        } elseif ($status == 202) {
            $taskStatus = SimulatePostTask::TASK_STATUS_PLATFORM_REVIEWING;
        } else {
            $taskStatus = SimulatePostTask::TASK_STATUS_FAIL;
        }
        return $taskStatus;
    }

    /**
     * 计算任务状态
     * @param int $status
     * @return int
     */
    private function calTaskStatus2(int $status): int
    {
        // 计算状态
        if ($status == 1) {
            $taskStatus = SimulatePostTask::TASK_STATUS_SUCCESS;
        } elseif ($status == 3) {
            $taskStatus = SimulatePostTask::TASK_STATUS_FAIL;
            // } elseif ($status == 0 && strlen((string)($taskInfo['msg'] ?? '')) > 0) {
            //     $taskStatus = SimulatePostTask::TASK_STATUS_FAIL;
        } elseif ($status == 2) {
            $taskStatus = SimulatePostTask::TASK_STATUS_PLATFORM_REVIEWING;
        } else {
            $taskStatus = SimulatePostTask::TASK_STATUS_UNKNOWN;
        }
        return $taskStatus;
    }

    /**
     * 写日志
     * @param string $level
     * @param string $content
     * @param string $type
     */
    public function writeLog(string $level, string $content, string $type): void
    {
        $this->logger->writeLog($level, $content, "simulate/{$type}");
    }

}
