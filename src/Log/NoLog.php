<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Log;


use Jcsp\SocialSdk\Contract\LoggerInterface;

class NoLog implements LoggerInterface
{
    /**
     * @inheritdoc
     */
    public function writeLog(string $level, string $content, string $type): void
    {
    }

}