<?php

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


class Instagram extends OAuth2 implements ShareInterface
{

    // 使用 FACEBOOK Graph API 进行授权和视频发布

    const OAUTH_BASE_URL = 'https://www.facebook.com/v11.0/';
    const GRAPH_API_BASE_URL = 'https://graph.facebook.com/';

    /**
     * 默认权限
     * https://developers.facebook.com/docs/permissions/reference#instagram_permissions
     * @var array
     */
    private $defaultScope = [
        'public_profile',
        'instagram_basic',
        'pages_show_list',

        // post video 需要的权限
        'ads_management',
        'business_management',
        'instagram_basic',
        'instagram_content_publish',
        'pages_read_engagement',
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
        $config->setScope($config->getScope() ?: $this->defaultScope);
    }

    /**
     * 生成授权链接
     * @return string
     */
    public function generateAuthUrlByClient(): string
    {
        // 校验值
        $state = uniqid();

        // 授权权限列表字符串
        $scopeStr = implode(',', $this->authConfig->getScope());

        // 授权链接
        $authUrl = self::OAUTH_BASE_URL . "dialog/oauth?response_type=code&client_id={$this->authConfig->getClientId()}&scope={$scopeStr}&state={$state}&redirect_uri=" . urlencode($this->authConfig->getRedirectUrl());

        return $authUrl;
    }

    /**
     * 获取 AccessToken
     * @param string $code
     * @param string $state
     * @return AccessToken
     * @throws SocialSdkException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAccessTokenInAuthorizationCodeModeByClient(string $code, string $state): AccessToken
    {
        // 获取 access token
        $query = [
            'client_id' => $this->authConfig->getClientId(),
            'client_secret' => $this->authConfig->getClientSecret(),
            'redirect_uri' => $this->authConfig->getRedirectUrl(),
            'code' => $_GET["code"],
        ];
        $getAccessTokenUrl = self::GRAPH_API_BASE_URL . 'oauth/access_token?' . http_build_query($query);
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $getAccessTokenUrl);
        $resBody = $res->getBody()->getContents();
        $result = json_decode($resBody, true);
        $shortAccessToken = $result['access_token'] ?? '';

        // Exchanges a short-lived access token for a long-lived one
        // facebook 正常的 user access_token 生效期为两个小时，延长的话可以去到60天。
        // Exchanges a short-lived access token for a long-lived one
        $query = [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->authConfig->getClientId(),
            'client_secret' => $this->authConfig->getClientSecret(),
            'fb_exchange_token' => $shortAccessToken,
        ];
        $getAccessTokenUrl = self::GRAPH_API_BASE_URL . 'oauth/access_token?' . http_build_query($query);
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $getAccessTokenUrl);
        $resBody = $res->getBody()->getContents();
        $result = json_decode($resBody, true);

        // $result:
        // {"access_token":"xxxxxx","token_type":"bearer"}

        // 写日志
        $this->writeLog("info", "code：{$code}, state：{$state}\n响应结果：\n" . var_export($result, true));

        $longAccessToken = $result['access_token'] ?? '';
        if (empty($longAccessToken)) {
            throw new SocialSdkException('No access token found，result:' . json_encode($longAccessToken, JSON_UNESCAPED_UNICODE));
        }

        // 构造数据
        $accessToken = new AccessToken();
        $accessToken->setToken((string)($result['access_token'] ?? ''));
        if (!empty($data['expires_in'] ?? 0)) {
            $accessToken->setExpireTime(time() + (int)($data['expires_in'] ?? 0));
        }
        $accessToken->setRefreshToken(''); // 没给，user access token 过期要重新进行授权
        $accessToken->setScope([]);
        $accessToken->setParams($result);
        $accessToken->setUserId('');

        return $accessToken;
    }

    /**
     * AccessToken 是否已经过期
     * @return bool
     */
    public function isAccessTokenExpired(): bool
    {
        return true;
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
        // facebook 没有提供刷新 access token 的接口，要用户重新授权
        throw new SocialSdkException("Instagram does not support refresh access token.");
    }

