<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Client;


use Facebook\Exceptions\FacebookResponseException;
use Jcsp\SocialSdk\Contract\ShareInterface;
use Jcsp\SocialSdk\Exception\ShareException;
use Jcsp\SocialSdk\Exception\SocialSdkException;
use Jcsp\SocialSdk\Model\AccessToken;
use Jcsp\SocialSdk\Model\AuthConfig;
use Jcsp\SocialSdk\Model\Channel;
use Jcsp\SocialSdk\Model\UserProfile;
use Jcsp\SocialSdk\Model\VideoShareParams;
use Jcsp\SocialSdk\Model\VideoShareResult;

class Facebook extends OAuth2 implements ShareInterface
{

    /**
     * @var \Facebook\Facebook
     */
    private $lib;

    /**
     * 默认权限
     * https://developers.facebook.com/docs/facebook-login/permissions/
     * @var array
     */
    private $defaultScope = [
        'publish_video',
        'publish_pages',
        'manage_pages',
        'user_friends',
        'pages_messaging',
        'groups_access_member_info',
        'publish_to_groups',
        'pages_show_list',

        // 'user_birthday',
        // 'user_gender',
        // 'email',

        'pages_read_user_content',
        'pages_manage_posts',
        'pages_read_engagement',
        'pages_manage_engagement',

        // // manage_pages 变成以下：
        // 'pages_manage_ads',
        // 'pages_manage_metadata',
        // 'pages_read_engagement',
        // 'pages_read_user_content',
        //
        // // publish_pages 变成以下：
        // 'pages_manage_posts',
        // 'pages_manage_engagement',
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
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    protected function initAuthClient(AuthConfig $config, ?AccessToken $token = null): void
    {
        $this->lib = new \Facebook\Facebook([
            'app_id' => $config->getClientId(),
            'app_secret' => $config->getClientSecret(),
            // 'default_graph_version' => 'v2.10',
        ]);
        $config->setScope($config->getScope() ?: $this->defaultScope);
    }

    /**
     * 生成授权链接
     * @return string
     */
    public function generateAuthUrlByClient(): string
    {
        // 生成授权链接
        $helper = $this->lib->getRedirectLoginHelper();
        $authUrl = $helper->getLoginUrl($this->authConfig->getRedirectUrl(), $this->authConfig->getScope());
        return $authUrl;
    }

    /**
     * 获取 AccessToken
     * @param string $code
     * @param string $state
     * @return AccessToken
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function getAccessTokenInAuthorizationCodeModeByClient(string $code, string $state): AccessToken
    {
        // 获取 access token
        $helper = $this->lib->getRedirectLoginHelper();
        $helper->getPersistentDataHandler()->set('state', $state);
        $fbAccessToken = $helper->getAccessToken();

        // The OAuth 2.0 client handler helps us manage access tokens
        $oAuth2Client = $this->lib->getOAuth2Client();

        // Get the access token metadata from /debug_token
        $tokenMetadata = $oAuth2Client->debugToken($fbAccessToken);

        // Validation (these will throw FacebookSDKException's when they fail)
        $tokenMetadata->validateAppId($this->authConfig->getClientId());

        // If you know the user ID this access token belongs to, you can validate it here
        //$tokenMetadata->validateUserId('123');
        $tokenMetadata->validateExpiration();

        // facebook 正常的 user access_token 生效期为两个小时，延长的话可以去到60天。
        // Exchanges a short-lived access token for a long-lived one
        $fbAccessToken = $oAuth2Client->getLongLivedAccessToken($fbAccessToken);

        // 写日志
        $this->writeLog("info", "code：{$code}, state：{$state}\n响应结果：\n" . var_export($fbAccessToken, true));

        // $fbAccessToken：
        // Facebook\Authentication\AccessToken::__set_state(array(
        //    'value' => 'EAAJaCpgTZCo4BAAesetMdcTZAExvPUCw2ooiviwefPDpEhqhCwZANNQYIdq4r0xIu2cVZBZBuuJcYYYkUdt9JGvFTLxPspjs1XN0vAkrT1JKt4Ght92E44Bm2rLmfSZCcnmQT8kzt0gSCSZBK7zlzjfBkpyu3nNK8jEGo872RXPVmcZBM6zN8ZB85',
        //    'expiresAt' =>
        //   DateTime::__set_state(array(
        //      'date' => '2020-07-10 02:35:27.000000',
        //      'timezone_type' => 3,
        //      'timezone' => 'UTC',
        //   )),
        // ))

        $fbAccessTokenParams = [
            'value' => $fbAccessToken->getValue(),
            'expiresAt' => $fbAccessToken->getExpiresAt(),
        ];

        // 构造数据
        $accessToken = new AccessToken();
        $accessToken->setToken((string)($fbAccessToken->getValue() ?? ''));
        $expireAt = $fbAccessToken->getExpiresAt();
        if (!empty($expireAt)) { // $expireAt 可能为 null
            $accessToken->setExpireTime($expireAt->getTimestamp());
        }
        $accessToken->setRefreshToken(''); // 没给，user access token 过期要重新进行授权
        $accessToken->setScope([]);
        $accessToken->setParams($fbAccessTokenParams);
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
        throw new SocialSdkException("facebook does not support refresh access token.");
    }

    /**
     * 获取授权用户信息
     * @return UserProfile
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function getUserProfile(): UserProfile
    {
        // 获取用户信息
        $apiUrl = '/me?fields=id,name,gender,birthday,email,picture';
        $response = $this->lib->get($apiUrl, $this->accessToken->getToken());
        $graphUser = $response->getGraphUser();

        // 写日志
        $this->writeLog("info", "获取用户信息成功:\n" . var_export($graphUser, true));

        // 性别
        $sex = UserProfile::SEX_UNKNOWN;
        if ($graphUser->getGender() == 'mail') {
            $sex = UserProfile::SEX_MALE;
        } elseif ($graphUser->getGender() == 'female') {
            $sex = UserProfile::SEX_FEMALE;
        }

        // 构造数据
        $userProfile = new UserProfile();
        $userProfile->setId((string)($graphUser->getId() ?? ''));
        $userProfile->setSex($sex);
        $userProfile->setPictureUrl((string)($graphUser->getPicture() ?? ''));
        $userProfile->setFullName((string)($graphUser->getName() ?? ''));
        $userProfile->setEmail((string)($graphUser->getEmail() ?? ''));
        if (!empty($graphUser->getBirthday())) {
            $userProfile->setBirthday($graphUser->getBirthday()->getTimestamp());
        }
        $userProfile->setLink((string)($graphUser->getLink() ?? ''));
        $userProfile->setParams($graphUser->asArray() ?? []);

        return $userProfile;
    }

    /**
     * 是否能够分享到用户
     * @return bool
     */
    public function canShareToUser(): bool
    {
        return false;
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
     * @return Channel[]
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function getShareChannelList(): array
    {
        // 获取主页
        $response = $this->lib->get('/me/accounts?type=page&fields=id,name,link,picture,access_token', $this->accessToken->getToken());
        $pages = $response->getGraphEdge()->asArray();

        // 写日志
        $this->writeLog("info", "获取主页数据成功:\n" . var_export($response, true));

        // 构造数据
        $channelList = [];
        foreach ($pages as $page) {
            $channel = new Channel();
            $channel->setId((string)($page['id'] ?? ''));
            $channel->setName((string)($page['name'] ?? ''));
            $channel->setUrl((string)($page['link'] ?? ''));
            $channel->setImgUrl((string)($page['picture']['data']['url'] ?? ''));
            $channel->setToken((string)($page['access_token'] ?? ''));
            $channel->setParams($page);
            $channelList[] = $channel;
        }

        return $channelList;
    }

    /**
     * 视频分享
     * @param VideoShareParams $params
     * @return VideoShareResult
     * @throws ShareException
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function shareVideo(VideoShareParams $params): VideoShareResult
    {
        // https://developers.facebook.com/docs/graph-api/reference/video#Creating
        // https://github.com/facebookarchive/php-graph-sdk/blob/master/docs/reference/Facebook.md#uploadvideo

        // 下载视频到本地
        $localFilePath = $this->downloadFile($params->getVideoUrl());

        // 发布视频到主页
        $data = [];
        $data['title'] = $params->getTitle();
        $data['description'] = $params->getDescription();
        if (!empty($params->getThumbnailUrl())) {
            // 需要权限：pages_read_user_content, pages_manage_engagement
            $data['thumb'] = $this->lib->fileToUpload($params->getThumbnailUrl());
        }
        $data['published'] = 'true';
        $targetPath = "/{$params->getSocialId()}/videos";

        try {
            $response = $this->lib->uploadVideo($targetPath, $localFilePath, $data, $params->getAccessToken());
        } catch (FacebookResponseException $ex) {
            throw (new ShareException($ex->getMessage(), $ex->getCode(), $ex))->setDevMsg($ex->getRawResponse())->setUnauthorized($ex->getCode() == 190);
        }

        // 写日志
        $this->writeLog("info", "发布到主页成功:\n" . var_export($response, true));

        // 有错误抛出
        if (isset($response['error'])) {
            throw (new ShareException($response['error']))->setDevMsg($response['error']);
        }

        // 分享链接
        $postId = (string)($response['video_id'] ?? $response['id_str'] ?? $response['id'] ?? '');
        if (empty($postId)) {
            throw (new ShareException("No found post id"))->setDevMsg("No found post id");
        }
        $postUrl = 'https://www.facebook.com/' . $postId;

        // 构造数据
        $result = new VideoShareResult();
        $result->setId($postId);
        $result->setTitle("");
        $result->setDescription('');
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
    }

}