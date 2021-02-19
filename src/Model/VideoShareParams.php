<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;


class VideoShareParams
{

    /**
     * 社媒昵称，用户名或类别名等
     * @var string
     */
    private $displayName = '';

    /**
     * 用户id或类别id等
     * @var string
     */
    private $socialId = '';

    /**
     * Access Token
     * @var string
     */
    private $accessToken = '';

    /**
     * 视频标题
     * @var string
     */
    private $title = '';

    /**
     * 视频关键词
     * @var string
     */
    private $keywords = '';

    /**
     * 视频描述
     * @var string
     */
    private $description = '';

    /**
     * 视频链接
     * @var string
     */
    private $videoUrl = '';

    /**
     * 视频缩略图
     * @var string
     */
    private $thumbnailUrl = '';

    /**
     * 是否提交到 channel
     * @var bool
     */
    private $isPostToChannel = false;

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     */
    public function setDisplayName(string $displayName): void
    {
        $this->displayName = $displayName;
    }

    /**
     * @return string
     */
    public function getSocialId(): string
    {
        return $this->socialId;
    }

    /**
     * @param string $socialId
     */
    public function setSocialId(string $socialId): void
    {
        $this->socialId = $socialId;
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getKeywords(): string
    {
        return $this->keywords;
    }

    /**
     * @param string $keywords
     */
    public function setKeywords(string $keywords): void
    {
        $this->keywords = $keywords;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getVideoUrl(): string
    {
        return $this->videoUrl;
    }

    /**
     * @param string $videoUrl
     */
    public function setVideoUrl(string $videoUrl): void
    {
        $this->videoUrl = $videoUrl;
    }

    /**
     * @return string
     */
    public function getThumbnailUrl(): string
    {
        return $this->thumbnailUrl;
    }

    /**
     * @param string $thumbnailUrl
     */
    public function setThumbnailUrl(string $thumbnailUrl): void
    {
        $this->thumbnailUrl = $thumbnailUrl;
    }

    /**
     * @return bool
     */
    public function getIsPostToChannel(): bool
    {
        return $this->isPostToChannel;
    }

    /**
     * @param bool $isPostToChannel
     */
    public function setIsPostToChannel(bool $isPostToChannel): void
    {
        $this->isPostToChannel = $isPostToChannel;
    }

}