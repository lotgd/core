<?php
declare(strict_types=1);

namespace LotGD\Core\Events;

use JetBrains\PhpStorm\ArrayShape;
use LotGD\Core\Models\Viewpoint;

/**
 * Class ViewpointDecorationEventData.
 */
class ViewpointDecorationEventData extends EventContextData
{
    #[ArrayShape([
        "viewpoint" => [
            "type" => Viewpoint::class,
            "required" => "bool",
        ],
    ])]
    protected static ?array $argumentConfig = [
        "viewpoint" => ["type" => Viewpoint::class, "required" => true],
    ];
}
