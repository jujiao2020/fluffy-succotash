<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;


class OAuthToken
{
    /**
     * @var string
     */
    private $oauthToken = '';

    /**
     * @var string
     */
    private $oauthTokenSecret = '';

    /**
     * @var bool
     */
    private $oauthCallbackConfirmed = false;

    /**
     * @return string
     */
    public function getOauthToken(): string
    {
        return $this->oauthToken;
    }

    /**
     * @param string $oauthToken
     */
    public function setOauthToken(string $oauthToken): void
    {
        $this->oauthToken = $oauthToken;
    }

    /**
     * @return string
     */
    public function getOauthTokenSecret(): string
    {
        return $this->oauthTokenSecret;
    }

    /**
     * @param string $oauthTokenSecret
     */
    public function setOauthTokenSecret(string $oauthTokenSecret): void
    {
        $this->oauthTokenSecret = $oauthTokenSecret;
    }

    /**
     * @return bool
     */
    public function getIsOauthCallbackConfirmed(): bool
    {
        return $this->oauthCallbackConfirmed;
    }

    /**
     * @param bool $oauthCallbackConfirmed
     */
    public function setOauthCallbackConfirmed(bool $oauthCallbackConfirmed): void
    {
        $this->oauthCallbackConfirmed = $oauthCallbackConfirmed;
    }

}