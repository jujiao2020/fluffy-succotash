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

class Youtube extends OAuth2 implements ShareInterface
{

    /**
     * @var \Google_Client
     */
    private $lib;

    /**
     * 默认权限
     * @var array
     */
    private $defaultScope = [
        'https://www.googleapis.com/auth/youtube',
        'https://www.googleapis.com/auth/youtube.upload',
        'https://www.googleapis.com/auth/youtube.force-ssl',
        'https://www.googleapis.com/auth/youtube.readonly',
        'https://www.googleapis.com/auth/youtubepartner',
        'https://www.googleapis.com/auth/youtubepartner-channel-audit',
        'https://www.googleapis.com/auth/userinfo.profile',
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
        $this->lib = new \Google_Client();
        $this->lib->setClientId($config->getClientId());
        $this->lib->setClientSecret($config->getClientSecret());
        if (!is_null($token)) {
            $this->lib->setAccessToken($token->getParams());
        }
        $config->setScope($config->getScope() ?: $this->defaultScope);
    }

    /**
     * 生成授权链接
     * @return string
     */
    public function generateAuthUrlByClient(): string
    {
        $state = uniqid();

        $this->lib->setScopes(implode(' ', $this->authConfig->getScope()));
        $redirect = filter_var($this->authConfig->getRedirectUrl(), FILTER_SANITIZE_URL);
        $this->lib->setRedirectUri($redirect);
        $this->lib->setAccessType('offline');
        $this->lib->setApprovalPrompt('force');
        $this->lib->setState($state);
        return $this->lib->createAuthUrl();
    }

    /**
     * 获取 AccessToken
     * @param string $code
     * @param string $state
     * @return AccessToken
     */
    public function getAccessTokenInAuthorizationCodeModeByClient(string $code, string $state): AccessToken
    {
        // 获取 access token
        $this->lib->setScopes(implode(' ', $this->authConfig->getScope()));
        $redirect = filter_var($this->authConfig->getRedirectUrl(), FILTER_SANITIZE_URL);
        $this->lib->setRedirectUri($redirect);
        $this->lib->setAccessType('offline');
        $this->lib->setApprovalPrompt('force');
        $accessTokenData = $this->lib->fetchAccessTokenWithAuthCode($code);

        // 写日志
        $this->writeLog("info", "code：{$code}\n响应结果：\n" . var_export($accessTokenData, true));

        // 构造数据
        // $accessTokenData：
        // {"access_token":"ya29.a0Adw1xeUj4JL3PLlRLOqn6nyTPe0tJZvETBCyOldAEBaGBPLW98s4k0fIvpMoWm1ilbV4ir4AFk4PBmhZlPtIujk9bHerrjssrgTxMskd46chkLL7hvA32SJPe5ymVosRcSnzJ1NK1FgAzLNK8rVz46OJZcz-dFExKK","expires_in":3599,"refresh_token":"1\/\/0ejgTLv2u162yCgYIARAAGA4SNwF-L9Irxm-p-r6WZRbCG8Y5CXX-mZDRHAoyeE43FCY1rr0UhTs6ToEfSpThRDwnYZsk2VpVF9","scope":"https:\/\/www.googleapis.com\/auth\/youtubepartner-channel-audit https:\/\/www.googleapis.com\/auth\/youtubepartner https:\/\/www.googleapis.com\/auth\/youtube.upload https:\/\/www.googleapis.com\/auth\/youtube https:\/\/www.googleapis.com\/auth\/userinfo.profile https:\/\/www.googleapis.com\/auth\/youtube.force-ssl https:\/\/www.googleapis.com\/auth\/youtube.readonly","token_type":"Bearer","id_token":"eyJhbGciOiJSUzI1NiIsImtpZCI6IjUzYzY2YWFiNTBjZmRkOTFhMTQzNTBhNjY0ODJkYjM4MDBjODNjNjMiLCJ0eXAiOiJKV1QifQ.eyJpc3MiOiJodHRwczovL2FjY291bnRzLmdvb2dsZS5jb20iLCJhenAiOiIyNzg1OTcxMDc1OTktNG40N2FmY2ZuamJ0dTRzZjE0bzJicDNkN2VvaDJ2cm8uYXBwcy5nb29nbGV1c2VyY29udGVudC5jb20iLCJhdWQiOiIyNzg1OTcxMDc1OTktNG40N2FmY2ZuamJ0dTRzZjE0bzJicDNkN2VvaDJ2cm8uYXBwcy5nb29nbGV1c2VyY29udGVudC5jb20iLCJzdWIiOiIxMDI5ODIzNTc4NDg0MDU2MzU2ODIiLCJhdF9oYXNoIjoiejE4NnhpYzNHU1RrQmpSaUdfWk5QUSIsIm5hbWUiOiLop4bpopHpgJoiLCJwaWN0dXJlIjoiaHR0cHM6Ly9saDMuZ29vZ2xldXNlcmNvbnRlbnQuY29tL2EtL0FPaDE0R2lpU3JlcXBXeHFHREJHQU85OHRQSXk1ZDZxVEhla3lGRUZwc1luPXM5Ni1jIiwiZ2l2ZW5fbmFtZSI6IuinhumikemAmiIsImxvY2FsZSI6InpoLUNOIiwiaWF0IjoxNTg1MTI0MDM5LCJleHAiOjE1ODUxMjc2Mzl9.Q_nupFEzB0fYAI4-Bc9TXdugrqCG9bWIhgvrBhUU72Jqn21Skw6maXZ1aWkXKZGhOGvUakYQWDdIWrXVVpsBomDWjb4jHG3zXYR_enjUkB5pCZp3x7L9ORzw90gK__2CYRF-JitE70YrJtoCGJXqchAxYPzPHn5LJtNoLrpE4lVA58Yfag0yhhX_NxJHVNfF-wO6_qoJ_I2w4vm3SELcrWLncVB13XNZElc7M3MDEDkiXhH1PpIQyk18-EPotaQFQOB67vY0hazCmiBwyXc5s720lIt1U-iblRIq7Kmxc0uOBB7EVwSA_hhPDryQ5dZtlhLiaSlUiiF8l7aloz03bQ","created":1585124039}
        $accessToken = new AccessToken();
        $accessToken->setToken((string)($accessTokenData['access_token'] ?? ''));
        $accessToken->setExpireTime(time() + (int)($accessTokenData['expires_in'] ?? 0));
        $accessToken->setRefreshToken((string)($accessTokenData['refresh_token'] ?? ''));
        $accessToken->setScope(explode(' ', $accessTokenData['scope'] ?? ''));
        $accessToken->setParams($accessTokenData);
        $accessToken->setUserId(''); // 没给
        $this->accessToken = $accessToken;

        return $accessToken;
    }

