<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use LotGD\Core\Tools\Model\Properties;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

/**
 * Properties for Characters
 * @Entity
 * @Table(name="game_configuration")
 */
class GameConfigurationElement
{
    use Properties;
}
