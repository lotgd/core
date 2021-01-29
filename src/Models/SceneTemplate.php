<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Exceptions\ClassNotFoundException;
use LotGD\Core\SceneTemplates\SceneTemplateInterface;
use LotGD\Core\Tools\Model\UserAssignable;

/**
 * Class SceneTemplates.
 * @Entity
 * @Table("scene_templates")
 */
class SceneTemplate
{
    use UserAssignable;

    /** @Id @Column(type="string", length=255, unique=True, name="class") */
    protected string $class;

    /** @Column(type="string", length=255, name="module") */
    protected string $module;

    /**
     * @OneToMany(targetEntity="Scene", mappedBy="template")
     * @var Collection<Scene>
     */
    private Collection $owningScenes;

    /**
     * @OneToMany(targetEntity="Viewpoint", mappedBy="template", fetch="EXTRA_LAZY")
     * @var Collection<Viewpoint>
     */
    private Collection $owningViewpoints;

    /**
     * SceneTemplates constructor.
     * @param string $class FQCN of the scene handling class.
     * @param string $module Module from where the class is from.
     * @param bool $userAssignable Set to false to flag the scene as not-assignable for the user.
     * @throws ArgumentException
     * @throws ClassNotFoundException
     */
    public function __construct(string $class, string $module, bool $userAssignable = true)
    {
        if (!\class_exists($class)) {
            throw new ClassNotFoundException("The class {$class} cannot be found.");
        } elseif (\is_a($class, SceneTemplateInterface::class, true) === false) {
            throw new ArgumentException("The given {$class} must implement SceneTemplateInterface");
        }

        $this->class = $class;
        $this->module = $module;
        $this->setUserAssignable($userAssignable);
    }

    /**
     * @return string The class name of the template.
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * Changes whether the template should be able to get manually assigned to a template or not.
     * @param bool $flag
     */
    public function setUserAssignable(bool $flag = true)
    {
        $this->userAssignable = $flag;
    }

    /**
     * @return bool
     */
    public function isUserAssignable(): bool
    {
        return $this->userAssignable;
    }

    /**
     * @return Collection
     */
    public function getOwningScenes(): Collection
    {
        return $this->owningScenes;
    }

    /**
     * @return Collection
     */
    public function getOwningViewpoints(): Collection
    {
        return $this->owningViewpoints;
    }
}
