<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use LotGD\Core\Tools\Model\Creator;
use LotGD\Core\Tools\Model\SceneBasics;

/**
 * A CharacterViewpoint is the current Scene a character is experiencing with
 * all changes from modules included.
 * @Entity
 * @Table(name="character_viewpoints")
 */
class CharacterViewpoint implements CreateableInterface
{
    use Creator;
    use SceneBasics;

    /** @Id @ManyToOne(targetEntity="Character", inversedBy="id", cascade="persist") */
    private $owner;
    /** @Column(type="array") */
    private $actions = [];
    /** @Column(type="array") */
    private $attachments = [];
    /** @Column(type="array") */
    private $data = [];

    /** @var array */
    private static $fillable = [
        "owner"
    ];

    /**
     * Returns the owner
     * @return \LotGD\Core\Models\Character
     */
    public function getOwner(): Character
    {
        return $this->owner;
    }

    /**
     * Sets the owner
     * @param \LotGD\Core\Models\Character $owner
     */
    public function setOwner(Character $owner)
    {
        $this->owner = $owner;
    }

    /**
     * Copies the static data from a scene to this CharacterViewpoint entity
     * @param \LotGD\Core\Models\Scene $scene
     */
    public function changeFromScene(Scene $scene)
    {
        $this->setTitle($scene->getTitle());
        $this->setDescription($scene->getDescription());
        $this->setTemplate($scene->getTemplate());
    }

    /**
     * Returns all actions
     * @return array
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Sets actions
     * @param array $actions
     */
    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }

    /**
     * Returns all data
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Sets all data
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Returns a single data field
     * @param string $fieldname Fieldname
     * @param type $default default value
     * @return mixed
     */
    public function getDataField(string $fieldname, $default = null)
    {
        return $this->data[$fieldname] ?? $default;
    }

    /**
     * Sets a single data field
     * @param string $fieldname
     */
    public function setDataField(string $fieldname, $value)
    {
        $this->data[$fieldname] = $value;
    }

    /**
     * Returns the action that corresponds to the given ID, if present.
     * @return Action|null
     */
    public function findActionById(string $id)
    {
        foreach ($this->getActions() as $group) {
            foreach ($group->getSctions() as $a) {
                if ($a->getId() == $actionId) {
                    return $a;
                }
            }
        }
        return null;
    }
}
