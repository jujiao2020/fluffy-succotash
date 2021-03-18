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

class LinkedIn extends OAuth2 implements ShareInterface
{
    // https://gitlab.com/HelloAllan/PHP-LinkedIn-SDK/blob/eb23161b9f503b36b442323e3fe23587ddbe6215/LinkedIn/LinkedIn.php

    const OAUTH_BASE_URL = 'https://www.linkedin.com/oauth/v2';
    const API_BASE_URL = 'https://api.linkedin.com/v2';

    /**
     * 默认权限
     * @var array
     */
    private $defaultScope = [
        'r_emailaddress',
        'r_liteprofile',
        // 'w_compliance', // 需要申请权限
        // 'r_member_social',// 需要申请权限
        'w_member_social',
        // 'r_organization_social',// 需要申请权限
        // 'w_organization_social',// 需要申请权限
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
        $scopeStr = implode('%20', $this->authConfig->getScope());

        // 授权链接
        $authUrl = self::OAUTH_BASE_URL . "/authorization?response_type=code&client_id={$this->authConfig->getClientId()}&scope={$scopeStr}&state={$state}&redirect_uri=" . urlencode($this->authConfig->getRedirectUrl());

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
        // 获取 Access Token
        // https://docs.microsoft.com/zh-cn/linkedin/shared/authentication/authorization-code-flow?context=linkedin/context#step-3-exchange-authorization-code-for-an-access-token
        $getAccessTokenUrl = self::OAUTH_BASE_URL . '/accessToken';
        $params = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->authConfig->getClientId(),
            'client_secret' => $this->authConfig->getClientSecret(),
            'redirect_uri' => $this->authConfig->getRedirectUrl(),
        ];
        $client = new \GuzzleHttp\Client();
        $res = $client->request('POST', $getAccessTokenUrl, [
            'form_params' => $params,
        ]);
        $resBody = $res->getBody()->getContents();
        if ($res->getStatusCode() < 200 || $res->getStatusCode() > 300) {
            throw new SocialSdkException('Get Access Token Fail: ' . $resBody);
        }
        $data = json_decode($resBody, true);
        if (isset($data['error']) && !empty($data['error'])) {
            throw new SocialSdkException('Access Token Request Error: ' . $data['error'] . ' -- ' . $data['error_description']);
        }

        // 写日志
        $this->writeLog("info", "code：{$code}\n响应结果：\n" . var_export($resBody, true));

        // The length of access tokens is ~500 characters.
        // We recommend that you plan for your application to handle tokens with length of at least 1000 characters in order to accommodate any future expansion plans.
        // This applies to both access tokens and refresh tokens.
        // 返回示例：
        // {
        //   "access_token": "AQXNnd2kXITHELmWblJigbHEuoFdfRhOwGA0QNnumBI8XOVSs0HtOHEU-wvaKrkMLfxxaB1O4poRg2svCWWgwhebQhqrETYlLikJJMgRAvH1ostjXd3DP3BtwzCGeTQ7K9vvAqfQK5iG_eyS-q-y8WNt2SnZKZumGaeUw_zKqtgCQavfEVCddKHcHLaLPGVUvjCH_KW0DJIdUMXd90kWqwuw3UKH27ki5raFDPuMyQXLYxkqq4mYU-IUuZRwq1pcrYp1Vv-ltbA_svUxGt_xeWeSxKkmgivY_DlT3jQylL44q36ybGBSbaFn-UU7zzio4EmOzdmm2tlGwG7dDeivdPDsGbj5ig",
        //   "expires_in": 86400,
        //   "refresh_token": "AQWAft_WjYZKwuWXLC5hQlghgTam-tuT8CvFej9-XxGyqeER_7jTr8HmjiGjqil13i7gMFjyDxh1g7C_G1gyTZmfcD0Bo2oEHofNAkr_76mSk84sppsGbygwW-5oLsb_OH_EXADPIFo0kppznrK55VMIBv_d7SINunt-7DtXCRAv0YnET5KroQOlmAhc1_HwW68EZniFw1YnB2dgDSxCkXnrfHYq7h63w0hjFXmgrdxeeAuOHBHnFFYHOWWjI8sLLenPy_EBrgYIitXsAkLUGvZXlCjAWl-W459feNjHZ0SIsyTVwzAQtl5lmw1ht08z5Du-RiQahQE0sv89eimHVg9VSNOaTvw",
        //   "refresh_token_expires_in": 525600
        // }

        // 但实际接口返回，没有给 refresh_token 和 refresh_token_expires_in
        // LinkedIn supports programmatic refresh tokens for a limited set of partners on a case-by-case basis.

