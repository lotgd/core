<?php
declare(strict_types=1);

namespace LotGD\Core\Events;

use JetBrains\PhpStorm\ArrayShape;
use LotGD\Core\Models\Character;

/**
 * Class CharacterEventData.
 */
class CharacterEventData extends EventContextData
{
    #[ArrayShape([
        "character" => [
            "type" => Character::class,
            "required" => "bool",
        ],
        "value" => [
            "type" => "mixed",
            "required" => "bool",
        ],
    ])]
    protected static array $argumentConfig = [
        "character" => ["type" => Character::class, "required" => true],
        "value" => ["type" => "mixed", "required" => false],
    ];
}
