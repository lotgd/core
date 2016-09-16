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

    /** @Id @OneToOne(targetEntity="Character", inversedBy="viewpoint", cascade="persist") */
    private $owner;
    /** @Column(type="array") */
    private $actionGroups = [];
    /** @Column(type="array") */
    private $attachments = [];
    /** @Column(type="array") */
    private $data = [];
    /** @ManyToOne(targetEntity="Scene") */
    private $scene;

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
        $this->setScene($scene);

        $this->setActionGroups([]);
        $this->setAttachments([]);
        $this->setData([]);
    }

    /**
     * Sets the template scene used to create this viewpoint.
     * @param Scene $scene
     */
    public function setScene(Scene $scene)
    {
        $this->scene = $scene;
    }

    /**
     * Returns the template scene used to create this viewpoint.
     * @return Scene
     */
    public function getScene()
    {
        return $this->scene;
    }

    /**
     * Returns all action groups.
     * @return array
     */
    public function getActionGroups(): array
    {
        return $this->actionGroups;
    }

    /**
     * Sets action groups.
     * @param array $actionGroups
     */
    public function setActionGroups(array $actionGroups)
    {
        $this->actionGroups = $actionGroups;
    }

    /**
     * Returns all attachments.
     * @return array
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * Sets attachments.
     * @param array $attachments
     */
    public function setAttachments(array $attachments)
    {
        $this->attachments = $attachments;
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
        foreach ($this->getActionGroups() as $group) {
            foreach ($group->getActions() as $a) {
                if ($a->getId() == $id) {
                    return $a;
                }
            }
        }
        return null;
    }
}
