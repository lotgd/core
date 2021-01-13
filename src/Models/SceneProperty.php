<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use LotGD\Core\Tools\Model\Properties;
use Ramsey\Uuid\Uuid;

/**
 * A place for modules to store per-module private data.
 * @Entity
 * @Table(name="scene_properties")
 */
class SceneProperty
{
    use Properties;

    /** @Id @ManyToOne(targetEntity="Scene", inversedBy="properties")
     * @JoinColumn(name="owner", referencedColumnName="id")
     */
    private Scene $owner;

    /**
     * Returns the owner.
     * @return Scene
     */
    public function getOwner(): Scene
    {
        return $this->owner;
    }

    /**
     * Sets the owner.
     * @param Scene $owner
     */
    public function setOwner(Scene $owner)
    {
        $this->owner = $owner;
    }
}
