<?php
declare(strict_types=1);

namespace LotGD\Core\Models;


interface ExtendableModelInterface
{
    public function __call($method, $arguments);
}