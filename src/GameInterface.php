<?php
declare (strict_types=1);

namespace LotGD\Core;

interface GameInterface
{
    /**
     * @{inheritdoc}
     */
    public function getEntityManager();

    /**
    * @{inheritdoc}
     */
    public function getEventManager();
}
