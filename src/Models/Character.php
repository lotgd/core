<?php

namespace LotGD\Core\Models;

use Doctrine\ORM\Mapping\Entity;

/**
 * Description of Character
 *
 * @Entity
 * @Table(name="Characters")
 */
class Character {
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column(type="string", length=255, unique=true); */
    private $name;
    private $health = 10;
    private $maxhealth = 10;
    private $properties;
}
