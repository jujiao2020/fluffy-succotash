<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Contract;


interface LoggerInterface
{
    /**
     * 写日志
     * @param string $level 日志等级
     * @param string $content 内容
     * @param string $type 类型
     */
    public function writeLog(string $level, string $content, string $type): void;

}