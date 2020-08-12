<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Util;


use Jcsp\SocialSdk\Exception\SocialSdkException;

class FileUtil
{
    /**
     * 下载视频到本地
     * @param string $fileUrl
     * @param string $dirPath
     * @return string
     * @throws SocialSdkException
     */
    public static function downloadFile(string $fileUrl, string $dirPath = "/tmp"): string
    {
        // 处理目录
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        // 生成文件路径
        $fileExt = strtolower(pathinfo($fileUrl, PATHINFO_EXTENSION));
        $fileName = time() . rand() . '.' . $fileExt;
        $filePath = $dirPath . '/' . $fileName;

        // 下载文件到本地
        // if (extension_loaded("zlib")) {
        //     $content = file_get_contents("compress.zlib://" . $fileUrl);
        // } else {
        //     $content = file_get_contents($fileUrl);
        // }
        // file_put_contents($filePath, $content);
        self::downloadFileInChunks($fileUrl, $filePath);

        // 返回本地文件路径
        return $filePath;
    }

    /**
     * 分段下载文件
     * @param string $fileUrl
     * @param string $outputPath
     * @return int
     * @throws SocialSdkException
     */
    private static function downloadFileInChunks(string $fileUrl, string $outputPath): int
    {
        // 分块大小
        $chunkSize = 1024 * 1024 * 10;

        // 建立 socket 句柄，打开文件句柄
        $parts = parse_url($fileUrl);
        $socket = fsockopen($parts['host'], 80, $errStr, $errCode, 5);
        $file = fopen($outputPath, 'wb');

        // 校验
        if ($errCode != 0) {
            throw new SocialSdkException("下载文件失败($errCode): $errStr");
        }
        if (!$socket || !$file) {
            throw new SocialSdkException("下载文件失败");
        }

        // 下载文件
        if (!empty($parts['query'])) {
            $parts['path'] .= '?' . $parts['query'];
        }
        $request = "GET {$parts['path']} HTTP/1.1\r\n";
        $request .= "Host: {$parts['host']}\r\n";
        $request .= "User-Agent: Mozilla/5.0\r\n";
        $request .= "Keep-Alive: 115\r\n";
        $request .= "Connection: keep-alive\r\n\r\n";
        fwrite($socket, $request);

        // 读取响应头
        $headers = array();
        while (!feof($socket)) {
            $line = fgets($socket);
            if ($line == "\r\n") break;
            $headers[] = $line;
        }

        // 从响应头中获取 Content-Length
        $contentLength = 0;
        foreach ($headers as $header) {
            if (stripos($header, 'Content-Length:') === 0) {
                $contentLength = (int)str_replace('Content-Length: ', '', $header);
                break;
            }
        }

        // 读取文件内容，并且写入文件
        $cnt = 0;
        while (!feof($socket)) {
            $buf = fread($socket, $chunkSize);
            $bytes = fwrite($file, $buf);
            if (!$bytes) {
                throw new SocialSdkException("下载文件失败.");
            }
            $cnt += $bytes;
            if ($cnt >= $contentLength) { // 下载完成
                break;
            }
        }

        // 关闭句柄
        fclose($socket);
        fclose($file);
        return $cnt;
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