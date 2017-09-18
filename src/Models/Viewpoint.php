<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use LotGD\Core\Action;
use LotGD\Core\ActionGroup;
use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Tools\Model\Creator;
use LotGD\Core\Tools\Model\SceneBasics;
use LotGD\Core\Tools\SceneDescription;

/**
 * A Viewpoint is the current Scene a character is experiencing with
 * all changes from modules included.
 * @Entity
 * @Table(name="viewpoints")
 */
class Viewpoint implements CreateableInterface
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

    /** @var SceneDescription */
    private $_description;

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
     * Sets the description of this viewpoint.
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
        $this->_description = new SceneDescription($description);
    }

    /**
     * Returns the current description as a string
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Adds a paragraph to the existing description
     * @param string $paragraph
     */
    public function addDescriptionParagraph(string $paragraph)
    {
        if ($this->_description === null) {
            $this->_description = new SceneDescription($this->description);
        }

        $this->_description->addParagraph($paragraph);
        $this->description = (string)$this->_description;
    }

    /**
     * Copies the static data from a scene to this Viewpoint entity.
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
     * Returns a restoration point that can be used to reconstruct the current viewpoint.
     * @return ViewpointSnapshot
     */
    public function getSnapshot(): ViewpointSnapshot
    {
        $snapshot = new ViewpointSnapshot(
            $this->getTitle(),
            $this->getDescription(),
            $this->getTemplate(),
            $this->getActionGroups(),
            $this->getAttachments(),
            $this->getData()
        );

        return $snapshot;
    }

    /**
     * Changes the current viewpoint to the state saved in the given restoration point.
     * @param ViewpointSnapshot $snapshot
     */
    public function changeFromSnapshot(ViewpointSnapshot $snapshot)
    {
        $this->setTitle($snapshot->getTitle());
        $this->setDescription($snapshot->getDescription());
        $this->setTemplate($snapshot->getTemplate());
        $this->setActionGroups($snapshot->getActionGroups());
        $this->setAttachments($snapshot->getAttachments());
        $this->setData($snapshot->getData());
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
     * Adds a new action group to a viewpoint
     * @param ActionGroup $group The new group to add.
     * @param null|string $after, optional group id that comes before.
     * @throws ArgumentException
     */
    public function addActionGroup(ActionGroup $group, ?string $after = null): void
    {
        $groupid = $group->getId();
        if ($this->findActionGroupById($groupid) == true) {
            throw new ArgumentException("Group {$group} is already contained in this viewpoint.");
        }

        if ($after === null) {
            $this->actionGroups[] = $group;
        } else {
            $groups = [];
            foreach ($this->actionGroups as $g) {
                if ($g->getId() == $after) {
                    $groups[] = $group;
                }
                $groups[] = $g;
            }
            $this->actionGroups = $groups;
        }
    }

    /**
     * Finds an action group by id.
     * @param $actionGroupId
     * @return ActionGroup|null
     */
    public function findActionGroupById(string $actionGroupId): ?ActionGroup
    {
        $groups = $this->getActionGroups();
        foreach ($groups as $g) {
            if ($g->getId() == $actionGroupId) {
                return $g;
            }
        }
        return null;
    }

    /**
     * Checks if the viewpoint has a certain action group.
     * @param string $actionGroupId
     * @return bool
     */
    public function hasActionGroup(string $actionGroupId): bool
    {
        $groups = $this->getActionGroups();
        foreach ($groups as $g) {
            if ($g->getId() == $actionGroupId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add the specified action to the group with the provided id. Does nothing
     * if the id is not present.
     * @param Action $action
     * @param string $actionGroupId
     */
    public function addActionToGroupId(Action $action, string $actionGroupId)
    {
        $actionGroups = $this->getActionGroups();
        foreach ($actionGroups as $group) {
            if ($group->getId() == $actionGroupId) {
                $actions = $group->getActions();
                $actions[] = $action;
                $group->setActions($actions);
                break;
            }
        }
        $this->setActionGroups($actionGroups);
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

    /**
     * Removes any actions that correspond to a given scene ID, if present.
     * @param int $id
     */
    public function removeActionsWithSceneId(int $id)
    {
        foreach ($this->getActionGroups() as $group) {
            $actions = $group->getActions();
            foreach ($actions as $key => $a) {
                if ($a->getDestinationSceneId() == $id) {
                    unset($actions[$key]);
                }
            }
            $actions = array_values($actions);
            $group->setActions($actions);
        }
    }
}