    /**
     * 获取授权用户信息
     * @return UserProfile
     * @throws SocialSdkException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getUserProfile(): UserProfile
    {
        // 流程：
        // 1）获取用户的 fb access token
        // 2）根据 fb access token 获取用户的 fb page，找出其中带有所有 ig_user_id（instagram_business_account.id）
        // 3）根据 ig_user_id 获取用户信息（用户名，昵称，头像，用用户名拼接成个人主页链接）

        // 一个 fb 账号可以有多个公共主页，所以 n 个公共主页可以关联 n 个 ins 账号，所以可以获取到多个 ig_user_id。
        // 但这里只返回找到第一个的 ins 用户。

        // 获取用户授权时选择的 Facebook 公共主页
        $query = [
            'fields' => 'id,name,access_token,instagram_business_account,paging',
            'access_token' => $this->accessToken->getToken(),
        ];
        $endpoint = self::GRAPH_API_BASE_URL . 'me/accounts?' . http_build_query($query);
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $endpoint);
        $content = $res->getBody()->getContents();
        $result = json_decode($content, true);

        // 写日志
        $this->writeLog("info", "获取用户授权时选择的 Facebook 公共主页:\n" . var_export($result, true));

        // {
        //   "data": [
        //     {
        //       "id": "11111111",
        //       "name": "xxxxxx",
        //       "access_token": "xxx",
        //       "instagram_business_account": {
        //         "id": "17841449039477317"
        //       }
        //     }
        //   ],
        //   "paging": {
        //     "cursors": {
        //       "before": "MTAxNDkxOTc4ODU3MjY4",
        //       "after": "MTEyNTAwMDA3NTU3OTQ2"
        //     }
        //   }
        // }

        $igUserId = '';
        $list = $result['data'] ?? [];
        if (is_array($list)) {
            foreach ($list as $item) {
                if (isset($item['instagram_business_account']['id'])) {
                    $igUserId = $item['instagram_business_account']['id'];
                    break;
                }
            }
        }

        if (empty($igUserId)) {
            throw new SocialSdkException('No ins user found');
        }

        // 获取 ins 用户信息
        $query = [
            'fields' => 'id,ig_id,name,username,profile_picture_url',
            'access_token' => $this->accessToken->getToken(),
        ];
        $endpoint = self::GRAPH_API_BASE_URL . $igUserId . '?' . http_build_query($query);
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $endpoint);
        $content = $res->getBody()->getContents();
        $result2 = json_decode($content, true);

        // 写日志
        $this->writeLog("info", "获取用户信息成功:\n" . var_export($result2, true));

        // {"id":"2111","ig_id":490185,"name":"xxxx","username":"xxxxx","paging":{"cursors":{"before":"xxx","after":"xxxxx"}}},"profile_picture_url":"https:\/\/scontent-sea1-1.xx.fbcdn.net\/v\/t51.2885-15\/xxxxxx.jpg?_nc_cat=107&ccb=1-3&_nc_sid=86c713&_nc_ohc=MY_oEy4hT-0AX98fazY&_nc_ht=scontent-sea1-1.xx&edm=AL-3X8kEAAAA&oh=a669bb31fe7fcb65413dae1057e6abfe&oe=6107EBDA"}

        if (!isset($result2['id'])) {
            throw new SocialSdkException('No ins user found..');
        }

        $igUserId = $result2['id'] ?? '';
        $name = $result2['name'] ?? '';
        $username = $result2['username'] ?? '';
        $photoUrl = $result2['profile_picture_url'] ?? '';
        $pageUrl = 'https://www.instagram.com/' . $username;

        // 构造数据
        $userProfile = new UserProfile();
        $userProfile->setId((string)$igUserId);
        $userProfile->setSex(UserProfile::SEX_UNKNOWN);
        $userProfile->setPictureUrl((string)$photoUrl);
        $userProfile->setFullName((string)$name);
        $userProfile->setLink((string)$pageUrl);
        $userProfile->setParams(['fb_pages' => $result, 'ig_user' => $result2]);
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
     * @throws SocialSdkException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function shareVideo(VideoShareParams $params): VideoShareResult
    {
        // https://developers.facebook.com/docs/instagram-api/guides/content-publishing#publish-videos

        // Instagram accounts are limited to 25 API-published posts within a 24 hour moving period.
        // Publishing to IGTV is not supported.
        // If the Page connected to the targeted Instagram Business account requires Page Publishing Authorization (PPA),
        // PPA must be completed or the request will fail.

        // 需要权限：
        // ads_management
        // business_management
        // instagram_basic
        // instagram_content_publish
        // pages_read_engagement

        try {
            // 创建 IG 容器.
            $igContainerId = $this->createIgContainer($params);

            // 查询容器发布状态
            $tryTimes = 0;
            $state = false;
            while (!$state && $tryTimes < 10) {
                sleep(6);
                $tryTimes++;
                $state = $this->getPublishingStatus($igContainerId);
            }

            // 发布视频到 IG 容器
            $mediaId = $this->publishVideoToContainer($igContainerId, $params);

            // 获取视频信息
            $postUrl = '';
            $mediaInfo = [];
            $tryTimes = 0;
            while (empty($postUrl) && $tryTimes < 6) {
                sleep(5);
                $tryTimes++;
                $mediaInfo = $this->getSingleMediaDetail($mediaId, $params->getAccessToken());
                $postUrl = $mediaInfo['post_url'] ?? '';
            }
            if (empty($postUrl)) {
                throw (new ShareException("No found post url"))->setDevMsg("No found post url, info: " . var_export($mediaInfo, true));
            }

            // 构造数据
            $result = new VideoShareResult();
            $result->setId($mediaId);
            $result->setTitle($mediaInfo['caption'] ?? '');
            $result->setThumbnailUrl($mediaInfo['thumbnail_url'] ?? '');
            $result->setUrl($postUrl);
            $result->setCreatedTime(time());
            return $result;
        } catch (\GuzzleHttp\Exception\ClientException $ex) {
            throw (new ShareException('发布失败', $ex->getCode()))
                ->setDevMsg($ex->getMessage())
                ->setUnauthorized($this->isUnauthorized($ex->getResponse()->getStatusCode()));
        }
    }

    /**
     * 创建 IG 容器
     * @param VideoShareParams $params
     * @return string
     * @throws ShareException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function createIgContainer(VideoShareParams $params): string
    {
        // Use the POST /{ig-user-id}/media endpoint to create an IG Container.

        // 发送请求
        $query = [
            'media_type' => 'VIDEO',
            'video_url' => $params->getVideoUrl(),
            'caption' => $params->getTitle(),
            'access_token' => $params->getAccessToken(),
        ];
        $endpoint = self::GRAPH_API_BASE_URL . $params->getSocialId() . '/media?' . http_build_query($query);
        $client = new \GuzzleHttp\Client();
        $res = $client->request('POST', $endpoint);
        $content = $res->getBody()->getContents();

        // 校验权限
        if ($this->isUnauthorized($res->getStatusCode())) {
            throw (new ShareException('发布失败'))->setDevMsg("创建IG容器成功：" . $content)->setUnauthorized(true);
        }

        // 获取结果
        $result = json_decode($content, true);

        // 写日志
        $this->writeLog("info", "创建IG容器成功:\n" . var_export($content, true), 'shareVideo');

        // 必须要有容器id
        $igContainerId = $result['id'] ?? '';
        if (empty($igContainerId)) {
            throw new ShareException('Ig containerId not found.');
        }

        return (string)$igContainerId;
    }

    /**
     * 查询容器发布状态
     * @param string $igContainerId
     * @return bool
     * @throws ShareException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getPublishingStatus(string $igContainerId): bool
    {
        // If you are able to create a container for a video but the POST /{ig-user-id}/media_publish endpoint does not return the published media ID,
        // you can get the container's publishing status by querying the GET /{ig-container-id}?fields=status_code endpoint.
        // This endpoint will return one of the following:

        $query = [
            'fields' => 'id,status_code,status',
            'access_token' => $this->accessToken->getToken(),
        ];
        $endpoint = self::GRAPH_API_BASE_URL . $igContainerId . '?' . http_build_query($query);
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $endpoint);
        $content = $res->getBody()->getContents();
        if ($this->isUnauthorized($res->getStatusCode())) {
            throw (new ShareException('发布失败'))->setDevMsg('publishState: ' . $content)->setUnauthorized(true);
        }

        // 获取结果
        $result = json_decode($content, true);

        // 写日志
        $this->writeLog("info", "查询容器发布状态：igContainerId: {$igContainerId}, 结果：\n" . json_encode($result, JSON_UNESCAPED_UNICODE));

        // Error Code : https://developers.facebook.com/docs/instagram-api/reference/error-codes/
        // https://developers.facebook.com/docs/instagram-api/reference/ig-user/media#create-video-container
        // {"status_code":"FINISHED","id":"18165386518154241"}
        // {
        //   "status": "Error: Media upload has failed with error code 2207026",
        //   "status_code": "ERROR",
        //   "id": "17883263555396595"
        // }

        // EXPIRED — The container was not published within 24 hours and has expired.
        // ERROR — The container failed to complete the publishing process.
        // FINISHED — The container and its media object are ready to be published.
        // IN_PROGRESS — The container is still in the publishing process.
        // PUBLISHED — The container's media object has been published.

        $statusCode = $result['status_code'] ?? '';
        if ($statusCode == 'FINISHED') {
            return true;
        } elseif ($statusCode == 'ERROR' || $statusCode == 'EXPIRED') {
            throw (new ShareException('发布失败'))->setDevMsg('publishState: ' . $content);
        }
        return false;
    }

    /**
     * 发布视频到 IG 容器
     * @param string $igContainerId
     * @param VideoShareParams $params
     * @return string
     * @throws ShareException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function publishVideoToContainer(string $igContainerId, VideoShareParams $params): string
    {
        // Use the POST /{ig-user-id}/media_publish endpoint to publish the video using its container.

        // 需要权限：
        // instagram_basic
        // instagram_content_publish
        // pages_read_engagement OR pages_show_list

        $query = [
            'creation_id' => $igContainerId,
            'access_token' => $params->getAccessToken(),
        ];
        $endpoint = self::GRAPH_API_BASE_URL . $params->getSocialId() . '/media_publish?' . http_build_query($query);
        $client = new \GuzzleHttp\Client();
        $res = $client->request('POST', $endpoint);
        $content = $res->getBody()->getContents();
        if ($this->isUnauthorized($res->getStatusCode())) {
            throw (new ShareException('发布失败'))->setDevMsg($content)->setUnauthorized(true);
        }

        // 获取结果
        $result = json_decode($content, true);

        // 写日志
        $this->writeLog("info", "发布视频到 IG 容器成功:\n" . var_export($result, true), 'shareVideo');

        // result：
        // {"id":"18238604686053358"}
        // {"error":{"message":"Media ID is not available","type":"OAuthException","code":9007,"error_subcode":2207027,"is_transient":false,"error_user_title":"\u65e0\u6cd5\u53d1\u5e03","error_user_msg":"\u5a92\u4f53\u6587\u4ef6\u8fd8\u672a\u5c31\u7eea\uff0c\u65e0\u6cd5\u53d1\u5e03\uff0c\u8bf7\u7a0d\u7b49","fbtrace_id":"AxJzkjRmOWJOr-O9eJ2PljM"}}

        return $result['id'] ?? '';
    }

    /**
     * 获取单个媒体信息
     * @param string $mediaId
     * @param string $accessToken
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSingleMediaDetail(string $mediaId, string $accessToken): array
    {
        // Get the Instagram Business Account's Media Object

        // 发送请求
        $query = [
            'fields' => 'id,media_url,owner,caption,media_type,shortcode,thumbnail_url,username,timestamp',
            'access_token' => $accessToken,
        ];
        $endpoint = self::GRAPH_API_BASE_URL . $mediaId . '?' . http_build_query($query);
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $endpoint);
        $content = $res->getBody()->getContents();
        $result = json_decode($content, true);

        $this->writeLog("info", "获取单个媒体信息:\n" . var_export($result, true));

        // $result:
        // {
        //   "id": "18007485208330661",
        //   "ig_id": "2624526693038850483",
        //   "media_url": "https://xxxx",
        //   "owner": {
        //     "id": "17841437894927516"
        //   },
        //   "caption": "xxxxxxxx",
        //   "media_type": "VIDEO",
        //   "shortcode": "CRsMR86HIGz",
        //   "thumbnail_url": "https://scontent-sea1-1.cdninstagram.com/v/t51.29350-15/221477968_113549897674775_1976828313400394218_n.jpg?_nc_cat=108&ccb=1-3&_nc_sid=8ae9d6&_nc_ohc=q1fYO-rNSk0AX8zREzm&_nc_ht=scontent-sea1-1.cdninstagram.com&oh=5c23a973dcccd36aee21a6c9995cde82&oe=6106611A",
        //   "username": "video2b_com",
        //   "timestamp": "2021-07-24T01:01:02+0000"
        // }

        // 拼接帖子链接
        $result['post_url'] = '';
        $shortCode = $result['shortcode'] ?? '';
        if (!empty($shortCode)) {
            $result['post_url'] = 'https://www.instagram.com/p/' . $shortCode;
        }

        return $result;
    }

    /**
     * 判断是否是授权失效
     * @param int $code
     * @return bool
     */
    private function isUnauthorized(int $code): bool
    {
        return $code == 190;
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
    }
}