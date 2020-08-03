<?php declare(strict_types=1);

return [
    // 下载文件的临时存放目录
    'temp_storage_path' => "/tmp",

    // 模拟登录发布
    'simulate' => [
        // 视频发布接口
        'post_video_endpoint' => '',
        // 视频发布任务状态查询接口
        'query_post_task_endpoint' => '',
        // 账号列表获取接口
        'get_account_list_endpoint' => '',
    ],

    // 缓存处理器（类名或对象）
    'cache' => \Jcsp\SocialSdk\Cache\Session::class,

    // 日志处理器（类名或对象）
    'logger' => \Jcsp\SocialSdk\Log\NoLog::class,

];
