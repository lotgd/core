<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use LotGD\Core\Tools\Model\Properties;

/**
 * Properties for Characters.
 * @Entity
 * @Table(name="game_configuration")
 */
class GameConfigurationElement
{
    use Properties;
}
