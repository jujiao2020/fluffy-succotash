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

class Pinterest extends OAuth2 implements ShareInterface
{
    /**
     * sdk
     * @var \DirkGroenen\Pinterest\Pinterest
     */
    private $lib;

    /**
     * 默认权限
     * @var array
     */
    private $defaultScope = [
        'read_public',
        'write_public',
        'read_relationships',
        'write_relationships'
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
        $this->lib = new \DirkGroenen\Pinterest\Pinterest($config->getClientId(), $config->getClientSecret());
        if (!is_null($token)) {
            $this->lib->auth->setOAuthToken($token->getToken());
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
        $this->lib->auth->setState($state);
        return $this->lib->auth->getLoginUrl($this->authConfig->getRedirectUrl(), $this->authConfig->getScope());
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
        $token = $this->lib->auth->getOAuthToken($code);

        // 写日志
        $this->writeLog("info", "code：{$code}\n响应结果：\n" . var_export($token, true));

        // 构造数据
        $accessToken = new AccessToken();
        $accessToken->setToken((string)($token->access_token ?? ''));
        $accessToken->setExpireTime(0); // access token 不过期
        $accessToken->setRefreshToken(''); // access token 不过期
        $accessToken->setScope((array)($token->scope ?? ''));
        $accessToken->setParams(json_decode(json_encode($token, JSON_UNESCAPED_UNICODE), true));
        $accessToken->setUserId(""); // 没给

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
        // Pinterest 没有提供刷新 access token 的接口，而且 Pinterest 的 access token 是不过期的
        throw new SocialSdkException("no need to refresh token for pinterest.");
    }

    /**
     * 获取授权用户信息
     * @return UserProfile
     */
    public function getUserProfile(): UserProfile
    {
        // 调用接口
        /** @var \DirkGroenen\Pinterest\Models\User $user */
        $user = $this->lib->users->me();

        // 写日志
        $this->writeLog("info", "获取用户信息成功:\n" . var_export($user, true));

        // 构造数据
        $userProfile = new UserProfile();
        $userProfile->setId((string)($user->id ?? ''));
        $userProfile->setFullName($user->username ?? ((string)($user->first_name ?? '') . (string)($user->last_name ?? '')));
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
        // 获取所有 board
        $boards = $this->lib->users->getMeBoards()->all();

        // 写日志
        $this->writeLog("info", "获取 board 列表数据成功:\n" . var_export($boards, true));

        // 组装数据
        $channelList = [];
        /** @var \DirkGroenen\Pinterest\Models\Board $board */
        foreach ($boards as $board) {
            $channel = new Channel();
            $channel->setId((string)($board->id ?? ''));
            $channel->setName((string)($board->name ?? ''));
            $channel->setUrl((string)($board->url ?? ''));
            $channel->setParams(json_decode(json_encode($board, JSON_UNESCAPED_UNICODE), true));
            $channelList[] = $channel;
        }
        return $boards;
    }

    /**
     * 视频分享
     * @param VideoShareParams $params
     * @return VideoShareResult
     * @throws \DirkGroenen\Pinterest\Exceptions\PinterestException
     */
    public function shareVideo(VideoShareParams $params): VideoShareResult
    {
        // 发布视频
        $pin = $this->lib->pins->create(array(
            "note" => $params->getTitle(),
            "image_url" => $params->getThumbnailUrl(),
            "board" => $params->getDisplayName(), // 这种格式： <user_name>/<board_name>
            "link" => $params->getVideoUrl(),
        ));

        // 写日志
        $this->writeLog("info", "分享视频成功:\n" . var_export($pin, true));

        // 返回
        $result = new VideoShareResult();
        $result->setId((string)($pin->id ?? ''));
        $result->setTitle((string)($pin->note ?? ''));
        $result->setDescription((string)($pin->note ?? ''));
        $result->setThumbnailUrl('');
        $result->setUrl((string)($pin->url ?? ''));
        $result->setCreatedTime(time());
        return $result;
    }

}