    /**
     * AccessToken 是否已经过期
     * @return bool
     */
    public function isAccessTokenExpired(): bool
    {
        return $this->lib->isAccessTokenExpired();
    }

    /**
     * 是否能够 RefreshToken
     * @return bool
     */
    public function allowRefreshToken(): bool
    {
        return true;
    }

    /**
     * 刷新 AccessToken
     * @param string $refreshToken
     * @return AccessToken
     */
    public function refreshAccessTokenByClient(string $refreshToken): AccessToken
    {
        // 刷新 token
        $accessTokenData = $this->lib->refreshToken($refreshToken);

        // 写日志
        $this->writeLog("info", "refresh_token：{$refreshToken}\n响应结果：\n" . var_export($accessTokenData, true));

        // 构造数据
        $accessToken = new AccessToken();
        $accessToken->setToken((string)($accessTokenData['access_token'] ?? ''));
        $accessToken->setExpireTime(time() + (int)($accessTokenData['expires_in'] ?? 0));
        $accessToken->setRefreshToken((string)($accessTokenData['refresh_token'] ?? ''));
        $accessToken->setScope(explode(' ', $accessTokenData['scope'] ?? ''));
        $accessToken->setParams(json_decode(json_encode($accessTokenData, JSON_UNESCAPED_UNICODE), true));
        $accessToken->setUserId(''); // 没给

        return $accessToken;
    }

