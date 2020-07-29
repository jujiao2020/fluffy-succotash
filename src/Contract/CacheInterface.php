<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Contract;


interface CacheInterface
{
    /**
     * 设置值
     * @param string $key
     * @param $value
     * @param int $expireTime 过期秒数，-1 默认
     */
    public function set(string $key, $value, int $expireTime = -1): void;

    /**
     * 读取值
     * @param string $key
     * @return mixed
     */
    public function get(string $key);

    /**
     * 删除值
     * @param string $key
     * @return mixed
     */
    public function delete(string $key);

}