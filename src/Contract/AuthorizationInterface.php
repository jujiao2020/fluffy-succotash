<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Contract;

use Jcsp\SocialSdk\Model\AccessToken;
use Jcsp\SocialSdk\Model\AuthConfig;

interface AuthorizationInterface
{
    /**
     * 授权相关的初始化
     * @param AuthConfig $config
     * @param AccessToken|null $token
     */
    public function initAuth(AuthConfig $config, ?AccessToken $token = null): void;

    /**
     * 生成授权链接
     * @return string
     */
    public function generateAuthUrl(): string;

    /**
     * 获取 AccessToken
     * @param array $requestParams
     * @return AccessToken
     */
    public function getAccessToken(array $requestParams): AccessToken;

    /**
     * AccessToken 是否已经过期
     * @return bool
     */
    public function isAccessTokenExpired(): bool;

    /**
     * 是否能够 RefreshToken
     * @return bool
     */
    public function allowRefreshToken(): bool;

    /**
     * 刷新 AccessToken
     * @return AccessToken
     */
    public function refreshAccessToken(): AccessToken;

}