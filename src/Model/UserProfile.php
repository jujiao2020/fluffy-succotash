<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Model;


use Jcsp\SocialSdk\Contract\UserProfileInterface;

class UserProfile implements UserProfileInterface
{
    // 性别常量
    const SEX_UNKNOWN = 0; // 未知
    const SEX_MALE = 1; // 男
    const SEX_FEMALE = 2; // 女

    /**
     * 用户id
     * @var string
     */
    private $id = '';

    /**
     * 用户姓名
     * @var string
     */
    private $fullName = '';

    /**
     * 邮箱
     * @var string
     */
    private $email = '';

    /**
     * 生日
     * @var int
     */
    private $birthday = 0;

    /**
     * 性别（0:未知 1:男 2:女）
     * @var int
     */
    private $sex = 0;

    /**
     * 图像url
     * @var string
     */
    private $pictureUrl = '';

    /**
     * 访问链接
     * @var string
     */
    private $link = '';

    /**
     * 原数据
     * @var array
     */
    private $params = [];

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
    public function getFullName(): string
    {
        return $this->fullName;
    }

    /**
     * @param string $fullName
     */
    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return int
     */
    public function getBirthday(): int
    {
        return $this->birthday;
    }

    /**
     * @param int $birthday
     */
    public function setBirthday(int $birthday): void
    {
        $this->birthday = $birthday;
    }

    /**
     * @return int
     */
    public function getSex(): int
    {
        return $this->sex;
    }

    /**
     * @param int $sex
     */
    public function setSex(int $sex): void
    {
        $this->sex = $sex;
    }

    /**
     * @return string
     */
    public function getPictureUrl(): string
    {
        return $this->pictureUrl;
    }

    /**
     * @param string $pictureUrl
     */
    public function setPictureUrl(string $pictureUrl): void
    {
        $this->pictureUrl = $pictureUrl;
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink(string $link): void
    {
        $this->link = $link;
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