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
    /**
     * CharacterEventData constructor.
     * @param array $data Must contain field character.
     * @throws ArgumentException
     */
    protected function __construct(array $data)
    {
        $mustHaveForm = ["character"];
        $doesHaveForm = array_keys($data);
        sort($mustHaveForm);
        sort($doesHaveForm);

        if ($doesHaveForm !== $mustHaveForm) {
            throw new ArgumentException("A new CharacterEventData event must have a character data field.");
        }

        if ($data["character"] instanceof Character === false) {
            throw new ArgumentException(sprintf(
                "data[character] must be an instance of %s, %s given.",
                Character::class,
                get_class($data["character"])
            ));
        }

        parent::__construct($data);
    }
}