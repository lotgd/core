<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use LotGD\Core\Events\EventContextData;
use LotGD\Core\Exceptions\CharacterStatGroupExistsException;
use LotGD\Core\Exceptions\CharacterStatGroupNotFoundException;
use LotGD\Core\Game;

/**
 * Class CharacterStats.
 */
class CharacterStats
{
    private $game;
    private $character;
    private $stat_groups;
    private $sorted = true;

    /**
     * CharacterStats constructor.
     * @param Game $game
     * @param Character $character
     */
    public function __construct(Game $game, Character $character)
    {
        $this->game = $game;
        $this->character = $character;

        // Hook
        $eventData = $this->game->getEventManager()->publish(
            "h/lotgd/core/characterStats/populate",
            EventContextData::create(["character" => $character, "stats" => $this])
        );
    }

    /**
     * @return CharacterStatGroup[]|\Generator
     */
    public function iterate(): \Generator
    {
        // First, sort stat set by weight if not sorted yet
        if (!$this->sorted) {
            \uasort($this->stat_groups, function (CharacterStatGroup $a, CharacterStatGroup $b) {
                return $a->getWeight() <=> $b->getWeight();
            });
            $this->sorted = true;
        }

        // Now, iterate.
        foreach ($this->stat_groups as $id => $stat_group) {
            yield $stat_group;
        }
    }

    /**
     * @param CharacterStatGroup $group
     * @throws CharacterStatGroupExistsException
     */
    public function addCharacterStatGroup(CharacterStatGroup $group)
    {
        if (isset($this->stat_groups[$group->getId()])) {
            throw new CharacterStatGroupExistsException("Character stat {$group->getId()} already exists.");
        }

        $this->stat_groups[$group->getId()] = $group;
        $this->sorted = false;
    }

    /**
     * @param string $id
     * @throws CharacterStatGroupNotFoundException
     * @return CharacterStatGroup
     */
    public function getCharacterStatGroup(string $id): CharacterStatGroup
    {
        if (empty($this->stat_groups[$id])) {
            throw new CharacterStatGroupNotFoundException("Character stat {$id} does not exists.");
        }

        return $this->stat_groups[$id];
    }

    /**
     * @param string $id
     * @return bool
     */
    public function hasCharacterStatGroup(string $id): bool
    {
        if (isset($this->stat_groups[$id])) {
            return true;
        }

        return false;
    }
}
