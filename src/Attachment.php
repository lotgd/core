<?php
declare(strict_types=1);

namespace LotGD\Core;

use Exception;
use LotGD\Core\Models\Scene;

/**
 * An attachment to a scene. This is desigend to be subclasses by modules to
 * provide functinoality like forms or maybe image attachments to go along with a scene.
 */
abstract class Attachment implements AttachmentInterface
{
    protected string $id;

    /**
     * Construct a new attachment of the given type. Randomly assigns it an ID.
     * @param Game $game
     * @param Scene $scene
     * @throws Exception
     */
    public function __construct(Game $game, Scene $scene)
    {
        $this->id = \bin2hex(\random_bytes(8));
    }

    public function __toString(): string
    {
        return "<Attachment#{$this->id} '{". static::class . "}'>";
    }

    /**
     * Returns an unique identifier for this attachment. Each attachment instance
     * will have its own unique ID, assigned at time of the instantiation.
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}
