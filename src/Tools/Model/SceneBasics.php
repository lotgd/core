<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use LotGD\Core\Models\SceneTemplate;

/**
 * Provides scene basics.
 */
trait SceneBasics
{
    /** @Column(type="string", length=255) */
    private string $title = "{No scene set}";
    /** @Column(type="text") */
    private string $description = "{No scene set}";
    /** @Column(type="string", length=255) */
    /**
     * @ManyToOne(targetEntity="SceneTemplate", fetch="EAGER")
     * @JoinColumn(name="template", referencedColumnName="class", nullable=true)
     */
    private ?SceneTemplate $template;

    /**
     * Sets scene title.
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * Returns scene title.
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets scene description.
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * Returns scene description.
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Sets scene template.
     * @param SceneTemplate|null $template
     */
    public function setTemplate(?SceneTemplate $template)
    {
        $this->template = $template;
    }

    /**
     * Returns scene template.
     * @return SceneTemplate|null
     */
    public function getTemplate(): ?SceneTemplate
    {
        return $this->template;
    }
}
