<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Client;


use Jcsp\SocialSdk\Contract\ShareInterface;
use Jcsp\SocialSdk\Exception\ShareException;
use Jcsp\SocialSdk\Exception\SocialSdkException;
use Jcsp\SocialSdk\Model\AccessToken;
use Jcsp\SocialSdk\Model\AuthConfig;
use Jcsp\SocialSdk\Model\Channel;
use Jcsp\SocialSdk\Model\UserProfile;
use Jcsp\SocialSdk\Model\VideoShareParams;
use Jcsp\SocialSdk\Model\VideoShareResult;

class Vimeo extends OAuth2 implements ShareInterface
{
    /**
     * @var \Vimeo\Vimeo
     */
    private $lib;

    /**
     * 默认权限
     * @var array
     */
    private $defaultScope = [
        'public',
        'private',
        'purchased',
        'create',
        'edit',
        'delete',
        'interact',
        'upload',
        'promo_codes',
        'video_files',
    ];

    /**
     * 初始化
     */
    public function init(): void
    {
    }

    /**
     * 初始化授权客户端
     * @param AuthConfig $config
     * @param AccessToken|null $token
     */
    protected function initAuthClient(AuthConfig $config, ?AccessToken $token = null): void
    {
        $this->lib = new \Vimeo\Vimeo($config->getClientId(), $config->getClientSecret());
        if (!is_null($token)) {
            $this->lib->setToken($token->getToken());
        }
        $config->setScope($config->getScope() ?: $this->defaultScope);
    }

    /**
     * 生成授权链接
     * @return string
     */
    public function generateAuthUrlByClient(): string
    {
        // 校验值
        // $state = base64_encode(openssl_random_pseudo_bytes(30));
        $state = uniqid();

        // 生成授权链接
        $authUrl = $this->lib->buildAuthorizationEndpoint($this->authConfig->getRedirectUrl(), $this->authConfig->getScope(), $state);

        // 返回授权链接
        return $authUrl;
    }

    /**
     * 获取 AccessToken
     * @param string $code
     * @param string $state
     * @return AccessToken
     * @throws SocialSdkException
     */
    public function getAccessTokenInAuthorizationCodeModeByClient(string $code, string $state): AccessToken
    {
        // 获取 AccessToken
        $response = $this->lib->accessToken($code, $this->authConfig->getRedirectUrl());

        // 如果有错误就报错
        if (isset($response['body']['error'])) {
            throw new SocialSdkException($response['body']['error_description'] ?? $response['body']['error']);
        }

        // 日志记录
        $this->writeLog("info", "code：{$code}\n响应结果：\n" . var_export($response, true));

        // 如果有错误就报错
        if (isset($response['body']['error'])) {
            throw new SocialSdkException($response['body']['error_description'] ?? $response['body']['error']);
        }

        // 分析 user id
        $userId = substr(strrchr($response['body']['user']['uri'], "/"), 1);

        // 构造数据
        $accessToken = new AccessToken();
        $accessToken->setToken((string)($response['body']['access_token'] ?? ''));
        $accessToken->setExpireTime(0); // access token 不过期
        $accessToken->setRefreshToken(''); // access token 不过期
        $accessToken->setScope(explode(' ', $response['body']['scope']));
        $accessToken->setParams($response['body'] ?? []);
        $accessToken->setUserId($userId);
        $this->lib->setToken($accessToken->getToken());

        return $accessToken;
    }

    /**
     * AccessToken 是否已经过期
     * @return bool
     */
    public function isAccessTokenExpired(): bool
    {
        return false;
    }

    /**
     * 是否能够 RefreshToken
     * @return bool
     */
    public function allowRefreshToken(): bool
    {
        return false;
    }

    /**
     * 刷新 AccessToken
     * @param string $refreshToken
     * @return AccessToken
     * @throws SocialSdkException
     */
    public function refreshAccessTokenByClient(string $refreshToken): AccessToken
    {
        throw new SocialSdkException("No need to refresh token for vimeo.");
    }

