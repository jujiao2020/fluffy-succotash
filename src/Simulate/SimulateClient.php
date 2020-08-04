<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Simulate;


use Jcsp\SocialSdk\Contract\LoggerInterface;
use Jcsp\SocialSdk\Contract\SimulateInterface;
use Jcsp\SocialSdk\Exception\SocialSdkException;
use Jcsp\SocialSdk\Model\SimPostVideoParams;
use Jcsp\SocialSdk\Model\SimPostVideoResult;
use Jcsp\SocialSdk\Model\SimPostVideoTask;
use Jcsp\SocialSdk\Model\SimulateAccount;

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
     * @param SimPostVideoParams $params
     * @return SimPostVideoResult
     * @throws SocialSdkException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function simPostVideo(SimPostVideoParams $params): SimPostVideoResult
    {
        // 校验
        if (empty($params->getSocialMediaName())) {
            throw new SocialSdkException("社媒名称不能为空");
        }
        if (strlen($params->getTitle()) == 0) {
            throw new SocialSdkException("分享标题不能为空");
        }

        // 构造参数
        $params = [
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
            'form_params' => $params,
            'timeout' => 30,
        ]);
        $resBody = "";
        try {
            $resBody = $res->getBody()->getContents();
        } catch (\Exception $ex) {
        }
        $resData = json_decode($resBody, true);
        $hasError = $res->getStatusCode() != 200 || empty($resData) || !is_array($resData) || !in_array((int)($resData['status'] ?? 0), [200, 204]);

        // 写日志
        $logStr = "请求url：{$endpoint}\n请求参数：\n" . var_export($params, true) . "\n响应状态码：{$res->getStatusCode()}，响应结果：\n{$resBody}";
        $this->logger->writeLog(empty($apiResult['error']) ? 'info' : 'error', $logStr, 'simulate/sim_post_video');

        // 分析结果
        if ($hasError) {
            throw new SocialSdkException("调用模拟登录发布接口失败：$resBody");
        }
        $msg = $resData['msg'] ?? "调用模拟登录发布接口失败：" . $resData['error'];

        // 构造数据
        $result = new SimPostVideoResult();
        $result->setUrl($resData['post_url'] ?? '');
        $result->setTaskId($resData['task_id'] ?? '');
        $result->setStatus((int)($resData['status'] ?? 0));
        $result->setMsg($msg);
        $result->setInfo((string)($resData['info'] ?? ''));
        return $result;
    }

    /**
     * 查询模拟登录视频发布的任务状态
     * @param string $taskId
     * @return SimPostVideoTask
     * @throws SocialSdkException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function queryTaskInfo(string $taskId): SimPostVideoTask
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
        $this->logger->writeLog($hasError ? 'error' : 'info', $logStr, 'simulate/query_task_info');

        // 分析结果
        if ($hasError) {
            throw new SocialSdkException("调用模拟登录发布任务查询接口发生异常：$resBody");
        }

        // 获取任务信息
        $taskInfo = $resData['list'] ?? [];

        // 构造数据
        $task = new SimPostVideoTask();
        $task->setStatus((int)($taskInfo['status'] ?? 0));
        $task->setMsg((string)($taskInfo['msg'] ?? ''));
        $task->setInfo((string)($taskInfo['info'] ?? ''));
        $task->setUrl((string)($taskInfo['video_ytb_url'] ?? ''));
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
        $this->logger->writeLog($hasError ? 'error' : 'info', $logStr, 'simulate/get_account_list');

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

}