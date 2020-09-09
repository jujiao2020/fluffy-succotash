<?php declare(strict_types=1);


namespace Jcsp\SocialSdk\Client;


use Jcsp\SocialSdk\Contract\ShareInterface;
use Jcsp\SocialSdk\Exception\SocialSdkException;
use Jcsp\SocialSdk\Model\AccessToken;
use Jcsp\SocialSdk\Model\AuthConfig;
use Jcsp\SocialSdk\Model\Channel;
use Jcsp\SocialSdk\Model\UserProfile;
use Jcsp\SocialSdk\Model\VideoShareParams;
use Jcsp\SocialSdk\Model\VideoShareResult;

class VK extends OAuth2 implements ShareInterface
{

    /**
     * @var \VK\Client\VKApiClient
     */
    private $lib;

    /**
     * @var \VK\OAuth\VKOAuth
     */
    private $oauthLib;

    /**
     * 默认权限
     * @var array
     */
    private $defaultScope = [
        \VK\OAuth\Scopes\VKOAuthUserScope::DOCS,
        \VK\OAuth\Scopes\VKOAuthUserScope::FRIENDS,
        \VK\OAuth\Scopes\VKOAuthUserScope::PHOTOS,
        \VK\OAuth\Scopes\VKOAuthUserScope::WALL,
        \VK\OAuth\Scopes\VKOAuthUserScope::GROUPS,
        \VK\OAuth\Scopes\VKOAuthUserScope::VIDEO,
        \VK\OAuth\Scopes\VKOAuthUserScope::OFFLINE, // 加了这个，access token 不过期
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
        $this->oauthLib = new \VK\OAuth\VKOAuth();
        $this->lib = new \VK\Client\VKApiClient();
        $config->setScope($config->getScope() ?: $this->defaultScope);
        $this->authMode = self::AUTH_MODE_IMPLICIT;
    }

    /**
     * 生成授权链接
     * @return string
     */
    public function generateAuthUrlByClient(): string
    {
        // 校验值
        $state = uniqid();

        // 生成授权链接
        $authUrl = $this->oauthLib->getAuthorizeUrl(
            \VK\OAuth\VKOAuthResponseType::TOKEN,
            (int)$this->authConfig->getClientId(),
            $this->authConfig->getRedirectUrl(),
            \VK\OAuth\VKOAuthDisplay::PAGE,
            $this->authConfig->getScope(),
            $state,
            null,
            true
        );

        // 返回授权链接
        return $authUrl;
    }

    /**
     * 获取 AccessToken
     * @param string $code
     * @param string $state
     * @return AccessToken
     * @throws \VK\Exceptions\VKClientException
     * @throws \VK\Exceptions\VKOAuthException
     */
    public function getAccessTokenInAuthorizationCodeModeByClient(string $code, string $state): AccessToken
    {
        // 获取 Access Token
        $response = $this->oauthLib->getAccessToken(
            (int)$this->authConfig->getClientId(),
            $this->authConfig->getClientSecret(),
            $this->authConfig->getRedirectUrl(),
            $code
        );

        // 日志记录
        $this->writeLog("info", "code：" . var_export($code, true) . "\n响应结果" . var_export($response, true));

        // 构造数据
        $accessToken = new AccessToken();
        $accessToken->setToken((string)($response['access_token'] ?? ''));
        $accessToken->setExpireTime(0);
        $accessToken->setRefreshToken('');
        $accessToken->setScope([]);
        $accessToken->setParams($response);
        $accessToken->setUserId((string)($response['user_id'] ?? ''));
        $this->accessToken = $accessToken;

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
        // 有了 offline 权限， access token 不过期
        throw new SocialSdkException("No need to refresh token for VK.");
    }

