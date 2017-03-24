<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

/**
 * Represents a complete set of viewpoint data used to restore a saved viewpoint.
 * @package LotGD\Core\Models
 */
class ViewpointRestorationPoint
{
    private $title;
    private $description;
    private $template;
    private $actionGroups;
    private $attachements;
    private $data;

    /**
     * ViewpointRestorationPoint constructor.
     * @param string $title
     * @param string $description
     * @param string $template
     * @param array $actionGroups
     * @param array $attachements
     * @param array $data
     */
    public function __construct(
        string $title,
        string $description,
        string $template,
        array $actionGroups,
        array $attachements,
        array $data
    )
    {
        $this->title = $title;
        $this->description = $description;
        $this->template = $template;
        $this->actionGroups = $actionGroups;
        $this->attachements = $attachements;
        $this->data = $data;
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
     * @return string
     */
    public function getTemplate(): string
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
        return $this->attachements;
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