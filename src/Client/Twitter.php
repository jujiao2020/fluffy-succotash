<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Client;


use Abraham\TwitterOAuth\TwitterOAuth;
use Jcsp\SocialSdk\Contract\ShareInterface;
use Jcsp\SocialSdk\Exception\SocialSdkException;
use Jcsp\SocialSdk\Model\AccessToken;
use Jcsp\SocialSdk\Model\AuthConfig;
use Jcsp\SocialSdk\Model\Channel;
use Jcsp\SocialSdk\Model\OAuthToken;
use Jcsp\SocialSdk\Model\UserProfile;
use Jcsp\SocialSdk\Model\VideoShareParams;
use Jcsp\SocialSdk\Model\VideoShareResult;

class Twitter extends OAuth1 implements ShareInterface
{

    /**
     * sdk
     * @var TwitterOAuth
     */
    private $lib;

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
        $this->lib = new TwitterOAuth($config->getClientId(), $config->getClientSecret());
        if (!is_null($token)) {
            $this->lib->setOauthToken($token->getToken(), $token->getTokenSecret());
        }
    }

    /**
     * 获取 oauth token 信息
     * @return OAuthToken
     * @throws \Abraham\TwitterOAuth\TwitterOAuthException
     */
    public function getOAuthToken(): OAuthToken
    {
        // 参考文档：
        // https://twitteroauth.com/redirect.php

        // 获取 oauth_token 和 oauth_token_secret
        $result = $this->lib->oauth('oauth/request_token', ['oauth_callback' => $this->authConfig->getRedirectUrl()]);

        // $result：
        // array(3) {
        //   ["oauth_token"]=>
        //   string(27) "rsKhGgAAAAAAAAAbAAABcqLgWlU"
        //   ["oauth_token_secret"]=>
        //   string(32) "CQ4t2yjpqdYQTxgYJQZd1MidkveLXx6K"
        //   ["oauth_callback_confirmed"]=>
        //   string(4) "true"
        // }

        // 写日志
        $this->writeLog("info", "oauth/request_token：结果：{$result}");

        // 构造数据返回
        $oauthToken = new OAuthToken();
        $oauthToken->setOauthToken($result['oauth_token'] ?? '');
        $oauthToken->setOauthTokenSecret($result['oauth_token_secret'] ?? '');
        $oauthToken->setOauthCallbackConfirmed($result['oauth_callback_confirmed'] === 'true');
        return $oauthToken;
    }

    /**
     * 生成授权链接
     * @param string $oauthToken
     * @return string
     */
    public function generateAuthUrlByClient(string $oauthToken): string
    {
        return $this->lib->url('oauth/authorize', ['oauth_token' => $oauthToken]);
    }

    /**
     * 获取 AccessToken
     * @param OAuthToken $oauthToken
     * @param string $oauthVerifier
     * @return AccessToken
     * @throws \Abraham\TwitterOAuth\TwitterOAuthException
     */
    public function getAccessTokenByClient(OAuthToken $oauthToken, string $oauthVerifier): AccessToken
    {
        // 参考文档：
        // https://twitteroauth.com/callback.php?oauth_token=rsKhGgAAAAAAAAAbAAABcqLgWlU&oauth_verifier=XOOExgUof0uWJ0hbnoyI3TK0FfdUaB5j

        // 获取 access token
        $this->lib->setOauthToken($oauthToken->getOauthToken(), $oauthToken->getOauthTokenSecret());
        $result = $this->lib->oauth('oauth/access_token', array('oauth_verifier' => $oauthVerifier));

        // $result：
        // array(4) {
        //   ["oauth_token"]=>
        //   string(50) "1247749112746766342-nGyf2YlWYePPrCMpkJbfaR7OEAVTTQ"
        //   ["oauth_token_secret"]=>
        //   string(45) "eRKCuOxDkT0XV3DVtHr6ZVOZqnLGfI8pYJLmLQD7S0ugG"
        //   ["user_id"]=>
        //   string(19) "1247749112746766342"
        //   ["screen_name"]=>
        //   string(7) "opiuycc"
        // }

        // 构造数据
        $accessToken = new AccessToken();
        $accessToken->setToken((string)($result['oauth_token'] ?? ''));
        $accessToken->setTokenSecret((string)($result['oauth_token_secret'] ?? ''));
        $accessToken->setExpireTime(0); // access token 不过期
        $accessToken->setRefreshToken(''); // access token 不过期
        $accessToken->setScope([]);
        $accessToken->setParams($result);
        $accessToken->setUserId((string)($result['user_id'] ?? ''));

        return $accessToken;
    }

    /**
     * 获取授权用户信息
     * @return UserProfile
     */
    public function getUserProfile(): UserProfile
    {
        // 获取用户信息
        $user = $this->lib->get('account/verify_credentials', ['tweet_mode' => 'extended', 'include_entities' => 'true']);

        // 写日志
        $this->writeLog("info", "获取用户信息成功:\n" . var_export($user, true));

        // $user：
        // object(stdClass)#12 (43) {
        //   ["id"]=>
        //   int(1247749112746766342)
        //   ["id_str"]=>
        //   string(19) "1247749112746766342"
        //   ["name"]=>
        //   string(7) "opiuycc"
        //   ["screen_name"]=>
        //   string(7) "opiuycc"
        //   ["location"]=>
        //   string(0) ""
        //   ["description"]=>
        //   string(0) ""
        //   ["url"]=>
        //   NULL
        //   ["entities"]=>
        //   object(stdClass)#18 (1) {
        //     ["description"]=>
        //     object(stdClass)#17 (1) {
        //       ["urls"]=>
        //       array(0) {
        //       }
        //     }
        //   }
        //   ["protected"]=>
        //   bool(false)
        //   ["followers_count"]=>
        //   int(0)
        //   ["friends_count"]=>
        //   int(0)
        //   ["listed_count"]=>
        //   int(0)
        //   ["created_at"]=>
        //   string(30) "Wed Apr 08 04:52:37 +0000 2020"
        //   ["favourites_count"]=>
        //   int(0)
        //   ["utc_offset"]=>
        //   NULL
        //   ["time_zone"]=>
        //   NULL
        //   ["geo_enabled"]=>
        //   bool(false)
        //   ["verified"]=>
        //   bool(false)
        //   ["statuses_count"]=>
        //   int(0)
        //   ["lang"]=>
        //   NULL
        //   ["contributors_enabled"]=>
        //   bool(false)
        //   ["is_translator"]=>
        //   bool(false)
        //   ["is_translation_enabled"]=>
        //   bool(false)
        //   ["profile_background_color"]=>
        //   string(6) "F5F8FA"
        //   ["profile_background_image_url"]=>
        //   NULL
        //   ["profile_background_image_url_https"]=>
        //   NULL
        //   ["profile_background_tile"]=>
        //   bool(false)
        //   ["profile_image_url"]=>
        //   string(77) "http://abs.twimg.com/sticky/default_profile_images/default_profile_normal.png"
        //   ["profile_image_url_https"]=>
        //   string(78) "https://abs.twimg.com/sticky/default_profile_images/default_profile_normal.png"
        //   ["profile_link_color"]=>
        //   string(6) "1DA1F2"
        //   ["profile_sidebar_border_color"]=>
        //   string(6) "C0DEED"
        //   ["profile_sidebar_fill_color"]=>
        //   string(6) "DDEEF6"
        //   ["profile_text_color"]=>
        //   string(6) "333333"
        //   ["profile_use_background_image"]=>
        //   bool(true)
        //   ["has_extended_profile"]=>
        //   bool(true)
        //   ["default_profile"]=>
        //   bool(true)
        //   ["default_profile_image"]=>
        //   bool(true)
        //   ["following"]=>
        //   bool(false)
        //   ["follow_request_sent"]=>
        //   bool(false)
        //   ["notifications"]=>
        //   bool(false)
        //   ["translator_type"]=>
        //   string(4) "none"
        //   ["suspended"]=>
        //   bool(false)
        //   ["needs_phone_verification"]=>
        //   bool(false)
        // }

        // 构造数据
        $userProfile = new UserProfile();
        $userProfile->setId((string)($user->id_str ?? $user->id ?? ''));
        $userProfile->setSex(UserProfile::SEX_UNKNOWN);
        $userProfile->setPictureUrl((string)($user->profile_image_url ?? ''));
        $userProfile->setFullName((string)($user->name ?? ''));
        $userProfile->setEmail('');
        $userProfile->setBirthday(0);
        $userProfile->setParams(json_decode(json_encode($user ?: []), true));
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
     * @throws SocialSdkException
     */
    public function shareVideo(VideoShareParams $params): VideoShareResult
    {
        // 下载视频到本地
        $localFilePath = $this->downloadFile($params->getVideoUrl());

        // 上传视频
        // $mimeType = 'video/' . strtolower(pathinfo($params->getVideoUrl(), PATHINFO_EXTENSION));
        $mimeType = mime_content_type($localFilePath);
        $this->lib->setTimeouts(25, 35); // 设置超时时间
        $media = $this->lib->upload(
            'media/upload',
            [
                'media' => $localFilePath,
                'media_type' => $mimeType,
                'media_category' => 'tweet_video'
            ],
            true
        );

        // 删除本地文件
        unlink($localFilePath);

        // 定时检查文件状态，尝试查询最多5次，直至状态为成功或失败
        // "pending” -> “in_progress” -> [“failed” | “succeeded”]
        // https://developer.twitter.com/en/docs/media/upload-media/api-reference/get-media-upload-status
        $attempts = 0;
        $info = null;
        while (!in_array(($info = $this->lib->mediaStatus($media->media_id_string))->processing_info->state, ['failed', 'succeeded']) && $attempts < 5) {
            $attempts++;
            $checkAfterSecs = $info->processing_info->check_after_secs;
            sleep($checkAfterSecs);
        }

        // 上传失败的情况，中断流程
        if ($info->processing_info->state == 'failed') {
            throw new SocialSdkException("Twitter文件上传失败！");
        }

        // 发视频推文
        $data = ['status' => $params->getTitle()];
        $data['media_ids'] = implode(',', [$media->media_id_string]);
        $result = $this->lib->post('statuses/update', $data);
        $resultArr = json_encode($result);
        $resultArr = json_decode($resultArr, true);
        if (isset($resultArr["errors"][0]["message"])) {
            throw new SocialSdkException($resultArr["errors"][0]["message"]);
        }

        // 分享链接
        $userId = $result['user']['id_str'] ?? $this->accessToken->getUserId();
        $postUrl = 'https://twitter.com/' . $userId . '/status/' . $result['id_str'];

        // 构造数据
        $result = new VideoShareResult();
        $result->setId((string)($result['id_str'] ?? ''));
        $result->setTitle((string)($result['text'] ?? ''));
        $result->setDescription('');
        $result->setThumbnailUrl('');
        $result->setUrl($postUrl);
        $result->setCreatedTime(strtotime((string)($result['created_at'] ?? '')));
        return $result;
    }

}