<?php
declare(strict_types=1);


namespace LotGD\Core\Models;


use LotGD\Core\Exceptions\CharacterStatExistsException;
use LotGD\Core\Exceptions\CharacterStatNotFoundException;
use LotGD\Core\Models\CharacterStats\CharacterStatInterface;

/**
 * Class CharacterStatGroup
 * @package LotGD\Core\Models
 */
class CharacterStatGroup
{
    private $id;
    private $name;
    private $stats = [];
    private $weight;
    private $sorted = true;

    /**
     * CharacterStatGroup constructor.
     * @param string $id
     * @param string $name
     */
    public function __construct(string $id, string $name, int $weight = 0)
    {
        $this->id = $id;
        $this->name = $name;
        $this->weight = $weight;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * @param CharacterStatInterface $characterStat
     * @throws CharacterStatExistsException
     */
    public function addCharacterStat(CharacterStatInterface $characterStat)
    {
        if (isset($this->stats[$characterStat->getId()])) {
            throw new CharacterStatExistsException("There is already a character stat registered to this group with the id {$characterStat->getId()}");
        }

        $this->stats[$characterStat->getId()] = $characterStat;
        $this->sorted = false;
    }

    /**
     * @param string $id
     * @return CharacterStatInterface
     * @throws CharacterStatNotFoundException
     */
    public function getCharacterStat(string $id): CharacterStatInterface
    {
        if (empty($this->stats[$id])) {
            throw new CharacterStatNotFoundException("Character stat with id {$id} not found.");
        }

        return $this->stats[$id];
    }

    /**
     * @param string $id
     * @return bool
     */
    public function hasCharacterStat(string $id): bool
    {
        if (isset($this->stats[$id])) {
            return true;
        }

        return false;
    }

    /**
     * @return \Generator|CharacterStatInterface[]
     */
    public function iterate(): \Generator
    {
        // First, sort stat set by weight if not sorted
        if (!$this->sorted) {
            uasort($this->stats, function (CharacterStatInterface $a, CharacterStatInterface $b) {
                return $a->getWeight() <=> $b->getWeight();
            });
            $this->sorted = true;
        }

        // Now, iterate.
        foreach ($this->stats as $stat) {
            yield $stat;
        }
    }
}