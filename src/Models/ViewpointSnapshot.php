<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

/**
 * Represents a complete set of viewpoint data used to restore a saved viewpoint.
 */
class ViewpointSnapshot
{
    /**
     * ViewpointRestorationPoint constructor.
     * @param string $title
     * @param string $description
     * @param string|null $template
     * @param array $actionGroups
     * @param array $attachments
     * @param array $data
     */
    public function __construct(
        private string $title,
        private string $description,
        private ?string $template,
        private array $actionGroups,
        private array $attachments,
        private array $data,
    ) {
    }

    /**
     * Title of the viewpoint.
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Description of the viewpoint.
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Template of the viewpoint.
     * @return string|null
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * Action groups of the viewpoint.
     * @return array
     */
    public function getActionGroups(): array
    {
        return $this->actionGroups;
    }

    /**
     * Attachements of the viewpoint.
     * @return array
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * Date of the viewpoint.
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
