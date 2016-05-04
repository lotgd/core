<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

# use LotGD\Core\Tools\Optional\Optional;

/**
 *
 */
interface CharacterInterface
{
    public function getName(): string;
    public function getDisplayName(): string;
    public function getCharacterViewpoint(): CharacterViewpoint;
    public function getProperty(string $name, $default = null);
}
