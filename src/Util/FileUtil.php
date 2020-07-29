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
            mkdir($dirPath);
        }

        // 生成文件路径
        $fileExt = strtolower(pathinfo($fileUrl, PATHINFO_EXTENSION));
        $fileName = time() . rand() . '.' . $fileExt;
        $filePath = $dirPath . '/' . $fileName;

        // 下载文件到本地
        if (extension_loaded("zlib")) {
            $content = file_get_contents("compress.zlib://" . $fileUrl);
        } else {
            $content = file_get_contents($fileUrl);
        }
        file_put_contents($filePath, $content);

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