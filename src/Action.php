<?php
declare(strict_types=1);

namespace LotGD\Core;

/**
 * A representation of an action the user can take to affect the game
 * state. An encapsulation of a navigation menu option.
 */
class Action
{
    protected $id;
    protected $destinationSceneId;

    /**
     * Construct a new action with the specified Scene as its destination.
     * @param int $destinationSceneId
     */
    public function __construct(int $destinationSceneId)
    {
        $this->id = bin2hex(random_bytes(8));
        $this->destinationSceneId = $destinationSceneId;
    }

    /**
     * Returns the unique, automatically generated identifier for this action.
     * Use this ID to refer to this action when calling Game::takeAction().
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Return the database ID of the destination scene, where the user will
     * go if they take this action.
     * @return string
     */
    public function getDestinationSceneId(): int
    {
        return $this->destinationSceneId;
    }
}
