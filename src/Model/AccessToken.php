<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;


class AccessToken
{
    /**
     * 用户id
     * @var string
     */
    private $userId = '';

    /**
     * 访问令牌
     * OAuth1.0a、OAuth2.0 的 access token
     * @var string
     */
    private $token = '';

    /**
     * OAuth1.0a 特有的访问令牌秘钥
     * @var string
     */
    private $tokenSecret = '';

    /**
     * 用于刷新访问令牌的令牌
     * oauth2.0 才有
     * @var string
     */
    private $refreshToken = '';

    /**
     * Access Token 过期时间戳，0 为不过期
     * @var int
     */
    private $expireTime = 0;

    /**
     * Refresh Token 过期时间戳，0 为不过期
     * @var int
     */
    private $refreshTokenExpireTime = 0;

    /**
     * 权限列表，格式如：["public", "user", "video"]
     * @var array
     */
    private $scope = [];

    /**
     * 原数据
     * @var array
     */
    private $params = [];

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     */
    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getTokenSecret(): string
    {
        return $this->tokenSecret;
    }

    /**
     * @param string $tokenSecret
     */
    public function setTokenSecret(string $tokenSecret): void
    {
        $this->tokenSecret = $tokenSecret;
    }

    /**
     * @return string
     */
    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    /**
     * @param string $refreshToken
     */
    public function setRefreshToken(string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    /**
     * @return int
     */
    public function getExpireTime(): int
    {
        return $this->expireTime;
    }

    /**
     * @param int $expireTime
     */
    public function setExpireTime(int $expireTime): void
    {
        $this->expireTime = $expireTime;
    }

    /**
     * @return int
     */
    public function getRefreshTokenExpireTime(): int
    {
        return $this->refreshTokenExpireTime;
    }

    /**
     * @param int $refreshTokenExpireTime
     */
    public function setRefreshTokenExpireTime(int $refreshTokenExpireTime): void
    {
        $this->refreshTokenExpireTime = $refreshTokenExpireTime;
    }

    /**
     * @return array
     */
    public function getScope(): array
    {
        return $this->scope;
    }

    /**
     * @param array $scope
     */
    public function setScope(array $scope): void
    {
        $this->scope = $scope;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param array $params
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

}