<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Simulate;


use Jcsp\SocialSdk\Contract\LoggerInterface;
use Jcsp\SocialSdk\Contract\SimulateInterface;
use Jcsp\SocialSdk\Exception\SocialSdkException;
use Jcsp\SocialSdk\Model\CommonResult;
use Jcsp\SocialSdk\Model\SimulateBindAccountResult;
use Jcsp\SocialSdk\Model\SimulateChannel;
use Jcsp\SocialSdk\Model\SimulatePostTask;
use Jcsp\SocialSdk\Model\SimulateAccountBindVerificationParams;
use Jcsp\SocialSdk\Model\SimulateAccountBindParams;
use Jcsp\SocialSdk\Model\SimulateAccountBindInfo;
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
     * 是否测试
     * @var bool
     */
    protected $isTest = false;

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
            'keywords' => $params->getKeywords(),
            'desc' => $params->getDescription(), // 视频描述，5000字以内
            'thumbnail' => $params->getThumbnailUrl(), // 缩略图url
            'callback' => $params->getCallbackUrl(),
            'media' => strtolower($params->getSocialMediaName()),
            'user' => $params->getAccount(),
            'video_website' => $params->getVideoWebSiteUrl(), // 视频官网 url
        ];

        // 分享到 channel 要传 social_id，分享到 user 不能传 channel
        if ($params->getIsShareToChannel()) {
            $requestParams['social_id'] = $params->getSocialId();
        }

        // 区分大v分享和个人账号分享，个人分享需要传这个字段
        if ($params->getAccountType() > 0) {
            $requestParams['account_type'] = $params->getAccountType();
        }

        // 请求链接
        $endpoint = $this->config['post_video_endpoint'] ?? '';
        if (empty($endpoint)) {
            throw new SocialSdkException('发布链接不能为空');
        }

        // 发起请求
        $this->warpTestParams($requestParams);
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
        $this->writeLog($hasError ? 'error' : 'info', $logStr);

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
        $this->writeLog($taskStatus == SimulatePostTask::TASK_STATUS_SUCCESS ? 'info' : 'warn', $logStr);

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
        $this->writeLog($hasError ? 'error' : 'info', $logStr);

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
        $this->writeLog($hasError ? 'error' : 'info', $logStr);

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
     * 绑定账号
     * @param SimulateAccountBindParams $params
     * @return SimulateBindAccountResult
     * @throws SocialSdkException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function bindAccount(SimulateAccountBindParams $params): SimulateBindAccountResult
    {
        // 校验
        if (strlen($params->getSocialMediaName()) == 0) {
            throw new SocialSdkException("社媒名称不能为空");
        }
        if (strlen($params->getUserId()) == 0) {
            throw new SocialSdkException("用户id不能为空");
        }
        if (strlen($params->getAccount()) == 0) {
            throw new SocialSdkException("用户账号不能为空");
        }
        if (strlen($params->getPwd()) == 0 && strlen($params->getTaskId()) == 0) {
            throw new SocialSdkException("用户密码不能为空");
        }
        if (strlen($params->getCallbackUrl()) == 0) {
            throw new SocialSdkException("缺少回调路径");
        }

        // 构造参数
        $requestParams = [
            'user_id' => $params->getUserId(),
            'user' => $params->getAccount(),
            'media' => strtolower($params->getSocialMediaName()),
            'callback' => $params->getCallbackUrl(),
        ];
        if (strlen($params->getTaskId()) == 0) { // pwd 和 task_id 只能传一个
            $requestParams['pwd'] = $params->getPwd();
        } else {
            $requestParams['task_id'] = $params->getTaskId();
        }
        if (strlen($params->getPhone()) > 0) {
            $requestParams['phone'] = $params->getPhone();
        }

        // 请求链接
        $endpoint = $this->config['bind_account_endpoint'] ?? '';
        if (empty($endpoint)) {
            throw new SocialSdkException('账号绑定链接不能为空');
        }

        // 发起请求
        $this->warpTestParams($requestParams);
        $client = new \GuzzleHttp\Client();
        $res = $client->request('POST', $endpoint, [
            'form_params' => $requestParams,
            'timeout' => 30,
        ]);

        // 处理响应，响应格式：
        // {"status": 200, "msg": "成功加入授权账号队列", "task_id": "xxxxxxxx"}
        $resBody = "";
        try {
            $resBody = $res->getBody()->getContents();
        } catch (\Exception $ex) {
        }
        $resData = json_decode($resBody, true);
        $hasError = $res->getStatusCode() != 200 || empty($resData) || !is_array($resData) || $resData['status'] >= 400 || !isset($resData['task_id']);

        // 写日志
        if (isset($requestParams['pwd'])) {
            $requestParams['pwd'] = '******';
        }
        $logStr = "请求url：{$endpoint}\n请求参数：\n" . var_export($requestParams, true) . "\n响应状态码：{$res->getStatusCode()}，响应结果：\n{$resBody}";
        $this->writeLog($hasError ? 'error' : 'info', $logStr);

        // 分析结果
        if ($hasError) {
            throw new SocialSdkException("调用账号绑定接口失败：$resBody");
        }

        // 计算状态值
        $status = $this->calTaskStatus3((int)($resData['status'] ?? 0));

        // 根据结果构建绑定处理信息
        $bindInfoData = $resData['data'] ?? [];
        $info = null;
        if (!empty($bindInfoData)) {
            $info = new SimulateAccountBindInfo();
            $info->setTaskId((string)($resData['task_id'] ?? ''));
            $info->setUserId((string)($bindInfoData['user_id'] ?? ''));
            $info->setAccount((string)($bindInfoData['account'] ?? ''));
            $info->setSocialId((string)($bindInfoData['social_id'] ?? ''));
            $info->setMsg((string)($bindInfoData['msg'] ?? ''));
            $info->setStatus($status);
            $info->setVerifyType((int)($bindInfoData['verify_type'] ?? ''));
            $info->setVerifyTips((string)($bindInfoData['verify_tips'] ?? ''));
            $info->setDisplayName((string)($bindInfoData['display_name'] ?? ''));
            $info->setHeadImgUrl((string)($bindInfoData['head_img_url'] ?? ''));
            $info->setPageUrl((string)($bindInfoData['page_url'] ?? ''));
        }

        // 返回结果
        $result = new SimulateBindAccountResult();
        $result->setStatus(200);
        $result->setMsg((string)($resData['msg'] ?? ''));
        $result->setTaskId((string)($resData['task_id'] ?? ''));
        $result->setSimulateAccountBindInfo($info);
        return $result;
    }

    /**
     * 计算绑定任务状态
     * @param int $status
     * @return int
     */
    private function calTaskStatus3(int $status): int
    {
        // 计算状态
        if ($status == 200) {
            $taskStatus = SimulateAccountBindInfo::BIND_STATUS_NEED_VERIFICATION;
        } elseif ($status == 202) {
            $taskStatus = SimulateAccountBindInfo::BIND_STATUS_SUCCESS;
        } else {
            $taskStatus = SimulateAccountBindInfo::BIND_STATUS_FAIL;
        }
        return $taskStatus;
    }

    /**
     * 账号绑定回调处理
     * @param array $requestParams
     * @return SimulateAccountBindInfo
     */
    public function handleBindProcessCallback(array $requestParams): SimulateAccountBindInfo
    {
        // 写日志
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $logStr = "访问路径: {$requestUri}， 请求参数：\n" . var_export($requestParams, true);
        $this->writeLog('info', $logStr);

        // 发生错误时的错误码
        // 正常：0， 账号密码错误：1， 人机验证：2，验证码错误/超时：3， 其他：4
        $errCode = (int)($requestParams['err_code'] ?? 0);
        $errCodeMap = [
            0 => SimulateAccountBindInfo::ERROR_CODE_NONE,
            1 => SimulateAccountBindInfo::ERROR_CODE_ACCOUNT_OR_PWD_INCORRECT,
            2 => SimulateAccountBindInfo::ERROR_CODE_VERIFY_EXPIRED,
            3 => SimulateAccountBindInfo::ERROR_CODE_NEED_MAN_MACHINE_VERIFICATION,
            4 => SimulateAccountBindInfo::ERROR_CODE_UNKNOWN,
        ];
        $errCode = $errCodeMap[$errCode] ?? SimulateAccountBindInfo::ERROR_CODE_UNKNOWN;

        // 处理 channel 数据s
        /** @var SimulateChannel[] $newChannelList */
        $newChannelList = [];
        $channelList = json_decode($requestParams['page_info'] ?? '', true);
        if (is_array($channelList)) {
            foreach ($channelList as $channel) {
                $newChannel = new SimulateChannel();
                $newChannel->setTaskId((string)($channel['task_id'] ?? 0));
                $newChannel->setUserId((int)($channel['user_id'] ?? 0));
                $newChannel->setAccount((string)($channel['account'] ?? ''));
                $newChannel->setSocialId((string)($channel['social_id']));
                $newChannel->setDisplayName((string)($channel['display_name'] ?? ''));
                $newChannel->setImgUrl((string)($channel['head_img_url'] ?? ''));
                $newChannel->setPageUrl((string)($channel['page_url'] ?? ''));
                $newChannelList[] = $newChannel;
            }
        }

        // 构造数据
        $obj = new SimulateAccountBindInfo();
        $obj->setTaskId((string)($requestParams['task_id'] ?? ''));
        $obj->setUserId((string)($requestParams['user_id'] ?? ''));
        $obj->setAccount((string)($requestParams['account'] ?? ''));
        $obj->setSocialId((string)($requestParams['social_id'] ?? ''));
        $obj->setMsg((string)($requestParams['msg'] ?? ''));
        $obj->setStatus((int)($requestParams['status'] ?? ''));
        $obj->setVerifyType((int)($requestParams['verify_type'] ?? ''));
        $obj->setVerifyTips((string)($requestParams['verify_tips'] ?? ''));
        $obj->setVerifyUrl((string)($requestParams['verify_url'] ?? ''));
        $obj->setDisplayName((string)($requestParams['display_name'] ?? ''));
        $obj->setHeadImgUrl((string)($requestParams['head_img_url'] ?? ''));
        $obj->setPageUrl((string)($requestParams['page_url'] ?? ''));
        $obj->setSocialMediaName(strtolower((string)($requestParams['media'] ?? '')));
        $obj->setErrCode($errCode);
        $obj->setChannelList($newChannelList);
        return $obj;
    }

    /**
     * 提交验证信息，某些社媒账号绑定需要
     * @param SimulateAccountBindVerificationParams $params
     * @return CommonResult
     * @throws SocialSdkException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function submitVerificationForAccountBinding(SimulateAccountBindVerificationParams $params): CommonResult
    {
        // 校验
        if (strlen($params->getSocialMediaName()) == 0) {
            throw new SocialSdkException("社媒名称不能为空");
        }
        if (strlen($params->getTaskId()) == 0) {
            throw new SocialSdkException("任务id不能为空");
        }
        if (strlen($params->getVerificationString()) == 0) {
            throw new SocialSdkException("校验信息不能为空");
        }

        // 构造参数
        $requestParams = [
            'verify' => $params->getVerificationString(),
            'task_id' => $params->getTaskId(),
            'media' => strtolower($params->getSocialMediaName()),
        ];

        // 请求链接
        $endpoint = $this->config['bind_account_submit_verification_endpoint'] ?? '';
        if (empty($endpoint)) {
            throw new SocialSdkException('用户账号绑定提交验证信息接口链接不能为空');
        }

        // 发起请求
        $this->warpTestParams($requestParams);
        $client = new \GuzzleHttp\Client();
        $res = $client->request('POST', $endpoint, [
            'form_params' => $requestParams,
            'timeout' => 30,
        ]);

        // 处理响应，响应格式：
        // {'status': 200, 'msg': '开始验证'}
        $resBody = "";
        try {
            $resBody = $res->getBody()->getContents();
        } catch (\Exception $ex) {
        }
        $resData = json_decode($resBody, true);
        $hasError = $res->getStatusCode() != 200 || empty($resData) || !is_array($resData) || $resData['status'] != 200;

        // 写日志
        $logStr = "请求url：{$endpoint}\n请求参数：\n" . var_export($requestParams, true) . "\n响应状态码：{$res->getStatusCode()}，响应结果：\n{$resBody}";
        $this->writeLog($hasError ? 'error' : 'info', $logStr);

        // 分析结果
        if ($hasError) {
            throw new SocialSdkException("调用用户账号绑定提交验证信息接口失败：$resBody");
        }

        // 返回结果
        $result = new CommonResult();
        $result->setStatus((int)($resData['status'] ?? 0));
        $result->setMsg((string)($resData['msg'] ?? ''));
        return $result;
    }

    /**
     * 解绑账号
     * @param string $taskId
     * @return CommonResult
     * @throws SocialSdkException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function unbindAccount(string $taskId): CommonResult
    {
        // 校验
        if (strlen($taskId) == 0) {
            throw new SocialSdkException("任务id不能为空");
        }

        // 构造参数
        $requestParams = [
            'task_id' => $taskId,
        ];

        // 请求链接
        $endpoint = $this->config['unbind_account_endpoint'] ?? '';
        if (empty($endpoint)) {
            throw new SocialSdkException('账号解绑链接不能为空');
        }

        // 发起请求
        $this->warpTestParams($requestParams);
        $client = new \GuzzleHttp\Client();
        $res = $client->request('POST', $endpoint, [
            'form_params' => $requestParams,
            'timeout' => 30,
        ]);

        // 处理响应，响应格式：
        // {'status': 200, 'msg': ''}
        $resBody = "";
        try {
            $resBody = $res->getBody()->getContents();
        } catch (\Exception $ex) {
        }
        $resData = json_decode($resBody, true);
        $hasError = $res->getStatusCode() != 200 || empty($resData) || !is_array($resData) || $resData['status'] != 200;

        // 写日志
        $logStr = "请求url：{$endpoint}\n请求参数：\n" . var_export($requestParams, true) . "\n响应状态码：{$res->getStatusCode()}，响应结果：\n{$resBody}";
        $this->writeLog($hasError ? 'error' : 'info', $logStr);

        // 分析结果
        if ($hasError) {
            throw new SocialSdkException("调用用户账号解绑接口失败：$resBody");
        }

        // 返回结果
        $result = new CommonResult();
        $result->setStatus((int)($resData['status'] ?? 0));
        $result->setMsg((string)($resData['msg'] ?? ''));
        return $result;
    }

    /**
     * 写日志
     * @param string $level
     * @param string $content
     * @param string $type
     */
    public function writeLog(string $level, string $content, string $type = ''): void
    {
        if (empty($type)) {
            $backtrace = debug_backtrace();
            $type = $backtrace[1]['function'] ?? 'zzz';
        }
        $this->logger->writeLog($level, $content, "simulate/{$type}");
    }

    /**
     * 设置是否走测试场景
     * @param bool $isTest
     */
    public function setIsTest(bool $isTest): void
    {
        $this->isTest = $isTest;
    }

    /**
     * 获取当前是否走测试场景
     * @return bool
     */
    public function getIsTest(): bool
    {
        return $this->isTest;
    }

    /**
     * 为参数增加测试标记
     * @param array $requestParams
     */
    private function warpTestParams(array &$requestParams): void
    {
        if ($this->isTest) {
            $requestParams['test'] = 1;
        }
    }

}