    /**
     * 获取授权用户信息
     * @return UserProfile
     * @throws \VK\Exceptions\VKApiException
     * @throws \VK\Exceptions\VKClientException
     */
    public function getUserProfile(): UserProfile
    {
        // 支持的字段：https://vk.com/dev/fields
        $params = [
            'user_ids' => [$this->accessToken->getUserId()],
            'fields' => ['uid', 'first_name', 'last_name', 'deactivated', 'verified', 'blacklisted', 'sex', 'bdate', 'city',
                'photo', 'home_town', 'photo_max', 'online', 'contacts', 'site', 'status', 'nickname', 'about', 'can_post', 'screen_name', 'timezone'],
        ];
        $response = $this->lib->users()->get($this->accessToken->getToken(), $params);

        // 日志记录
        $this->writeLog("info", "users,get: \n请求参数：" . var_export($params, true) . "\n响应结果" . var_export($response, true));

        // array(1) {
        //   [0]=>
        //   array(21) {
        //     ["id"]=>
        //     int(593604306)
        //     ["first_name"]=>
        //     string(6) "Jennie"
        //     ["last_name"]=>
        //     string(4) "Chen"
        //     ["is_closed"]=>
        //     bool(false)
        //     ["can_access_closed"]=>
        //     bool(true)
        //     ["sex"]=>
        //     int(1)
        //     ["nickname"]=>
        //     string(0) ""
        //     ["screen_name"]=>
        //     string(11) "id593604306"
        //     ["bdate"]=>
        //     string(9) "25.4.1995"
        //     ["timezone"]=>
        //     int(8)
        //     ["photo"]=>
        //     string(41) "https://vk.com/images/camera_50.png?ava=1"
        //     ["photo_max"]=>
        //     string(42) "https://vk.com/images/camera_200.png?ava=1"
        //     ["online"]=>
        //     int(0)
        //     ["can_post"]=>
        //     int(1)
        //     ["home_phone"]=>
        //     string(0) ""
        //     ["site"]=>
        //     string(0) ""
        //     ["status"]=>
        //     string(0) ""
        //     ["verified"]=>
        //     int(0)
        //     ["blacklisted"]=>
        //     int(0)
        //     ["home_town"]=>
        //     string(0) ""
        //     ["about"]=>
        //     string(0) ""
        //   }
        // }

        // 性别
        $sex = UserProfile::SEX_UNKNOWN;
        if ($response[0]['sex'] == 1) {
            $sex = UserProfile::SEX_FEMALE;
        } elseif ($response[0]['sex'] == 2) {
            $sex = UserProfile::SEX_MALE;
        }

        // 构造数据
        $userProfile = new UserProfile();
        $userProfile->setId((string)($response[0]['id'] ?? ''));
        $userProfile->setSex($sex);
        $userProfile->setPictureUrl((string)($response[0]['photo'] ?? ''));
        $userProfile->setFullName((string)($response[0]['first_name'] . ' ' . $response[0]['last_name'] ?? ''));
        $userProfile->setEmail("");
        $userProfile->setBirthday(0);
        $userProfile->setParams(array($response[0] ?? []));

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
        return true;
    }

    /**
     * 获取要分享到的频道列表
     * @return array
     * @throws \VK\Exceptions\Api\VKApiAccessGroupsException
     * @throws \VK\Exceptions\VKApiException
     * @throws \VK\Exceptions\VKClientException
     */
    public function getShareChannelList(): array
    {
        // 获取分组
        $params = [
            'user_id' => $this->accessToken->getUserId(),
            'extended' => 1, // 1 — to return complete information about a user's communities 0 — to return a list of community IDs without any additional fields (default)
        ];
        $response = $this->lib->groups()->get($this->accessToken->getToken(), $params);

        // 日志记录
        $this->writeLog("info", "groups.get: \n请求参数：" . var_export($params, true) . "\n响应结果" . var_export($response, true));

        // 组装数据
        $channelList = [];
        $items = $response['items'] ?? [];
        foreach ($items as $item) {
            if (($item['is_admin'] ?? 0) == 1 && ($item['is_closed'] ?? 0) == 0) {
                $channel = new Channel();
                $channel->setId((string)($item['id'] ?? ''));
                $channel->setUserId((string)($item['user_id'] ?? ''));
                $channel->setName((string)($item['name'] ?? ''));
                $channel->setImgUrl((string)($item['photo_50'] ?? ''));
                $channel->setUrl("");
                $channel->setParams($item);
                $channelList[] = $channel;
            }
        }

        return $channelList;
    }

