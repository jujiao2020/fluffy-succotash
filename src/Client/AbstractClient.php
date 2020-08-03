<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Client;


use Jcsp\SocialSdk\Contract\CacheInterface;
use Jcsp\SocialSdk\Contract\LoggerInterface;
use Jcsp\SocialSdk\Util\FileUtil;

abstract class AbstractClient
{
    /**
     * 缓存处理器
     * @var CacheInterface
     */
    protected $cache;

    /**
     * 日志处理器
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * 临时文件存储目录
     * @var string
     */
    protected $tempStoragePath;

    /**
     * AbstractClient constructor.
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     * @param string $tempStoragePath
     */
    public function __construct(CacheInterface $cache, LoggerInterface $logger, string $tempStoragePath = "/tmp")
    {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->tempStoragePath = preg_replace("@[\\/\\\\]*$@", "", $tempStoragePath);
        $this->init();
    }

    /**
     * 初始化
     */
    abstract public function init(): void;

    /**
     * 获取社媒名称
     * @return string
     */
    public function getSocialMediaName(): string
    {
        return preg_replace("@" . str_replace("\\", "\\\\", __NAMESPACE__) . "[\\/\\\\]*@i", "", static::class);
    }

    /**
     * @return CacheInterface
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * 写日志
     * @param string $level
     * @param string $content
     * @param string $type
     */
    public function writeLog(string $level, string $content, string $type = ""): void
    {
        // $type 为空的话，使用调用者的函数名
        if (empty($type)) {
            $backtrace = debug_backtrace();
            $type = $backtrace[1]['function'] ?? 'zzz';
        }
        $this->logger->writeLog($level, $content, $this->getSocialMediaName() . "/" . $type);
    }

    /**
     * 设置临时文件的目录路径
     * @param string $tempStoragePath
     */
    public function setTempStoragePath(string $tempStoragePath): void
    {
        $this->tempStoragePath = $tempStoragePath;
    }

    /**
     * 下载文件到本地
     * @param string $videoUrl
     * @return string
     */
    protected function downloadFile(string $videoUrl): string
    {
        return FileUtil::downloadFile($videoUrl, $this->tempStoragePath . DIRECTORY_SEPARATOR . $this->getSocialMediaName());
    }

}