<?php
declare(strict_types=1);

namespace LotGD\Core\Events;

use LotGD\Core\Models\Viewpoint;

/**
 * Class ViewpointDecorationEventData.
 */
class ViewpointDecorationEventData extends EventContextData
{
    protected static $argumentConfig = [
        "viewpoint" => ["type" => Viewpoint::class, "required" => true],
    ];
}