    /**
     * 获取授权用户信息
     * @return UserProfile
     * @throws SocialSdkException
     * @throws \Vimeo\Exceptions\VimeoRequestException
     */
    public function getUserProfile(): UserProfile
    {
        // 获取用户信息
        $response = $this->lib->request('/me');

        // 日志记录
        $this->writeLog("info", "获取用户信息成功:\n" . var_export($response, true));

        // 如果有错误就报错
        if (isset($response['body']['error'])) {
            throw new SocialSdkException($response['body']['error_description'] ?? $response['body']['error']);
        }

        // 用户id
        $userId = substr(strrchr($response['body']['uri'], "/"), 1);

        // 性别
        // $sex = $response['body']['gender'] ?? '';
        $sex = UserProfile::SEX_UNKNOWN;

        // 构造数据
        $userProfile = new UserProfile();
        $userProfile->setId($userId);
        $userProfile->setSex($sex);
        $userProfile->setPictureUrl((string)($response['body']['pictures']['sizes'][0]['link'] ?? ''));
        $userProfile->setFullName((string)($response['body']['name'] ?: ''));
        $userProfile->setEmail('');
        $userProfile->setBirthday(0);
        $userProfile->setLink((string)($response['body']['link'] ?? ''));
        $userProfile->setParams((array)($response['body'] ?? []));

        return $userProfile;
    }

    /**
     * 是否能够分享到用户
     * @return bool
     */
    public function canShareToUser(): bool
    {
        return true;
    }

    /**
     * 是否能够分享到频道
     * @return bool
     */
    public function canShareToChannel(): bool
    {
        return false;
    }

    /**
     * 获取要分享到的频道列表
     * 这个“频道”在不同平台有不同的说法，如：channel, page, board, blog, folder 等等的说法。
     * 如果某些平台无需分享到频道，返回空数组
     * @return Channel[]
     */
    public function getShareChannelList(): array
    {
        return [];
    }

    /**
     * 视频分享
     * @param VideoShareParams $params
     * @return VideoShareResult
     * @throws ShareException
     * @throws \Vimeo\Exceptions\VimeoRequestException
     */
    public function shareVideo(VideoShareParams $params): VideoShareResult
    {
        // 发布视频
        $response = $this->lib->request(
            '/me/videos',
            [
                'upload' => [
                    'approach' => 'pull',
                    'link' => $params->getVideoUrl(),
                ],
                'name' => $params->getTitle(),
                'description' => $params->getDescription(),
            ],
            'POST'
        );

        // Error:
        // array (
        //   'body' =>
        //   array (
        //     'error' => 'Something strange occurred. Please contact the app owners.',
        //     'link' => NULL,
        //     'developer_message' => 'No user credentials were provided.',
        //     'error_code' => 8003,
        //   ),
        //   'status' => 401,
        //   'headers' =>
        //   array (
        //     'Connection' => 'keep-alive',
        //     'Content-Length' => '157',
        //     'Server' => 'nginx',
        //     'Content-Type' => 'application/vnd.vimeo.error+json',
        //     'Cache-Control' => 'private, no-store, no-cache',
        //     'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
        //     'WWW-Authenticate' => 'Bearer error="invalid_token"',
        //     'Request-Hash' => '529b099f',
        //     'X-BApp-Server' => 'api-v7265-4vgjh',
        //     'X-Vimeo-DC' => 'ge',
        //     'Accept-Ranges' => 'bytes',
        //     'Via' => '1.1 varnish, 1.1 varnish',
        //     'Date' => 'Wed, 20 Jan 2021 00:53:56 GMT',
        //     'X-Served-By' => 'cache-bwi5124-BWI, cache-hkg17932-HKG',
        //     'X-Cache' => 'MISS, MISS',
        //     'X-Cache-Hits' => '0, 0',
        //     'X-Timer' => 'S1611104037.569698,VS0,VE238',
        //     'Vary' => 'Accept,Vimeo-Client-Id',
        //   ),
        // )

        // 分析结果
        $postUrl = $response['body']['link'] ?? '';
        if (empty($response) || $response['status'] != 201 || empty($postUrl)) {
            $status = $response['status'] ?? 0;
            $errorCode = $response['body']['error_code'] ?? '';
            $isUnrecognizedAccessToken = $status == 401 && $errorCode == 8003;
            $errMsg = $response['body']['error'] ?? '发布失败';
            $devMsg = json_encode($response, JSON_UNESCAPED_UNICODE) ?: '';
            throw (new ShareException($errMsg, $status))->setDevMsg($devMsg)->setUnauthorized($isUnrecognizedAccessToken);
        }

        // 日志记录
        $this->writeLog("info", "分享视频成功:\n" . var_export($response, true));

        // 视频 id
        $videoId = (string)(substr(strrchr($response['body']['uri'], "/"), 1));

        // 处理缩略图
        if (!empty($params->getThumbnailUrl())) {
            $tryTime = 0;
            while ($tryTime < 4) {
                sleep(30);
                $tryTime++;
                $this->setThumbnail($videoId, $params->getThumbnailUrl());
                if (!empty(trim($postUrl))) {
                    break;
                }
            }
        }

        // 构造数据
        $result = new VideoShareResult();
        $result->setId($videoId);
        $result->setTitle((string)($response['body']['name'] ?? ''));
        $result->setDescription((string)($response['body']['description'] ?? ''));
        $result->setThumbnailUrl('');
        $result->setUrl($postUrl);
        $result->setCreatedTime(time());
        return $result;
    }