    /**
     * 视频分享
     * @param VideoShareParams $params
     * @return VideoShareResult
     * @throws SocialSdkException
     * @throws \VK\Exceptions\Api\VKApiAccessVideoException
     * @throws \VK\Exceptions\Api\VKApiWallAddPostException
     * @throws \VK\Exceptions\Api\VKApiWallAdsPublishedException
     * @throws \VK\Exceptions\VKApiException
     * @throws \VK\Exceptions\VKClientException
     */
    public function shareVideo(VideoShareParams $params): VideoShareResult
    {
        // 下载视频到本地
        $localFilePath = $this->downloadFile($params->getVideoUrl());

        // 获取上传服务器路径
        $data = [
            'name' => $params->getTitle(),
            'description' => $params->getDescription(),
            'wallpost' => 1, // 1 — to post the saved video on a user's wall
            // 'link' => $link, // URL for embedding the video from an external website.
            // 'group_id' => $groupId,
            // 'album_id' =>,
            // 'no_comments' => 0,
            // 'repeat' => 0, // 1 — to repeat the playback of the video
            // 'compression' => 0,
        ];
        if ($params->getIsPostToChannel()) {
            $data['group_id'] = $params->getSocialId();
        }
        $address = $this->lib->video()->save($this->accessToken->getToken(), $data);

        // 日志记录
        $this->writeLog("info", "video.save: \n请求参数：" . var_export($data, true) . "\n响应结果" . var_export($address, true));

        // $address：
        // array(4) {
        //   ["size"]=>
        //   int(287405)
        //   ["owner_id"]=>
        //   int(593604306)
        //   ["video_id"]=>
        //   int(456239019)
        //   ["video_hash"]=>
        //   string(18) "4819db41c1f90afa50"
        // }

        // 上传本地文件
        $video = $this->lib->getRequest()->upload($address['upload_url'], 'video_file', $localFilePath);

        // 日志记录
        $this->writeLog("info", "upload: \n请求参数：" . var_export([$address['upload_url'], $localFilePath], true) . "\n响应结果" . var_export($address, true));

        // $video：
        // array(6) {
        //   ["access_key"]=>
        //   string(18) "a82f36662df9c98245"
        //   ["description"]=>
        //   string(22) "Company Intro for desc"
        //   ["owner_id"]=>
        //   int(593604306)
        //   ["title"]=>
        //   string(13) "Company Intro"
        //   ["upload_url"]=>
        //   string(191) "https://vu.vk.com/c839215/upload.exe?act=add_video&mid=593604306&oid=593604306&vid=456239019&fid=0&tag=aa51937b&hash=468bac946ecdef5b263d&swfupload=1&api=1&hash_extra=eyJhcGlfaWQiOjc0MjI5MDN9"
        //   ["video_id"]=>
        //   int(456239019)
        // }
        //
        // array(1) {
        //   ["error"]=>
        //   array(3) {
        //     ["error_code"]=>
        //     int(15)
        //     ["error_msg"]=>
        //     string(44) "Access denied: no access to call this method"
        //     ["request_params"]=>
        //     array(10) {
        //         ...
        //     }
        // }

        // 删除临时文件
        unlink($localFilePath);

        // 返回错误就抛出
        if (isset($video['error'])) {
            throw new SocialSdkException($video['error']['error_code'] . ":" . $video['error']['error_msg']);
        }

        // 拼凑视频路径
        $ownerId = $video['owner_id'] ?? '';
        $videoId = $video['video_id'] ?? '';
        $postUrl = "https://vk.com/video{$ownerId}_{$videoId}";

        // 构造数据
        $result = new VideoShareResult();
        $result->setId((string)($video['video_id'] ?? ''));
        $result->setTitle((string)($video['title'] ?? ''));
        $result->setDescription((string)($video['description'] ?? ''));
        $result->setThumbnailUrl('');
        $result->setUrl($postUrl);
        $result->setCreatedTime(time());
        return $result;
    }

}