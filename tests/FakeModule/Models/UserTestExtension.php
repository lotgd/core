<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\FakeModule\Models;

use LotGD\Core\Doctrine\Annotations\Extension;
use LotGD\Core\Doctrine\Annotations\ExtensionMethod;
use LotGD\Core\Models\Character;

/**
 * Class CharacterTestExtension
 * @package LotGD\Core\Tests\FakeModule\Models
 * @Extension(of="LotGD\Core\Tests\FakeModule\Models\UserEntity")
 */
class UserTestExtension
{
    /**
     * @param UserEntity $user
     * @return array
     * @ExtensionMethod(as="getNameAsArray")
     */
    public static function returnNameAsArrayForUser(UserEntity $user): array
    {
        $g = $user->getGame();

        if ($g !== null) {
            return [$user->getName()];
        } else {
            return [];
        }
    }
}