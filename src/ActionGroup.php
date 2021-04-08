<?php
declare(strict_types=1);

namespace LotGD\Core;

use LotGD\Core\Models\Viewpoint;

/**
 * A grouping of navigation actions, like a submenu.
 */
class ActionGroup implements \Countable, \Serializable
{
    const DefaultGroup = 'lotgd/core/default';
    const HiddenGroup = 'lotgd/core/hidden';

    /**
     * @var Action[]
     */
    private array $actions;
    private ?Viewpoint $viewpoint = null;

    /**
     * Create a new ActionGroup, starting with an empty set of actions.
     * @param string $id Unique identifier for this group, in the vendor/module/group format.
     * @param string $title Title to display to the end user. Empty string means no title.
     * @param int $sortKey Navigation menus are displayed in the order sorted by this integer.
     */
    public function __construct(
        private string $id,
        private string $title,
        private int $sortKey
    ) {
        $this->actions = [];
    }

    public function serialize()
    {
        return serialize([
            "id" => $this->id,
            "title" => $this->title,
            "actions" => $this->actions,
            "sortKey" => $this->sortKey,
        ]);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->id = $data["id"];
        $this->title = $data["title"];
        $this->actions = $data["actions"];
        $this->sortKey = $data["sortKey"];
    }

    /**
     * @param Viewpoint|null $viewpoint
     */
    public function setViewpoint(?Viewpoint $viewpoint)
    {
        $this->viewpoint = $viewpoint;

        foreach ($this->actions as $action) {
            $action->setViewpoint($viewpoint);
        }
    }

    /**
     * @return Viewpoint|null
     */
    public function getViewpoint(): ?Viewpoint
    {
        return $this->viewpoint;
    }

    /**
     * Returns the number of registered Actions for this group.
     * @return int
     */
    public function count(): int
    {
        return \count($this->actions);
    }

    /**
     * Returns the unique identifier for this group, in the vendor/module/group format.
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Returns the title for this group.
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Returns the rendered title for this group.
     * @return string
     * @throws Exceptions\InsecureTwigTemplateError
     */
    public function getRenderedTitle(): string
    {
        $title = $this->getTitle();
        $sceneRenderer = $this->viewpoint?->getTwigSceneRenderer();

        if ($sceneRenderer) {
            return $sceneRenderer->render($title, $this->viewpoint, ignoreErrors: true);
        } else {
            return $title;
        }
    }

    /**
     * Returns the sort key for this group. The ordering of the groups in the
     * final menu displayed to users will be sorted by this key. The default
     * menu's sort key is '0'.
     * @return int
     */
    public function getSortKey(): int
    {
        return $this->sortKey;
    }

    /**
     * Returns the ordered array of actions.
     * @return Action[]
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Sets the ordered array of actions.
     * @param Action[] $actions
     */
    public function setActions(array $actions)
    {
        foreach ($actions as $action) {
            $action->setViewpoint($this->viewpoint);
        }

        $this->actions = $actions;
    }

    /**
     * Adds a single action to the list of actions.
     * @param Action $action
     */
    public function addAction(Action $action)
    {
        $action->setViewpoint($this->viewpoint);
        $this->actions[] = $action;
    }
}
