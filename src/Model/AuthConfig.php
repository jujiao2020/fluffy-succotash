<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;


class AuthConfig
{
    /**
     * 应用 id
     * @var string
     */
    private $clientId = '';

    /**
     * 应用密钥
     * @var string
     */
    private $clientSecret = '';

    /**
     * 授权回调路径
     * @var string
     */
    private $redirectUrl = '';

    /**
     * 权限列表，格式如：["public", "user", "video"]
     * @var array
     */
    private $scope = [];

    /**
     * 额外参数
     * @var array
     */
    private $options = [];

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     */
    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    /**
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * @param string $clientSecret
     */
    public function setClientSecret(string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    /**
     * @param string $redirectUrl
     */
    public function setRedirectUrl(string $redirectUrl): void
    {
        $this->redirectUrl = $redirectUrl;
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
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }


}