<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection
};

use LotGD\Core\Tools\Model\{
    Creator, 
    Deletor
};
use LotGD\Core\Exceptions\{
    NoParentSetException,
    ParentAlreadySetException
};

/**
 * Description of Scene
 * @Entity
 * @Table(name="scenes")
 */
class Scene {
    use Creator;
    use Deletor;
    
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column(type="string", length=255) */
    private $title = "";
    /** @Column(type="text") */
    private $description = "";
    /** 
     * @ManyToOne(targetEntity="Scene")
     * @JoinColumn(name="parent", referencedColumnName="id", nullable=true)
     */
    private $parent = NULL;
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
    public function __construct() {
        $this->children = ArrayCollection();
    }
    
    /**
     * Returns primary id
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }
    
    /**
     * Sets scene title
     * @param string $title
     */
    public function setTitle(string $title) {
        $this->title = $title;
    }
    
    /**
     * Returns scene title
     * @return string
     */
    public function getTitle(): string {
        return $this->title;
    }
    
    /**
     * Sets scene description
     * @param string $description
     */
    public function setDescription(string $description) {
        $this->description = $description;
    }
    
    /**
     * Returns scene description
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }
    
    /**
     * Sets or removes the parent of this scene.
     * @param \LotGD\Core\Models\Scene $parent The new parent or NULL
     */
    public function setParent(Scene $parent = NULL) {
        // Get old parent and remove $this from it
        if($this->parent !== NULL) {
            $oldParent = $this->parent;
            $oldParent->_removeChild($this);
            $this->parent = NULL;
        }
        
        // New parent is not NULL
        if($parent !== NULL) {
            $this->parent = $parent;
            $parent->_addChild($this);
        }
    }
    
    /**
     * Returns the parent of this scene
     * @return \LotGD\Core\Models\Scene
     * @throws \LotGD\Core\Exceptions\NoParentSetException
     */
    public function getParent(): Scene {
        if($this->parent === NULL) {
            throw new NoParentSetException("This child does not have a parent set to return. Check with hasParent first.");
        }
        
        return $this->parent;
    }
    
    /**
     * Returns true if this entity has a parent
     * @return bool
     */
    public function hasParent(): bool {
        return !(empty($this->parent));
    }
    
    /**
     * Returns a list of all children registered for this entity
     * @return Collection
     */
    public function getChildren(): Collection {
        return $this->children;
    }
    
    /**
     * Returns true if the number of children registered for this entitiy is > 0
     * @return bool
     */
    public function hasChildren(): bool {
        return count($this->children) > 0 ? true : false;
    }
    
    /**
     * Registers a child for this entity.
     * @param \LotGD\Core\Models\Scene $child
     */
    protected function _addChild(Scene $child) {
        $this->children->add($child);
    }
    
    /**
     * Removes a child from this entity.
     * @param \LotGD\Core\Models\Scene $child
     */
    protected function _removeChild(Scene $child) {
        $this->children->removeElement($child);
    }
}
