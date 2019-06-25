<?php
declare(strict_types=1);

namespace LotGD\Core;

use Doctrine\Common\Annotations\AnnotationReader;
use LotGD\Core\Doctrine\Annotations\Extension;
use LotGD\Core\Doctrine\Annotations\ExtensionMethod;
use LotGD\Core\Exceptions\ArgumentException;
use ReflectionClass;

/**
 * Contains method to help the extension of a model.
 */
class ModelExtender
{
    /** @var AnnotationReader */
    private $reader;
    /** @var array */
    private static $classes = [];

    /**
     * ModelExtender constructor.
     */
    public function __construct()
    {
        $this->reader = new AnnotationReader();
    }

    /**
     * @param string[] $classes
     */
    public function addMore(array $classes): void
    {
        foreach ($classes as $class) {
            $this->add($class);
        }
    }

    /**
     * @param string $class
     * @throws ArgumentException if the given class is not properly annotated.
     */
    public function add(string $class): void
    {
        $reflectionClass = new ReflectionClass($class);
        /** @var Extension $extensionAnnotation */
        $extensionAnnotation = $this->reader->getClassAnnotation($reflectionClass, Extension::class);

        if ($extensionAnnotation === null) {
            throw new ArgumentException(\sprintf("Class %s must have the class Annotation %s", $class, Extension::class));
        }

        $modelClass = $extensionAnnotation->getModelClass();

        if (empty(self::$classes[$modelClass])) {
            self::$classes[$modelClass] = [];
        }

        // Run through static methods
        $reflectionMethods = $reflectionClass->getMethods();

        foreach ($reflectionMethods as $method) {
            if ($method->isStatic() === false) {
                throw new ArgumentException(\sprintf("Method %s must be static.", $method->getName()));
            }

            /** @var ExtensionMethod $extensionMethodAnnotation */
            $extensionMethodAnnotation = $this->reader->getMethodAnnotation($method, ExtensionMethod::class);
            $methodName = $method->getName();

            self::$classes[$modelClass][$extensionMethodAnnotation->getMethodName()] = [$class, $methodName];
        }
    }

    /**
     * Returns a callback registered in the model extender globally.
     * @param string $modelClassName
     * @param string $methodName
     * @return callable|null
     */
    public static function get(string $modelClassName, string $methodName): ?callable
    {
        if (empty(self::$classes[$modelClassName])) {
            return null;
        }

        if (empty(self::$classes[$modelClassName][$methodName])) {
            return null;
        }

        return self::$classes[$modelClassName][$methodName];
    }
}
