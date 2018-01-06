<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

use LotGD\Core\ModelExtender;

/**
 * Trait to add the __call class required for extendable models.
 * @package LotGD\Core\Tools\Model
 */
trait ExtendableModel
{
    public function __call($method, $arguments)
    {
        $callback = ModelExtender::get(self::class, $method);

        if ($callback) {
            return call_user_func_array($callback, array_merge([$this], $arguments));
        }
    }
}