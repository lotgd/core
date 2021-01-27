<?php
declare(strict_types=1);

namespace LotGD\Core;

use LotGD\Core\Models\Scene;

interface AttachmentInterface
{
    /**
     * AttachmentInterface constructor.
     * @param Game $g Should not be saved internally.
     * @param Scene $scene Should not be saved internally.
     */
    public function __construct(Game $g, Scene $scene);
    public function getId(): string;

    /**
     * Returns an array with attachment-specific fields.
     * @return array
     */
    public function getData(): array;

    /**
     * @return Action[]
     */
    public function getActions(): array;
}