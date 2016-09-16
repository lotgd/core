<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use LotGD\Core\Tools\Model\Properties;

/**
 * A place for modules to store per-module private data.
 * @Entity
 * @Table(name="module_properties")
 */
class ModuleProperty
{
    use Properties;

    /** @Id @ManyToOne(targetEntity="Module", inversedBy="properties")
     * @JoinColumn(name="owner", referencedColumnName="library")
     */
    private $owner;

    /**
     * Returns the owner
     * @return \LotGD\Core\Models\Module
     */
    public function getOwner(): Module
    {
        return $this->owner;
    }

    /**
     * Sets the owner
     * @param \LotGD\Core\Models\Module $owner
     */
    public function setOwner(Module $owner)
    {
        $this->owner = $owner;
    }
}
