<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;


class SimulateAccount
{
    /**
     * @var string
     */
    private $user = '';

    /**
     * @var string
     */
    private $media = '';

    /**
     * @var string
     */
    private $channelUrl = '';

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     */
    public function setUser(string $user): void
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getMedia(): string
    {
        return $this->media;
    }

    /**
     * @param string $media
     */
    public function setMedia(string $media): void
    {
        $this->media = $media;
    }

    /**
     * @return string
     */
    public function getChannelUrl(): string
    {
        return $this->channelUrl;
    }

    /**
     * @param string $channelUrl
     */
    public function setChannelUrl(string $channelUrl): void
    {
        $this->channelUrl = $channelUrl;
    }

}