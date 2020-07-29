<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Cache;


use Jcsp\SocialSdk\Contract\CacheInterface;

class Session implements CacheInterface
{

    public function __construct()
    {
        if (session_status() != PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * 设置值
     * @param string $key
     * @param $value
     * @param int $expireTime 过期秒数，-1 默认
     */
    public function set(string $key, $value, int $expireTime = -1): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * 读取值
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        return $_SESSION[$key];
    }

    /**
     * 删除值
     * @param string $key
     * @return mixed
     */
    public function delete(string $key)
    {
        $val = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);
        return $val;
    }

}