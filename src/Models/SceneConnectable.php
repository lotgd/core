<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

interface SceneConnectable
{
    public const Bidirectional = 0;
    public const Unidirectional = 1;
    public const Xordirectional = 2;

    /**
     * Creates an outgoing connection for this scene to the given connectable.
     * @param SceneConnectable $connectable
     * @param int $directionality
     * @return SceneConnection
     */
    public function connect(SceneConnectable $connectable, int $directionality): SceneConnection;
}