    /**
     * 异步获取视频分享链接
     * @param VideoShareParams $params
     * @param VideoShareResult $result
     * @return string
     */
    public function asyncToGetUrl(VideoShareParams $params, VideoShareResult $result): string
    {
        return "";
    }

    /**
     * 设置视频缩略图
     * @param string $videoId
     * @param string $thumbnailUrl
     */
    public function setThumbnail(string $videoId, string $thumbnailUrl): void
    {
        // https://developer.vimeo.com/api/upload/thumbnails#uploading-a-thumbnail
        // For best results, we recommend that you send us a common web format like JPEG, PNG, or GIF instead of something fancy.

        // 上传完视频后，不能够立即设置视频缩略图，会报错：{"Status":"failure","Notes":"500 Internal Server Error","Path":"/video/xxxxxxxx"}

        $localPath = '';
        try {
            $localPath = $this->downloadFile($thumbnailUrl);

            // 1. Get the URI of the thumbnail
            $response = $this->lib->request("/videos/{$videoId}", [], 'GET');
            $pictureUrl = $response['body']['metadata']['connections']['pictures']['uri'] ?? '';
            if (empty($pictureUrl)) {
                throw new \Exception("缺少缩略图上传路径, Response: " . json_encode($response, JSON_UNESCAPED_UNICODE));
            }
            $this->writeLog("info", "获取 pictures_uri: video_id: {$videoId}, pictures_uri: {$pictureUrl}");

            // 2. Get the upload link for the thumbnail
            // 3. Upload the thumbnail image file
            // 4. Set the thumbnail as active
            $link = $this->lib->uploadImage($pictureUrl, $localPath, true);

            // 写日志
            $this->writeLog("info", "缩略图上传成功: video_id: {$videoId}, thumbnailUrl: {$thumbnailUrl}, pictures_uri: {$pictureUrl}, upload_link: {$link}");
        } catch (\Exception $ex) {
            // 写日志
            $this->writeLog("error", "缩略图上传失败: video_id: {$videoId}, thumbnailUrl: {$thumbnailUrl}, Error: " . $ex->getMessage());
        } finally {
            if (!empty($localPath)) {
                @unlink($localPath);
            }
        }
    }

}