<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Client;


use Jcsp\SocialSdk\Contract\UserInterface;
use Jcsp\SocialSdk\Model\OAuthToken;
use Jcsp\SocialSdk\Contract\AuthorizationInterface;
use Jcsp\SocialSdk\Exception\SocialSdkException;
use Jcsp\SocialSdk\Model\AccessToken;
use Jcsp\SocialSdk\Model\AuthConfig;

abstract class OAuth1 extends AbstractClient implements AuthorizationInterface, UserInterface
{
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
     * @throws SocialSdkException
     */
    public function generateAuthUrl(): string
    {
        // 调用接口获取 OAuthToken 信息
        $oauthToken = $this->getOAuthToken();
        $this->writeLog("info", "获取 OAuthToken ：" . var_export($oauthToken, true));

        // 校验
        if (empty($oauthToken->getOauthToken()) || empty($oauthToken->getOauthTokenSecret()) || !$oauthToken->getIsOauthCallbackConfirmed()) {
            throw new SocialSdkException("生成授权链接失败，OAuthToken 信息有误");
        }

        // 缓存 OAuth Token 信息
        $this->cache->set($this->getOAuthTokenCacheKey(), $oauthToken);

        // 生成授权链接
        $authUrl = $this->generateAuthUrlByClient($oauthToken->getOauthToken());
        $this->writeLog("info", "生成授权链接: {$authUrl}");

        return $authUrl;
    }

    /**
     * 获取 oauth token 信息
     * @return OAuthToken
     */
    abstract public function getOAuthToken(): OAuthToken;

    /**
     * 生成授权链接
     * @param string $oauthToken
     * @return string
     */
    abstract public function generateAuthUrlByClient(string $oauthToken): string;

    /**
     * 获取 AccessToken
     * @param array $requestParams
     * @return AccessToken
     * @throws SocialSdkException
     */
    public function getAccessToken(array $requestParams): AccessToken
    {
        // 获取请求参数
        $oauthToken = $requestParams['oauth_token'] ?? '';
        $oauthVerifier = $requestParams['oauth_verifier'] ?? '';

        // 校验参数
        if (empty($oauthToken)) {
            throw new SocialSdkException("请求参数错误，缺少参数 oauth_token");
        }
        if (empty($oauthVerifier)) {
            throw new SocialSdkException("请求参数错误，缺少参数 oauth_verifier");
        }

        // 校验 oauth_token 值是否真实
        $oauthTokenInfoInCache = $this->cache->get($this->getOAuthTokenCacheKey());
        if (empty($oauthTokenInfoInCache)) {
            throw new SocialSdkException("OAuthToken 不存在");
        }
        if (!$oauthTokenInfoInCache instanceof OAuthToken) {
            throw new SocialSdkException("OAuthToken 异常");
        }
        if ($oauthToken != $oauthTokenInfoInCache->getOauthToken()) {
            throw new SocialSdkException("OAuthToken 非法");
        }

        // 调用接口获取 AccessToken
        $accessToken = $this->getAccessTokenByClient($oauthTokenInfoInCache, $oauthVerifier);
        $this->accessToken = $accessToken;

        // 写日志
        $this->writeLog("info", "获取 AccessToken 成功：" . var_export($accessToken, true));

        // 删除缓存中 OauthToken 值
        $this->cache->delete($this->getOAuthTokenCacheKey());

        return $accessToken;
    }

    /**
     * 获取 AccessToken
     * @param OAuthToken $oauthToken
     * @param string $oauthVerifier
     * @return AccessToken
     */
    abstract public function getAccessTokenByClient(OAuthToken $oauthToken, string $oauthVerifier): AccessToken;

    /**
     * 获取 OAuthToken 缓存 key
     * @return string
     */
    private function getOAuthTokenCacheKey(): string
    {
        return static::class . "_oauth1_temp_oauth_token";
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
     * @return AccessToken
     * @throws SocialSdkException
     */
    public function refreshAccessToken(): AccessToken
    {
        throw new SocialSdkException($this->getSocialMediaName() . " does not support refresh access token.");
    }

    /**
     * 写日志
     * @param string $level
     * @param string $content
     * @param string $type
     */
    public function writeLog(string $level, string $content, string $type = ""): void
    {
        // $type 为空的话，使用调用者的函数名
        if (empty($type)) {
            $backtrace = debug_backtrace();
            $type = $backtrace[1]['function'] ?? 'zzz';
            $type = preg_replace("@^((generateAuthUrl)|(getAccessToken)|(refreshAccessToken)).*$@", "$1", $type);
        }
        parent::writeLog($level, $content, $type);
    }

}