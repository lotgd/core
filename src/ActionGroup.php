<?php
declare (strict_types=1);

namespace LotGD\Core;

/**
 * A grouping of navigation actions, like a submenu.
 */
class ActionGroup
{
    const DefaultGroup = 'lotgd/core/default';
    const HiddenGroup = 'lotgd/core/hidden';

    private $id;
    private $title;
    private $sortKey;
    private $actions;

    /**
     * Create a new ActionGroup, starting with an empty set of actions.
     * @param string $id Unique identifier for this group, in the vendor/module/group format.
     * @param string $title Title to display to the end user. Empty string means no title.
     * @param int $sortKey Navigation menus are displayed in the order sorted by this integer.
     */
    public function __construct(string $id, string $title, int $sortKey)
    {
        $this->id = $id;
        $this->title = $title;
        $this->sortKey = $sortKey;
        $this->actions = [];
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
     * @return array<Action>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Sets the ordered array of actions.
     * @param array<Action> $actions
     */
    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }

    /**
     * Adds a single action to the list of actions.
     * @param Action $action
     */
    public function addAction(Action $action)
    {
        $this->actions[] = $action;
    }
}
