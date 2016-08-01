<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

/**
 * Interface for the character model and all objects that mimick such a model.
 */
interface CharacterInterface extends FighterInterface
{
    public function getId(): int;
    public function getName(): string;
    public function getDisplayName(): string;
    public function getHealth(): int;
    public function getMaxHealth(): int;
    public function getCharacterViewpoint();
    public function getProperty(string $name, $default = null);
}
