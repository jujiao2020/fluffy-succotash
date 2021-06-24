<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Client;


use Jcsp\SocialSdk\Contract\ShareInterface;
use Jcsp\SocialSdk\Exception\ShareException;
use Jcsp\SocialSdk\Model\AccessToken;
use Jcsp\SocialSdk\Model\AuthConfig;
use Jcsp\SocialSdk\Model\Channel;
use Jcsp\SocialSdk\Model\OAuthToken;
use Jcsp\SocialSdk\Model\UserProfile;
use Jcsp\SocialSdk\Model\VideoShareParams;
use Jcsp\SocialSdk\Model\VideoShareResult;

class Tumblr extends OAuth1 implements ShareInterface
{

    const BASE_URL = 'https://www.tumblr.com';
    const API_BASE_URL = 'https://api.tumblr.com';

    /**
     * @var \Tumblr\API\Client
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
        $this->lib = new \Tumblr\API\Client($config->getClientId(), $config->getClientSecret());
        if (!is_null($token)) {
            $this->lib->setToken($token->getToken(), $token->getTokenSecret());
        }
    }

    /**
     * 获取 oauth token 信息
     * @return OAuthToken
     */
    public function getOAuthToken(): OAuthToken
    {
        // 获取 OAuthToken 信息
        $requestHandler = $this->lib->getRequestHandler();
        $requestHandler->setBaseUrl(self::BASE_URL);
        $resp = $requestHandler->request('POST', 'oauth/request_token', []);
        $result = $resp->body;
        $data = [];
        parse_str($result, $data);

        // 写日志
        $this->writeLog("info", "oauth/request_token：结果：{$result}");

        // 构造数据返回
        $oauthToken = new OAuthToken();
        $oauthToken->setOauthToken($data['oauth_token'] ?? '');
        $oauthToken->setOauthTokenSecret($data['oauth_token_secret'] ?? '');
        $oauthToken->setOauthCallbackConfirmed($data['oauth_callback_confirmed'] === 'true');
        return $oauthToken;
    }

    /**
     * 生成授权链接
     * @param string $oauthToken
     * @return string
     */
    public function generateAuthUrlByClient(string $oauthToken): string
    {
        return self::BASE_URL . "/oauth/authorize?oauth_token={$oauthToken}";
    }

    /**
     * 获取 AccessToken
     * @param OAuthToken $oauthToken
     * @param string $oauthVerifier
     * @return AccessToken
     */
    public function getAccessTokenByClient(OAuthToken $oauthToken, string $oauthVerifier): AccessToken
    {
        // 获取 Access Token
        $this->lib->setToken($oauthToken->getOauthToken(), $oauthToken->getOauthTokenSecret());
        $requestHandler = $this->lib->getRequestHandler();
        $requestHandler->setBaseUrl(self::BASE_URL);
        $resp = $requestHandler->request('POST', 'oauth/access_token', ['oauth_verifier' => $oauthVerifier]);
        $result = $resp->body;
        $data = [];
        parse_str($result, $data);

        // 写日志
        $this->writeLog("info", "oauth/request_token：结果：" . var_export($resp, true));

        // 构造数据
        $accessToken = new AccessToken();
        $accessToken->setToken((string)($data['oauth_token'] ?? ''));
        $accessToken->setTokenSecret((string)($data['oauth_token_secret'] ?? ''));
        $accessToken->setExpireTime(0); // access token 不过期
        $accessToken->setRefreshToken(''); // access token 不过期
        $accessToken->setScope([]);
        $accessToken->setParams($data);
        $accessToken->setUserId('');
        $this->lib->setToken($accessToken->getToken(), $accessToken->getTokenSecret());

        return $accessToken;
    }

