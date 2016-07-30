<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

/**
 * Provides scene basics
 */
trait SceneBasics
{
    /** @Column(type="string", length=255) */
    private $title = "{No scene set}";
    /** @Column(type="text") */
    private $description = "{No scene set}";
    /** @Column(type="string", length=255) */
    private $template = "{No template set}";

    /**
     * Sets scene title
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * Returns scene title
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets scene description
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * Returns scene description
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Sets scene template
     * @param string $template
     */
    public function setTemplate(string $template)
    {
        $this->template = $template;
    }

    /**
     * Returns scene template
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }
}
