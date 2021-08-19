<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Util;


class FileUtil
{
    /**
     * 下载视频到本地
     * @param string $fileUrl
     * @param string $dirPath
     * @return string
     */
    public static function downloadFile(string $fileUrl, string $dirPath = "/tmp"): string
    {
        // 处理目录
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        // 生成文件路径
        $fileExt = strtolower(pathinfo(parse_url($fileUrl)['path'] ?? '', PATHINFO_EXTENSION));
        $fileName = time() . rand() . '.' . $fileExt;
        $filePath = $dirPath . '/' . $fileName;

        // 下载文件
        $fp = fopen($filePath, "wb");
        $ch = curl_init($fileUrl);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        fclose($fp);

        // 返回本地文件路径
        return $filePath;
    }

    /**
     * 删除文件
     * @param string $filePath
     * @return bool
     */
    public static function removeFile(string $filePath): bool
    {
        return unlink($filePath);
    }

}