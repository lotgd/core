<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\FakeModule\Models;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use LotGD\Core\Game;
use LotGD\Core\GameAwareInterface;
use LotGD\Core\Models\ExtendableModelInterface;
use LotGD\Core\Tools\Model\ExtendableModel;
use LotGD\Core\Tools\Model\GameAware;

/**
 * @Entity
 * @Table(name="Users")
 */
class UserEntity implements GameAwareInterface, ExtendableModelInterface
{
    use GameAware;
    use ExtendableModel;

    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column(type="string", length=50); */
    private $name;
    
    public function getId(): int
    {
        return $this->id;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function returnGame(): Game
    {
        return $this->getGame();
    }
}