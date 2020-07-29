<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Util;


class UrlUtil
{
    /**
     * 获取链接所有请求参数
     * @param string $url
     * @return array
     */
    public static function parseUrlQuery(string $url): array
    {
        $queryStr = htmlspecialchars_decode(parse_url($url)['query'] ?? '');
        $queryArr = [];
        parse_str(urldecode($queryStr), $queryArr);
        return $queryArr;
    }

    /**
     * 分析链接请求参数中的hash值
     * @param string $url
     * @return array
     */
    public static function parseUrlHash(string $url): array
    {
        $queryStr = htmlspecialchars_decode(parse_url($url)['fragment'] ?? '');
        $queryArr = [];
        parse_str(urldecode($queryStr), $queryArr);
        return $queryArr;
    }

}