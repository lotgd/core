<?php
declare(strict_types=1);


namespace LotGD\Core\Models\CharacterStats;


/**
 * Class BaseCharacterStat
 * @package LotGD\Core\Models\CharacterStats
 */
class BaseCharacterStat implements CharacterStatInterface
{
    private $id;
    private $name;
    private $value;
    private $weight;

    /**
     * BaseCharacterStat constructor.
     * @param string $id
     * @param string $name
     * @param $value
     */
    public function __construct(string $id, string $name, $value, int $weight = 0)
    {
        $this->id = $id;
        $this->name = $name;
        $this->value = $value;
        $this->weight = $weight;
    }

    /** @inheritdoc */
    public function getId(): string
    {
        return $this->id;
    }

    /** @inheritdoc */
    public function getName(): string
    {
        return $this->name;
    }

    /** @inheritdoc */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /** @inheritdoc */
    public function getValue()
    {
        return $this->value;
    }

    /** @inheritdoc */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /** @inheritdoc */
    public function getValueAsString(): string
    {
        return sprintf("%s", $this->getValue());
    }

    /** @inheritdoc */
    public function setWeight(int $weight)
    {
        $this->weight = $weight;
    }

    /** @inheritdoc */
    public function getWeight(): int
    {
        return $this->weight;
    }
}