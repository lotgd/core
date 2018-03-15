<?php
declare(strict_types=1);

namespace LotGD\Core\Events;

use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Models\Viewpoint;

/**
 * Class ViewpointDecorationEventData
 * @package LotGD\Core\Events
 */
class ViewpointDecorationEventData extends EventContextData
{
    protected static $argumentConfig = [
        "viewpoint" => ["type" => Viewpoint::class, "required" => true]
    ];
}