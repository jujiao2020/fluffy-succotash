<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;


class VideoShareResult
{
    /**
     * 视频id
     * @var string
     */
    private $id = '';

    /**
     * 视频链接
     * @var string
     */
    private $url = '';

    /**
     * 视频标题
     * @var string
     */
    private $title = '';

    /**
     * 视频描述
     * @var string
     */
    private $description = '';

    /**
     * 视频缩略图
     * @var string
     */
    private $thumbnailUrl = '';

    /**
     * 创建时间
     * @var int
     */
    private $createdTime = '';

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
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
     * @return int
     */
    public function getCreatedTime(): int
    {
        return $this->createdTime;
    }

    /**
     * @param int $createdTime
     */
    public function setCreatedTime(int $createdTime): void
    {
        $this->createdTime = $createdTime;
    }

}