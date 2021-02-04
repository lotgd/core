<?php
declare(strict_types=1);

namespace LotGD\Core;

/**
 * A representation of an action the user can take to affect the game
 * state. An encapsulation of a navigation menu option.
 */
class Action
{
    protected string $id;

    /**
     * Construct a new action with the specified Scene as its destination.
     * @param string $destinationSceneId
     * @param string|null $title
     * @param array $parameters
     */
    public function __construct(
        protected string $destinationSceneId,
        protected ?string $title = null,
        protected array $parameters = []
    ) {
        $this->id = \bin2hex(\random_bytes(8));
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
    public function getDestinationSceneId(): string
    {
        return $this->destinationSceneId;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * Returns all parameters for this action.
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Sets all parameters for this action.
     * @param array $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }
}
