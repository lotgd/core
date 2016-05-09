<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Column;

use LotGD\Core\Exceptions\NoParentSetException;
use LotGD\Core\Exceptions\WrongParentException;
use LotGD\Core\Tools\Model\Creator;
use LotGD\Core\Tools\Model\Deletor;
use LotGD\Core\Tools\Model\SceneBasics;

/**
 * Description of Scene
 * @Entity
 * @Table(name="scenes")
 */
class Scene
{
    use Creator;
    use Deletor;
    use SceneBasics;
    
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    
    /**
     * @ManyToOne(targetEntity="Scene")
     * @JoinColumn(name="parent", referencedColumnName="id", nullable=true)
     */
    private $parent = null;
    
    /**
     * @OneToMany(targetEntity="Scene", mappedBy="parent")
     */
    private $children = [];
    
    /**
     * @var array
     */
    private static $fillable = [
        "title",
        "description",
        "parent"
    ];
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = ArrayCollection();
    }
    
    /**
     * Returns primary id
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    
    /**
     * Sets or removes the parent of this scene.
     * @param \LotGD\Core\Models\Scene $parent The new parent or NULL
     */
    public function setParent(Scene $parent = null)
    {
        // Get old parent and remove $this from it
        if ($this->parent !== null) {
            $oldParent = $this->parent;
            $oldParent->removeChild($this);
            $this->parent = null;
        }
        
        // New parent is not null
        if ($parent !== null) {
            $this->parent = $parent;
            $parent->addChild($this);
        }
    }
    
    /**
     * Returns the parent of this scene
     * @return \LotGD\Core\Models\Scene
     * @throws \LotGD\Core\Exceptions\NoParentSetException
     */
    public function getParent(): Scene
    {
        if ($this->parent === null) {
            throw new NoParentSetException("This child does not have a parent set to return. Check with hasParent first.");
        }
        
        return $this->parent;
    }
    
    /**
     * Returns true if this entity has a parent
     * @return bool
     */
    public function hasParent(): bool
    {
        return !(empty($this->parent));
    }
    
    /**
     * Returns a list of all children registered for this entity
     * @return Collection
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }
    
    /**
     * Returns true if the number of children registered for this entitiy is > 0
     * @return bool
     */
    public function hasChildren(): bool
    {
        return count($this->children) > 0 ? true : false;
    }
    
    /**
     * Registers a child for this entity.
     * @param \LotGD\Core\Models\Scene $child
     */
    protected function addChild(Scene $child)
    {
        $this->children->add($child);
    }
    
    /**
     * Removes a child from this entity.
     * @param \LotGD\Core\Models\Scene $child
     * @throws WrongParentException
     */
    protected function removeChild(Scene $child)
    {
        if ($child->getParent() !== $this) {
            throw new WrongParentException("This Scene is not the parent of the given child.");
        }
        
        $this->children->removeElement($child);
    }
}
