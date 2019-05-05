<?php
declare(strict_types=1);


namespace LotGD\Core\Models;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Exceptions\ClassNotFoundException;
use LotGD\Core\SceneTemplates\SceneTemplateInterface;


/**
 * Class SceneTemplates
 * @Entity
 * @Table("scene_templates")
 */
class SceneTemplate
{
    /** @Id @Column(type="string", length=255, unique=True, name="class") */
    protected $class;

    /** @Column(type="string", length=255, unique=True, name="id") */
    protected $module;

    /** @Column(type="boolean", options={"default": True}) */
    protected $userAssignable;

    /**
     * SceneTemplates constructor.
     * @param string $class FQCN of the scene handling class.
     * @param string $module Module from where the class is from.
     * @throws ClassNotFoundException
     * @throws ArgumentException
     */
    public function __construct(string $class, string $module)
    {
        if (!class_exists($class)) {
            throw new ClassNotFoundException("The class {$class} cannot be found.");
        } elseif (is_a($class, SceneTemplateInterface::class) === false) {
            throw new ArgumentException("The given {$class} must implement SceneTemplateInterface");
        }

        $this->class = $class;
        $this->module = $module;
    }

    /**
     * Changes whether the template should be able to get manually assigned to a template or not.
     * @param bool $flag
     */
    public function setUserAssignable(bool $flag = true)
    {
        $this->userAssignable=$flag;
    }
}