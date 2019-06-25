<?php
declare(strict_types=1);

namespace LotGD\Core\Events;

use LotGD\Core\Models\Character;

/**
 * Class CharacterEventData.
 */
class CharacterEventData extends EventContextData
{
    protected static $argumentConfig = [
        "character" => ["type" => Character::class, "required" => true],
        "value" => ["type" => "mixed", "required" => false],
    ];
}