    /**
     * 获取授权用户信息
     * @return UserProfile
     */
    public function getUserProfile(): UserProfile
    {
        // 获取用户信息
        $this->lib->getRequestHandler()->setBaseUrl(self::API_BASE_URL);
        $info = $this->lib->getUserInfo();

        // getUserInfo: object(stdClass)#72 (1) {
        //   ["user"]=>
        //   object(stdClass)#59 (5) {
        //     ["name"]=>
        //     string(13) "youbbweseesee"
        //     ["likes"]=>
        //     int(0)
        //     ["following"]=>
        //     int(2)
        //     ["default_post_format"]=>
        //     string(4) "html"
        //     ["blogs"]=>
        //     array(2) {
        //         ...
        //     }
        //   }
        // }

        // 写日志
        $this->writeLog("info", "获取用户信息成功:\n" . var_export($info, true));

        // 构造数据
        $userProfile = new UserProfile();
        $userProfile->setId((string)($info->user->name ?? ''));
        $userProfile->setSex(UserProfile::SEX_UNKNOWN);
        $userProfile->setPictureUrl('');
        $userProfile->setFullName((string)($info->user->name ?? ''));
        $userProfile->setEmail('');
        $userProfile->setParams(json_decode(json_encode($info->user ?? []), true));
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
     */
    public function getShareChannelList(): array
    {
        // 调用获取用户数据接口获取 blog 列表

        // 获取用户信息
        $this->lib->getRequestHandler()->setBaseUrl(self::API_BASE_URL);
        $info = $this->lib->getUserInfo();

        // 写日志
        $this->writeLog("info", "获取 blog 数据成功:\n" . var_export($info, true));

        // 构造数据
        $posts = $info->user->blogs ?? [];
        $channelList = [];
        foreach ($posts as $blog) {
            if ($blog->admin) {
                $channel = new Channel();
                $channel->setId((string)($blog->uuid ?? ''));
                $channel->setName((string)($blog->name ?? ''));
                $channel->setUrl((string)($blog->url ?? ''));
                $channel->setParams(json_decode(json_encode($blog, JSON_UNESCAPED_UNICODE), true));
                $channelList[] = $channel;
            }
        }

        return $channelList;
    }

    /**
     * 视频分享
     * @param VideoShareParams $params
     * @return VideoShareResult
     * @throws ShareException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function shareVideo(VideoShareParams $params): VideoShareResult
    {
        // https://www.tumblr.com/docs/en/api/v2#post--create-a-new-blog-post-legacy

        // 视频限制100MB

        // 创建 video post 成功后，返回的 id 是假的，因为等 tumblr 视频转码完成后又会有新的 post id，只有 type 为 video 才会转码。
        // 转码过程中，调用接口获取某个 blog 下的 post ，post 还是转码中的 id 的，所以也不是最终的 id ，等转码完成后才能获取到最终的 id 。
        // 参考一下讨论：
        // https://github.com/tumblr/tumblr.php/issues/98
        // https://groups.google.com/forum/#!searchin/tumblr-api/video$20post$20id|sort:date/tumblr-api/A3_UDcrtXTM/qyw7tf4GBQAJ

        // 发布视频
        $blogName = $params->getDisplayName();
        $timeStr = gmdate('Y-m-d H:i:s');
        $data = [
            'type' => 'video',
            'state' => 'published', // The state of the post. Specify one of the following: published, draft, queue, private
            // 'tags' => "", // Comma-separated tags for this post
            'caption' => $params->getTitle(),
            'date' => $timeStr,
            'data' => $params->getVideoUrl(), // path is like the url of video I've uploaded，可以是本地，也可以是远程链接
        ];
        $this->lib->getRequestHandler()->setBaseUrl(self::API_BASE_URL);
        $res = $this->lib->createPost($blogName, $data);
        // 写日志
        $this->writeLog("info", "分享视频成功:\n" . var_export($res, true));


        // 获取视频状态和链接，如果状态不是 published 是 transcoding，此 id 不是最终的 id ，所以实际上此时拼凑的链接也是 404 的
        $state = $res->state ?? '';
        $asyncToGetUrl = strtolower($state) != 'published';
        $postId = (string)($res->id_string ?? $res->id ?? '');
        $postUrl = "https://{$blogName}.tumblr.com/post/{$postId}";

        // // 先请求一下上面拼接的链接，如果能正常访问，则认为这个链接正确。
        // $isPostUrlCorrect = false;
        // try {
        //     $httpClient = new \GuzzleHttp\Client();
        //     $response = $httpClient->request('GET', $postUrl);
        //     if ($response->getStatusCode() == 200) {
        //         $isPostUrlCorrect = true;
        //     }
        // } catch (\Exception $ex) {
        //     // GuzzleHttp 发现请求状态码大于等400会抛异常
        // }
        //
        // // 如果链接无法正常访问，就定时查询当前 blog 的 post 列表，找到这次上传的视频 post
        // // TODO:暂时这样阻塞调用，以后尝试下开新进程处理
        // if (!$isPostUrlCorrect) {
        //     $tryTime = 0;
        //     while ($tryTime < 8) {
        //         sleep(30);
        //         $tryTime++;
        //         $postUrl = $this->getPostUrl($blogName, $params->getTitle(), strtotime($timeStr), $postId);
        //         if (!empty(trim($postUrl))) {
        //             break;
        //         }
        //     }
        //     if (empty(trim($postUrl))) {
        //         throw (new ShareException('发布失败'))->setDevMsg("尝试获取 post 链接失败，尝试次数：{$tryTime} 次");
        //     }
        // }

        // 构造数据
        $result = new VideoShareResult();
        $result->setId($postId);
        $result->setTitle('');
        $result->setDescription('');
        $result->setThumbnailUrl('');
        $result->setUrl($postUrl);
        $result->setCreatedTime(strtotime($timeStr));
        $result->setAsyncToGetUrl($asyncToGetUrl);
        return $result;
    }

    /**
     * 根据条件获取 post 链接
     * @param string $blogName
     * @param string $videoName
     * @param int $uploadTimestamp
     * @param string $originPostIdStr
     * @return string
     */
    private function getPostUrl(string $blogName, string $videoName, int $uploadTimestamp, string $originPostIdStr): string
    {
        // 获取某个 blog 下的 post 列表
        $params = ['type' => 'video'];
        $res = $this->lib->getBlogPosts($blogName, $params);

        // 写日志
        $this->writeLog("info", "尝试抓取获取分享链接：blogName:{$blogName}\n：" . var_export($res, true), 'shareVideo');

        // 分析结果，找出匹配的 post 和 post url
        $posts = $res->posts ?? [];
        $postUrl = '';
        foreach ($posts as $post) {
            // 发现 summary 可以认为是视频上传时的 title ，用这个做依据
            if ($post->timestamp >= $uploadTimestamp && $post->summary == $videoName && $post->id_string != $originPostIdStr) {
                $postUrl = $post->post_url ?? '';
                // $postUrl = $post->short_url ?? '';
                break;
            }
        }

        return $postUrl;
    }

    /**
     * 异步获取视频分享链接
     * @param VideoShareParams $params
     * @param VideoShareResult $result
     * @return string
     * @throws \Exception
     */
    public function asyncToGetUrl(VideoShareParams $params, VideoShareResult $result): string
    {
        $originPostIdStr = $result->getId();
        $uploadTimestamp = $result->getCreatedTime();

        if (empty($originPostIdStr)) {
            throw new \Exception('genesis_post_id 不能为空');
        }

        $videoName = $params->getTitle();
        $blogName = $params->getDisplayName();

        // 获取某个 blog 下的 post 列表
        $params = ['type' => 'video'];
        $res = $this->lib->getBlogPosts($blogName, $params);

        // 写日志
        $this->writeLog("info", "尝试抓取获取分享链接：videoName: {$videoName}， blogName:{$blogName}\n："
            . json_encode($res, JSON_UNESCAPED_UNICODE));

        // 分析结果，找出匹配的 post 和 post url
        $posts = $res->posts ?? [];
        $postUrl = '';

        // 先用这个匹配
        foreach ($posts as $post) {
            $genesisPostId = $post->genesis_post_id ?? '';
            if (!empty($genesisPostId) && $genesisPostId == $originPostIdStr) {
                $postUrl = $post->post_url ?? '';
                // $postUrl = $post->short_url ?? '';
                break;
            }
        }

        // 如果没有，就用标题匹配
        if (empty($postUrl)) {
            foreach ($posts as $post) {
                // 发现 summary 可以认为是视频上传时的 title ，用这个做依据
                if ($post->timestamp >= $uploadTimestamp && $post->summary == $videoName && $post->id_string != $originPostIdStr) {
                    $postUrl = $post->post_url ?? '';
                    // $postUrl = $post->short_url ?? '';
                    break;
                }
            }
        }

        return $postUrl;
    }

    /**
     * 设置视频缩略图
     * @param string $videoId
     * @param string $thumbnailUrl
     */
    public function setThumbnail(string $videoId, string $thumbnailUrl): void
    {
        // 不支持
    }

}