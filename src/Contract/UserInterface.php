<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Contract;


use Jcsp\SocialSdk\Model\UserProfile;

interface UserInterface extends AuthorizationInterface
{
    /**
     * 获取授权用户信息
     * @return UserProfile
     */
    public function getUserProfile(): UserProfile;

}