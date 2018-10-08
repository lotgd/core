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
    protected $title = null;
    protected $parameters = [];

    /**
     * Construct a new action with the specified Scene as its destination.
     * @param int $destinationSceneId
     * @param string|null $title
     * @param array $parameters
     */
    public function __construct(string $destinationSceneId, ?string $title = null, array $parameters = [])
    {
        $this->id = bin2hex(random_bytes(8));
        $this->destinationSceneId = $destinationSceneId;
        $this->title = $title;
        $this->parameters = $parameters;
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
     * @return int
     */
    public function getDestinationSceneId(): string
    {
        return $this->destinationSceneId;
    }

    /**
     * @return null|string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return null|string
     */
    public function setTitle(?string $title)
    {
        $this->title = $title;
    }

    /**
     * Returns all parameters for this action
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Sets all parameters for this action
     * @param array $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }
}
