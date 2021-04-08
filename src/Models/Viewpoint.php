<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

use LotGD\Core\Action;
use LotGD\Core\ActionGroup;
use LotGD\Core\Attachment;
use LotGD\Core\AttachmentInterface;
use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Game;
use LotGD\Core\Services\TwigSceneRenderer;
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

    /**
     * @Id
     * @OneToOne(targetEntity="Character", inversedBy="viewpoint", cascade="persist")
     * @JoinColumn(fieldName="owner_id", referencedColumnName="id")
     */
    private Character $owner;

    /**
     * @var ActionGroup[]
     * @Column(type="array")
     */
    private array $actionGroups = [];

    /**
     * @var Attachment[]
     * @Column(type="array")
     */
    private array $attachments = [];

    /** @Column(type="array") */
    private array $data = [];

    /**
     * @ManyToOne(targetEntity="Scene")
     * @JoinColumn(name="scene_id", referencedColumnName="id")
     */
    private ?Scene $scene = null;

    private ?SceneDescription $_description = null;
    private ?TwigSceneRenderer $twigSceneRenderer = null;

    private static array $fillable = [
        "owner",
    ];

    /**
     * Returns the owner.
     * @return Character
     */
    public function getOwner(): Character
    {
        return $this->owner;
    }

    /**
     * Sets the owner.
     * @param Character $owner
     */
    public function setOwner(Character $owner)
    {
        $this->owner = $owner;
    }

    /**
     * @param TwigSceneRenderer $twigSceneRenderer
     */
    public function setTwigSceneRenderer(TwigSceneRenderer $twigSceneRenderer)
    {
        $this->twigSceneRenderer = $twigSceneRenderer;
    }

    /**
     * @return TwigSceneRenderer|null
     */
    public function getTwigSceneRenderer(): ?TwigSceneRenderer
    {
        return $this->twigSceneRenderer;
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
     * Clears the description.
     */
    public function clearDescription(): void
    {
        $this->description = "";
        $this->_description = new SceneDescription("");
    }

    /**
     * Returns the current description as a string.
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Adds a paragraph to the existing description.
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
     * @param Scene $scene
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
            title: $this->getTitle(),
            description: $this->getDescription(),
            template: $this->getTemplate() === null ? null : \get_class($this->getTemplate()),
            actionGroups: $this->getActionGroups(),
            attachments: $this->getAttachments(),
            data: $this->getData()
        );

        return $snapshot;
    }

    /**
     * Changes the current viewpoint to the state saved in the given restoration point.
     * @param ViewpointSnapshot $snapshot
     */
    public function changeFromSnapshot(EntityManager $entityManager, ViewpointSnapshot $snapshot)
    {
        if ($snapshot->getTemplate() !== null) {
            $templateInstance = $entityManager->getRepository(SceneTemplate::class)->find($snapshot->getTemplate());
        } else {
            $templateInstance = null;
        }

        $this->setTitle($snapshot->getTitle());
        $this->setDescription($snapshot->getDescription());
        $this->setTemplate($templateInstance);
        $this->setActionGroups($snapshot->getActionGroups());
        $this->setAttachments($snapshot->getAttachments());
        $this->setData($snapshot->getData());

        foreach ($this->actionGroups as $actionGroup) {
            $actionGroup->setViewpoint($this);
        }
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
     * @return Scene|null
     */
    public function getScene(): ?Scene
    {
        return $this->scene;
    }

    /**
     * Returns all action groups.
     * @return ActionGroup[]
     */
    public function getActionGroups(): array
    {
        return $this->actionGroups;
    }

    /**
     * Sets action groups.
     * @param ActionGroup[] $actionGroups
     */
    public function setActionGroups(array $actionGroups)
    {
        foreach ($actionGroups as $actionGroup) {
            if ($actionGroup instanceof ActionGroup) {
                $actionGroup->setViewpoint($this);
            }
        }
        $this->actionGroups = $actionGroups;
    }

    /**
     * Adds a new action group to a viewpoint.
     * @param ActionGroup $group The new group to add.
     * @param string|null $after Optional group id that comes before.
     * @throws ArgumentException
     */
    public function addActionGroup(ActionGroup $group, ?string $after = null): void
    {
        $groupId = $group->getId();
        if ($this->findActionGroupById($groupId) == true) {
            throw new ArgumentException("Group {$group} is already contained in this viewpoint.");
        }

        foreach ($group->getActions() as $action) {
            $action->setViewpoint($this);
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
     * Returns an action group by id or fails.
     * @param string $actionGroupId
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
                $group->addAction($action);
                break;
            }
        }
        $this->setActionGroups($actionGroups);
    }

    /**
     * Returns all attachments.
     * @return AttachmentInterface[]
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * Sets attachments.
     * @param AttachmentInterface[] $attachments
     */
    public function setAttachments(array $attachments)
    {
        $this->attachments = $attachments;
    }

    /**
     * Adds an attachment
     * @param AttachmentInterface $attachment
     */
    public function addAttachment(AttachmentInterface $attachment)
    {
        $this->attachments[] = $attachment;
    }

    /**
     * Returns all data.
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Sets all data.
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Returns a single data field.
     * @param string $fieldname Fieldname
     * @param mixed $default Default value
     * @return mixed
     */
    public function getDataField(string $fieldname, $default = null)
    {
        return $this->data[$fieldname] ?? $default;
    }

    /**
     * Sets a single data field.
     * @param string $fieldname
     * @param mixed $value
     */
    public function setDataField(string $fieldname, $value)
    {
        $this->data[$fieldname] = $value;
    }

    /**
     * Returns the action that corresponds to the given ID, if present.
     * @param string $id
     * @return Action|null
     */
    public function findActionById(string $id): ?Action
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
    public function removeActionsWithSceneId(string $id)
    {
        foreach ($this->getActionGroups() as $group) {
            $actions = $group->getActions();
            foreach ($actions as $key => $a) {
                if ($a->getDestinationSceneId() == $id) {
                    unset($actions[$key]);
                }
            }
            $actions = \array_values($actions);
            $group->setActions($actions);
        }
    }

    /**
     * Returns the rendered version of the title
     * @return string
     */
    public function getRenderedTitle(): string
    {
        $title = $this->getTitle();

        if ($this->twigSceneRenderer) {
            return $this->twigSceneRenderer->render($title, $this, ignoreErrors: true);
        } else {
            return $title;
        }
    }

    /**
     * Returns the rendered version of the description
     * @return string
     */
    public function getRenderedDescription(): string
    {
        $description = $this->getDescription();

        if ($this->twigSceneRenderer) {
            return $this->twigSceneRenderer->render($description, $this, ignoreErrors: true);
        } else {
            return $description;
    }
    }
}
