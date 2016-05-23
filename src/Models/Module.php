<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use Doctrine\ORM\EntityManagerInterface;
use LotGD\Core\Tools\Model\Creator;
use LotGD\Core\Tools\Model\Deletor;

/**
 * An installed module in the system. Note that module metadata is stored in
 * the composer.json for each module.
 * @Entity
 * @Table(name="modules")
 */
class Module implements SaveableInterface
{
    use Creator;
    use Deletor;

    /** @Id @Column(type="string", unique=true); */
    private $library;

    /** @Column(type="datetime") */
    private $createdAt;

    public function __construct(string $library)
    {
        $this->createdAt = new \DateTime();
        $this->library = $library;
    }

    /**
     * Returns the time this module was added to the system.
     * @return DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Returns the library of this module, in the form 'vendor/project-name', usable
     * by the Composer package manager.
     * @return string
     */
    public function getLibrary(): string
    {
        return $this->library;
    }
}