        // 构造数据
        $accessToken = new AccessToken();
        $accessToken->setToken((string)($data['access_token'] ?? ''));
        $accessToken->setExpireTime(time() + (int)($data['expires_in'] ?? 0));
        if (isset($data['refresh_token'])) {
            $accessToken->setRefreshToken((string)($data['refresh_token'] ?? ''));
            $accessToken->setRefreshTokenExpireTime(time() + (int)($data['refresh_token_expires_in'] ?? 0));
        }
        $accessToken->setScope([]);
        $accessToken->setParams($data);
        $accessToken->setUserId("");

        return $accessToken;
    }

    /**
     * AccessToken 是否已经过期
     * @return bool
     */
    public function isAccessTokenExpired(): bool
    {
        // https://docs.microsoft.com/zh-cn/linkedin/shared/authentication/programmatic-refresh-tokens?context=linkedin/context

        $flag = time() > $this->accessToken->getExpireTime();

        // 不是每个应用都会有 refresh token，如果有 refresh token ，还要判断 refresh token 是否过期
        if (!empty($this->accessToken->getRefreshToken()) && !empty($this->accessToken->getRefreshTokenExpireTime())) {
            $flag = $flag && time() > $this->accessToken->getRefreshTokenExpireTime();
        }
        return $flag;
    }

    /**
     * 是否能够 RefreshToken
     * @return bool
     */
    public function allowRefreshToken(): bool
    {
        // 不是每个应用都会有 refresh token，如果有 refresh token ，还要判断 refresh token 是否过期

        if (empty($this->accessToken) || empty($this->accessToken->getRefreshToken())) {
            return false;
        }

        if ($this->accessToken->getRefreshTokenExpireTime() == 0 || time() > $this->accessToken->getRefreshTokenExpireTime()) {
            return false;
        }

        return true;
    }

    /**
     * 刷新 AccessToken
     * @param string $refreshToken
     * @return AccessToken
     * @throws SocialSdkException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refreshAccessTokenByClient(string $refreshToken): AccessToken
    {
        // 刷新 Access Token
        $endpoint = self::OAUTH_BASE_URL . '/accessToken';
        $params = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->accessToken->getRefreshToken(),
            'client_id' => $this->authConfig->getClientId(),
            'client_secret' => $this->authConfig->getClientSecret(),
        ];
        $client = new \GuzzleHttp\Client();
        $res = $client->request('POST', $endpoint, [
            'form_params' => $params,
        ]);
        $resBody = $res->getBody()->getContents();
        if ($res->getStatusCode() < 200 || $res->getStatusCode() > 300) {
            throw new SocialSdkException('Refresh Access Token fail：' . $resBody);
        }
        $result = json_decode($resBody, true);
        if (isset($result['error']) && !empty($result['error'])) {
            throw new SocialSdkException('Refresh Access Token Request Error: ' . $result['error'] . ' -- ' . $result['error_description']);
        }

        // 写日志
        $this->writeLog("info", "刷新 access_token:\n" . var_export($resBody, true));

        // $result:
        // {
        //   "access_token": "BBBB2kXITHELmWblJigbHEuoFdfRhOwGA0QNnumBI8XOVSs0HtOHEU-wvaKrkMLfxxaB1O4poRg2svCWWgwhebQhqrETYlLikJJMgRAvH1ostjXd3DP3BtwzCGeTQ7K9vvAqfQK5iG_eyS-q-y8WNt2SnZKZumGaeUw_zKqtgCQavfEVCddKHcHLaLPGVUvjCH_KW0DJIdUMXd90kWqwuw3UKH27ki5raFDPuMyQXLYxkqq4mYU-IUuZRwq1pcrYp1Vv-ltbA_svUxGt_xeWeSxKkmgivY_DlT3jQylL44q36ybGBSbaFn-UU7zzio4EmOzdmm2tlGwG7dDeivdPDsGbj5ig",
        //   "expires_in": 86400,
        //   "refresh_token": "AQWAft_WjYZKwuWXLC5hQlghgTam-tuT8CvFej9-XxGyqeER_7jTr8HmjiGjqil13i7gMFjyDxh1g7C_G1gyTZmfcD0Bo2oEHofNAkr_76mSk84sppsGbygwW-5oLsb_OH_EXADPIFo0kppznrK55VMIBv_d7SINunt-7DtXCRAv0YnET5KroQOlmAhc1_HwW68EZniFw1YnB2dgDSxCkXnrfHYq7h63w0hjFXmgrdxeeAuOHBHnFFYHOWWjI8sLenPy_EBrgYIitXsAkLUGvZXlCjAWl-W459feNjHZ0SIsyTVwzAQtl5lmw1ht08z5Du-RiQahQE0sv89eimHVg9VSNOaTvw",
        //   "refresh_token_expires_in": 439200
        // }

        // 构造数据
        $accessToken = new AccessToken();
        $accessToken->setToken((string)($result['access_token'] ?? ''));
        $accessToken->setExpireTime(time() + (int)($result['expires_in'] ?? 0));
        $accessToken->setRefreshToken((string)($result['refresh_token'] ?? ''));
        $accessToken->setRefreshTokenExpireTime(time() + (int)($result['refresh_token_expires_in'] ?? 0));
        $accessToken->setScope([]);
        $accessToken->setParams($result);
        $accessToken->setUserId("");
        return $accessToken;
    }

    /**
     * 获取授权用户信息
     * @return UserProfile
     * @throws SocialSdkException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getUserProfile(): UserProfile
    {
        // 获取用户信息
        $endpoint = self::API_BASE_URL . '/me';
        $headers = [
            'x-li-format' => 'json',
            'Authorization' => 'Bearer ' . $this->accessToken->getToken(),
        ];
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $endpoint, [
            'headers' => $headers,
        ]);
        $resBody = $res->getBody()->getContents();
        if ($res->getStatusCode() < 200 || $res->getStatusCode() > 300) {
            throw new SocialSdkException('获取用户信息失败：' . $resBody);
        }
        $result = json_decode($resBody, true);

        // 写日志
        $this->writeLog("info", "获取用户信息成功:\n" . var_export($resBody, true));

        // $result:
        // {
        //     "localizedLastName": "Sue",
        //     "lastName": {
        //         "localized": {
        //             "en_US": "Sue"
        //         },
        //         "preferredLocale": {
        //             "country": "US",
        //             "language": "en"
        //         }
        //     },
        //     "firstName": {
        //         "localized": {
        //             "en_US": "Chen"
        //         },
        //         "preferredLocale": {
        //             "country": "US",
        //             "language": "en"
        //         }
        //     },
        //     "id": "SLAcABDg77",
        //     "localizedFirstName": "Chen"
        // }

        // 用户名称
        $name = trim((string)($result['localizedFirstName'] ?? '') . ' ' . (string)($result['localizedLastName'] ?? ''));

        // 构造数据
        $userProfile = new UserProfile();
        $userProfile->setId((string)($result['id'] ?: ''));
        $userProfile->setSex(0);
        $userProfile->setPictureUrl('');
        $userProfile->setFullName($name);
        $userProfile->setEmail('');
        $userProfile->setBirthday(0);
        $userProfile->setParams((array)($result ?? []));

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
        // TODO:没有申请到权限，无法做下去
        return true;
    }

    /**
     * 获取要分享到的频道列表
     * @return Channel[]
     */
    public function getShareChannelList(): array
    {
        // TODO:没有申请到权限，无法做下去
        return [];
    }

    /**
     * 视频分享
     * @param VideoShareParams $params
     * @return VideoShareResult
     * @throws SocialSdkException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function shareVideo(VideoShareParams $params): VideoShareResult
    {
        // https://docs.microsoft.com/en-us/linkedin/marketing/integrations/community-management/shares/ugc-post-api
        // 发布视频只能用 ARTICLE 类型，用 VIDEO 类型提示应用无权限
        // 用 youtube 、 vimeo 的链接可以在帖子直接显示视频播放器，twitter、pinterest、vk 只能显示截图，云服务商的视频链接只显示一个外链链接。

        // 请求参数
        $data = [
            'author' => 'urn:li:person:' . $params->getSocialId(),
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => ['text' => $params->getDescription()],
                    'shareMediaCategory' => 'ARTICLE',
                    'media' => [
                        [
                            'status' => 'READY',
                            'description' => ['text' => $params->getDescription()],
                            'originalUrl' => $params->getVideoUrl(),
                            'title' => ['text' => $params->getTitle()],
                        ]
                    ]
                ]
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
            ]
        ];

        // 发布视频
        $endpoint = self::API_BASE_URL . "/ugcPosts";
        $headers = [
            // 'Content-Type' => 'application/json', // GuzzleHttp 会自动转成使用这个
            'X-Restli-Protocol-Version' => '2.0.0',
            'x-li-format' => 'json',
            'Authorization' => 'Bearer ' . $this->accessToken->getToken(),
        ];
        $client = new \GuzzleHttp\Client();
        $res = $client->request('POST', $endpoint, [
            'headers' => $headers,
            'json' => $data,
        ]);
        $resBody = $res->getBody()->getContents();
        if ($res->getStatusCode() < 200 || $res->getStatusCode() > 300) {
            throw new ShareException('Share Video Failed:' . $resBody);
        }
        $responseData = json_decode($resBody, true);

        // 写日志
        $this->writeLog("info", "发布视频成功:\nheader:\n" .
            var_export(json_encode($res->getHeaders(), JSON_UNESCAPED_UNICODE), true) . "\nbody:\n" .
            var_export($responseData, true));

        // 获取 post id
        $postId = $res->getHeader('X-RestLi-Id')[0] ?? $responseData['id'] ?? '';
        if (empty($postId)) {
            throw (new ShareException('发布失败'))->setDevMsg('发布视频失败，无法获取 post id');
        }
        $postUrl = "https://www.linkedin.com/feed/update/{$postId}";
        // 分享url格式： https://www.linkedin.com/feed/update/urn:li:share:6684377513214517248/

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