<?php
declare (strict_types=1);

namespace LotGD\Core;

/**
 * A grouping of navigation actions, like a submenu.
 */
class ActionGroup
{
    private $id;
    private $title;
    private $sortKey;
    private $actions;

    /**
     * Create a new ActionGroup, starting with an empty set of actions.
     * @param string $id Unique identifier for this group, in the vendor/module/group format.
     * @param string $title Title to display to the end user. Empty string means no title.
     * @param string $sortKey Navigation menus are displayed in the order sorted by this string.
     */
    public function __construct(string $id, string $title, string $sortKey)
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
     * menu's sort key is 'A'.
     * @return string
     */
    public function getSortKey(): string
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
}
