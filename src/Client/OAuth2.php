<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Client;


use Jcsp\SocialSdk\Contract\AuthorizationInterface;
use Jcsp\SocialSdk\Contract\UserInterface;
use Jcsp\SocialSdk\Exception\SocialSdkException;
use Jcsp\SocialSdk\Model\AccessToken;
use Jcsp\SocialSdk\Model\AuthConfig;
use Jcsp\SocialSdk\Util\UrlUtil;

abstract class OAuth2 extends AbstractClient implements AuthorizationInterface, UserInterface
{
    // 授权模式
    /** @var int 授权码模式 */
    const AUTH_MODE_AUTHORIZATION_CODE = 1;
    /** @var int 隐式模式 */
    const AUTH_MODE_IMPLICIT = 2;

    /**
     * 授权方式
     * @var int
     */
    protected $authMode = self::AUTH_MODE_AUTHORIZATION_CODE;

    /**
     * 授权配置
     * @var AuthConfig
     */
    protected $authConfig;

    /**
     * Access Token
     * @var AccessToken|null
     */
    protected $accessToken;

    /**
     * 授权相关的初始化
     * @param AuthConfig $config
     * @param AccessToken|null $token
     */
    public function initAuth(AuthConfig $config, ?AccessToken $token = null): void
    {
        $this->authConfig = $config;
        $this->accessToken = $token;
        $this->initAuthClient($config, $token);
    }

    /**
     * 初始化授权客户端
     * @param AuthConfig $config
     * @param AccessToken|null $token
     */
    abstract protected function initAuthClient(AuthConfig $config, ?AccessToken $token = null): void;

    /**
     * 生成授权链接
     * @return string
     */
    public function generateAuthUrl(): string
    {
        // 调用客户端方法生成授权链接
        $authUrl = $this->generateAuthUrlByClient();

        // 分析授权链接，取出 state 值，放入缓存
        $query = UrlUtil::parseUrlQuery($authUrl);
        $state = $query['state'] ?? '';
        $this->cache->set($this->getStateCacheKey(), $state);

        // 写日志
        $this->writeLog("info", "生成授权链接: {$authUrl}");

        return $authUrl;
    }

    /**
     * 生成授权链接
     * @return string
     */
    abstract public function generateAuthUrlByClient(): string;

    /**
     * 获取 OAuthToken 缓存 key
     * @return string
     */
    private function getStateCacheKey(): string
    {
        return static::class . "_oauth2_state";
    }

    /**
     * 获取 AccessToken
     * @param array $requestParams
     * @return AccessToken
     * @throws SocialSdkException
     */
    public function getAccessToken(array $requestParams): AccessToken
    {
        if ($this->authMode == self::AUTH_MODE_AUTHORIZATION_CODE) {
            return $this->getAccessTokenInAuthorizationCodeMode($requestParams);
        } elseif ($this->authMode == self::AUTH_MODE_IMPLICIT) {
            return $this->getAccessTokenInImplicitMode($requestParams);
        }
        throw new SocialSdkException("获取授权码模式异常");
    }

    /**
     * 授权码模式下获取 AccessToken
     * @param array $requestParams
     * @return AccessToken
     * @throws SocialSdkException
     */
    private function getAccessTokenInAuthorizationCodeMode(array $requestParams): AccessToken
    {
        // 获取请求参数
        $state = $requestParams['state'] ?? '';
        $code = $requestParams['code'] ?? '';
        $error = $requestParams['error'] ?? '';

        // 校验参数
        if (!empty($error)) {
            throw new SocialSdkException($error ?: "发生错误");
        }
        if (empty($state)) {
            throw new SocialSdkException("请求参数错误，缺少参数 state");
        }
        if (empty($code)) {
            throw new SocialSdkException("请求参数错误，缺少参数 code");
        }

        // 校验 state 值是否真实
        $stateInCache = $this->cache->get($this->getStateCacheKey());
        if ($state != $stateInCache) {
            throw new SocialSdkException("state 值非法");
        }

        // 调用客户端接口获取 Access Token
        $accessToken = $this->getAccessTokenInAuthorizationCodeModeByClient($code, $state);

        // if (isset($response['body']['error'])) {
        //     throw new SocialSdkException($response['body']['error_description'] ?? $response['body']['error']);
        // }

        // 写日志
        $this->writeLog("info", "获取 AccessToken 成功：" . json_encode($accessToken, JSON_UNESCAPED_UNICODE));

        // 删除 state 值
        $this->cache->delete($this->getStateCacheKey());

        return $accessToken;
    }

    /**
     * 获取 AccessToken
     * @param string $code
     * @param string $state
     * @return AccessToken
     */
    abstract public function getAccessTokenInAuthorizationCodeModeByClient(string $code, string $state): AccessToken;

    /**
     * 隐式模式下获取 AccessToken
     * @param array $requestParams
     * @return AccessToken
     * @throws SocialSdkException
     */
    private function getAccessTokenInImplicitMode(array $requestParams): AccessToken
    {
        // https://oauth.vk.com/blank.html#access_token=4e02d5b637b3e01c77a1aae226993dcb5b3237d5f6b5e221746da5483f1829dcc17e7487666ea0cc&expires_in=0&user_id=593604306&state=5ee7448dabccc

        // 获取请求参数
        $state = (string)($requestParams['state'] ?? '');
        $token = (string)($requestParams['access_token'] ?? '');
        $expiresIn = (int)($requestParams['expires_in'] ?? 0);
        $userId = (string)($requestParams['user_id'] ?? '');
        $error = (string)($requestParams['error'] ?? '');

        // 校验参数
        if (!empty($error)) {
            throw new SocialSdkException($error ?: "发生错误");
        }
        if (empty($state)) {
            throw new SocialSdkException("请求参数错误，缺少参数 state");
        }
        // if (empty($userId)) {
        //     throw new SocialSdkException("请求参数错误，缺少参数 userId");
        // }

        // 校验 state 值是否真实
        $stateInCache = $this->cache->get($this->getStateCacheKey());
        if ($state != $stateInCache) {
            throw new SocialSdkException("state 值非法");
        }

        // 构造数据
        $accessToken = new AccessToken();
        $accessToken->setToken($token);
        $accessToken->setExpireTime($expiresIn);
        $accessToken->setRefreshToken('');
        $accessToken->setScope([]);
        $accessToken->setParams($requestParams);
        $accessToken->setUserId($userId);
        $this->accessToken = $accessToken;

        // 写日志
        $this->writeLog("info", "获取 AccessToken 成功：" . json_encode($accessToken, JSON_UNESCAPED_UNICODE));

        // 删除 state 值
        $this->cache->delete($this->getStateCacheKey());

        return $accessToken;
    }

    /**
     * 刷新 AccessToken
     * @return AccessToken
     * @throws SocialSdkException
     */
    public function refreshAccessToken(): AccessToken
    {
        // 校验
        if (empty($this->accessToken)) {
            throw new SocialSdkException("AccessToken 不能为空");
        }

        // 刷新 token
        $accessToken = $this->refreshAccessTokenByClient($this->accessToken->getRefreshToken());
        $this->accessToken = $accessToken;

        // 写日志
        $this->writeLog("info", "刷新 AccessToken（refresh_token: {$this->accessToken->getRefreshToken()}），access_token:\n" .
            var_export($accessToken, true));

        return $accessToken;
    }

    /**
     * 刷新 AccessToken
     * @param string $refreshToken
     * @return AccessToken
     */
    abstract public function refreshAccessTokenByClient(string $refreshToken): AccessToken;

}