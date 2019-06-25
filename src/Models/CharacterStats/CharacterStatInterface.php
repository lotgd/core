<?php
declare(strict_types=1);

namespace LotGD\Core\Models\CharacterStats;

interface CharacterStatInterface
{
    /**
     * CharacterStatInterface constructor.
     * @param string $id
     * @param string $name
     * @param $value
     */
    public function __construct(string $id, string $name, $value, int $weight = 0);

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $name
     * @return mixed
     */
    public function setName(string $name);

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @param $value
     * @return mixed
     */
    public function setValue($value);

    /**
     * @return string
     */
    public function getValueAsString(): string;

    /**
     * @return int
     */
    public function getWeight(): int;

    /**
     * @param int $weight
     * @return mixed
     */
    public function setWeight(int $weight);
}
