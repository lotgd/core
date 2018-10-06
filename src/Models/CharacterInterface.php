<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Ramsey\Uuid\UuidInterface;

/**
 * Interface for the character model and all objects that mimick such a model.
 */
interface CharacterInterface extends FighterInterface
{
    public function getId(): UuidInterface;
    public function getName(): string;
    public function getDisplayName(): string;
    public function getHealth(): int;
    public function getMaxHealth(): int;
    public function getViewpoint();
    public function getProperty(string $name, $default = null);
}
