<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

# use LotGD\Core\Tools\Optional\Optional;

/**
 *
 */
interface CharacterInterface
{
    public function getId(): int;
    public function getName(): string;
    public function getDisplayName(): string;
    public function getHealth(): int;
    public function getMaxHealth(): int;
    public function getCharacterViewpoint(): CharacterViewpoint;
    public function getProperty(string $name, $default = null);
}
