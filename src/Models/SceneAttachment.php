<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;
use LotGD\Core\Attachment;
use LotGD\Core\AttachmentInterface;
use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Tools\Model\UserAssignable;

/**
 * A SceneAttachment is a registered Attachment class to keep track of
 * (a) generally all available attachments, and
 * (b) which scene contains which attachment.
 * @Entity
 * @Table(name="scene_attachments")
 */
class SceneAttachment
{
    use UserAssignable;

    /**
     * @Id
     * @Column(type="string", length=36, unique=True, name="class", options={"fixed"=true})
     */
    protected string $class;

    /** @Column(type="string", length=255) */
    protected string $title;

    /** @ManyToMany(targetEntity="Scene", mappedBy="attachments")  */
    private ?Collection $scenes;

    /**
     * SceneAttachment constructor.
     * @param string $class A class inheriting from AttachmentInterface.
     * @param string $title
     * @param bool $userAssignable
     * @throws ArgumentException if $class does not implement AttachmentInterface
     */
    public function __construct(string $class, string $title, bool $userAssignable = true) {
        if (!is_subclass_of($class, AttachmentInterface::class)) {
            throw new ArgumentException("The class '{$class}' must inherit from " . AttachmentInterface::class);
        }

        $this->class = $class;
        $this->title = $title;
        $this->setUserAssignable($userAssignable);

        $this->scenes = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return Collection
     */
    public function getScenes(): Collection
    {
        return $this->scenes;
    }
}