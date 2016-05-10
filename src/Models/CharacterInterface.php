<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

# use LotGD\Core\Tools\Optional\Optional;

/**
 * Interface for the character model and all objects that mimick such a model.
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
