<?php
declare(strict_types=1);

namespace LotGD\Core;

/**
 * An attachment to a scene. This is desigend to be subclasses by modules to
 * provide functinoality like forms or maybe image attachments to go along with a scene.
 */
abstract class Attachment
{
    protected $id;
    protected $type;

    /**
     * Construct a new attachment of the given type. Randomly assigns it an ID.
     * @param string $type Type of this attachment, in the vendor/module/type format.
     * @return Attachment
     */
    public function __construct(string $type)
    {
        $this->id = bin2hex(random_bytes(8));
        $this->type = $type;
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

    /**
     * Returns the type of this attachment, in vendor/module/type format.
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
