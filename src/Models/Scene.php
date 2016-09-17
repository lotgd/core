<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Column;

use LotGD\Core\Tools\Model\Creator;
use LotGD\Core\Tools\Model\Deletor;
use LotGD\Core\Tools\Model\SceneBasics;

/**
 * Description of Scene
 * @Entity
 * @Table(name="scenes")
 */
class Scene implements CreateableInterface
{
    use Creator;
    use Deletor;
    use SceneBasics;

    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;

    /**
     * @ManyToMany(targetEntity="Scene", mappedBy="children", cascade={"persist"})
     */
    private $parents = null;

    /**
     * @ManyToMany(targetEntity="Scene", inversedBy="parents", cascade={"persist", "remove"})
     * @JoinTable(name="paths",
     *      joinColumns={@JoinColumn(name="scene_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="child_scene_id", referencedColumnName="id")}
     *      )
     */
    private $children = [];

    /**
     * @var array
     */
    private static $fillable = [
        "title",
        "description",
        "parents",
        "template"
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->parents = new ArrayCollection();
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
     * Set the parents to the given Collection.
     * @param Collection $parents
     */
    public function setParents(Collection $parents)
    {
        // Super slow, but presumably these are short collections :)
        // We should probably move to a set collection at some point.
        $oldParents = $this->parents;
        $additions = $parents->filter(function($element) use ($oldParents) {
            return !$oldParents->contains($element);
        });
        $removals = $this->parents->filter(function($element) use ($parents) {
            return !$parents->contains($element);
        });

        foreach ($additions as $a) {
            $this->addParent($a);
        }
        foreach ($removals as $r) {
            $this->removeParent($r);
        }

        $this->parents = $parents;
    }

    /**
     * Adds a parent to this scene.
     * @param \LotGD\Core\Models\Scene $parent
     */
    public function addParent(Scene $parent)
    {
        if (!$this->parents->contains($parent)) {
            $this->parents->add($parent);
            $parent->addChild($this);
        }
    }

    /**
     * Removes a parent from this scene.
     * @param Scene $parent
     */
    public function removeParent(Scene $parent)
    {
        $this->parents->removeElement($parent);
        $parent->removeChild($this);
    }

    /**
     * Returns all the possible parents of this scene.
     * @return Collection
     */
    public function getParents(): Collection
    {
        return $this->parents;
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
     * Registers a child for this entity.
     * @param \LotGD\Core\Models\Scene $child
     */
    protected function addChild(Scene $child)
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
        }
    }

    /**
     * Removes a child from this entity.
     * @param \LotGD\Core\Models\Scene $child
     */
    protected function removeChild(Scene $child)
    {
        $this->children->removeElement($child);
    }
}