    /**
     * 获取授权用户信息
     * @return UserProfile
     * @throws SocialSdkException
     */
    public function getUserProfile(): UserProfile
    {
        // 校验
        if (empty($this->accessToken) || empty($this->accessToken->getToken())) {
            throw new SocialSdkException('no access token');
        }

        // 获取用户信息
        $ytbOauth = new \Google_Service_Oauth2($this->lib);
        $userData = $ytbOauth->userinfo_v2_me->get();

        // 写日志
        $this->writeLog("info", "获取用户信息成功:\n" . var_export($userData, true));

        // 构造数据
        // {"email":null,"familyName":null,"gender":null,"givenName":"\u89c6\u9891\u901a","hd":null,"id":"102982357848405635682","link":null,"locale":"zh-CN","name":"\u89c6\u9891\u901a","picture":"https:\/\/lh3.googleusercontent.com\/a-\/AOh14GiiSreqpWxqGDBGAO98tPIy5d6qTHekyFEFpsYn","verifiedEmail":null}
        $userProfile = new UserProfile();
        $userProfile->setId((string)($userData->getId() ?: ''));
        $userProfile->setSex((int)$userData->getGender()); // TODO: to check
        $userProfile->setPictureUrl((string)($userData->getPicture() ?: ''));
        $userProfile->setFullName((string)($userData->getName() ?: ''));
        $userProfile->setEmail((string)($userData->getEmail() ?: ''));
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
        // TODO:
        return [];
    }

    /**
     * 视频分享
     * @param VideoShareParams $params
     * @return VideoShareResult
     */
    public function shareVideo(VideoShareParams $params): VideoShareResult
    {
        $youtube = new \Google_Service_YouTube($this->lib);
        $snippet = new \Google_Service_YouTube_VideoSnippet();
        $snippet->setTitle($params->getTitle());
        $snippet->setDescription($params->getDescription());

        // $snippet->setTags($tags);

        // Numeric video category. See
        // https://developers.google.com/youtube/v3/docs/videoCategories/list
        $snippet->setCategoryId('22');

        // Set the video's status to "public". Valid statuses are "public",
        // "private" and "unlisted".
        $status = new \Google_Service_YouTube_VideoStatus();
        $status->privacyStatus = "public";

        // Associate the snippet and status objects with a new video resource.
        $video = new \Google_Service_YouTube_Video();
        $video->setSnippet($snippet);
        $video->setStatus($status);

        // Specify the size of each chunk of data, in bytes. Set a higher value for
        // reliable connection as fewer chunks lead to faster uploads. Set a lower
        // value for better recovery on less reliable connections.
        $chunkSizeBytes = 1 * 1024 * 1024;

        // Setting the defer flag to true tells the client to return a request which can be called
        // with ->execute(); instead of making the API call immediately.
        $this->lib->setDefer(true);

        // Create a request for the API's videos.insert method to create and upload the video.
        $insertRequest = $youtube->videos->insert('status,snippet', $video);

        // Create a MediaFileUpload object for resumable uploads.
        $media = new \Google_Http_MediaFileUpload(
            $this->lib,
            $insertRequest,
            'video/*',
            null,
            true,
            $chunkSizeBytes
        );

        // 禅道3748,设置120秒超时，重试3次
        $opts = array(
            'http' =>
                array(
                    'timeout' => 300,//单位秒
                )
        );
        $context = stream_context_create($opts);
        $cnt = 0;
        $file = '';
        while ($cnt < 3 && ($file = file_get_contents($params->getVideoUrl(), false, $context)) === false) {
            $cnt++;
        }

        $media->setFileSize(strlen($file));

        // Read the media file and upload it chunk by chunk.
        $status = $media->nextChunk($file);
        if (empty($status)) {
            $status = $media->nextChunk($file);
        }
        // If you want to make other calls after the file upload, set setDefer back to false
        $this->lib->setDefer(false);

        // // 处理缩略图
        // if (!empty($params->getThumbnailUrl())) {
        //     $this->setThumbnail((string)$status['id'], $chunkSizeBytes, $opts, $params->getThumbnailUrl());
        // }

        // $status：
        //   array (
        //     'id' => '7UaQiOLbSZw',
        //     'channelId' => 'UC_5kuHThj8mVOl5RNlPUnAQ',
        //     'title' => 'BestQuality hardware manufacturing testing Factory1',
        //     'categoryId' => '22',
        //     'playlistId' => '',
        //   ),

        // 分享链接
        $url = 'https://youtu.be/' . $status['id'];

        // 写日志
        $this->writeLog("info", "分享视频成功:\n" . var_export($status, true));

        // 构造数据
        $result = new VideoShareResult();
        $result->setId((string)($status['id'] ?? ''));
        $result->setTitle((string)($status['snippet']['title'] ?? ''));
        $result->setDescription('');
        $result->setThumbnailUrl('');
        $result->setUrl($url);
        $result->setCreatedTime(time());
        return $result;
    }

}