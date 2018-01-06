<?php
declare(strict_types=1);

namespace LotGD\Core\Doctrine\Annotations;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\Attribute;
use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Models\ExtendableModelInterface;

/**
 * Annotation that is used to flag which entity a class extends.
 * @package LotGD\Core\Doctrine
 * @Annotation
 * @Target("CLASS")
 * @Attributes({
 *  @Attribute("of", type = "string")
 * })
 */
class Extension
{
    /** @var string */
    private $modelClass;

    /**
     * Extension constructor.
     * @param array $attributes
     * @throws ArgumentException
     */
    public function __construct(array $attributes) {
        $this->modelClass = $attributes["of"];

        if (!class_exists($this->modelClass)) {
            throw new ArgumentException("The class given in of must be a valid class.");
        }

        if (!in_array(ExtendableModelInterface::class, class_implements($this->modelClass))) {
            throw new ArgumentException("The class given in of must implement the ExtendableModelInterface.");
        }
    }

    /**
     * Returns the model class name.
     * @return string
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }
}