<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Contract;


interface UserProfileInterface
{
    public function getId(): string;

    public function getFullName(): string;

    public function getEmail(): string;

    public function getSex(): int;

    public function getBirthday(): int;

    public function getPictureUrl(): string;

}