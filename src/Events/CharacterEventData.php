<?php

namespace LotGD\Core\Events;


use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Models\Character;

/**
 * Class CharacterEventData
 * @package LotGD\Core\Events
 */
class CharacterEventData extends EventContextData
{
    protected static $argumentConfig = [
        "character" => ["type" => Character::class, "required" => true],
        "value" => ["type" => "mixed", "required" => false]
    ];
}