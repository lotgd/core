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
    /**
     * ViewpointDecorationEventData constructor.
     * @param array $data Must contain field viewpoint.
     * @throws ArgumentException
     */
    protected function __construct(array $data)
    {
        $mustHaveForm = ["viewpoint"];
        $doesHaveForm = array_keys($data);
        sort($mustHaveForm);
        sort($doesHaveForm);

        if ($doesHaveForm !== $mustHaveForm) {
            throw new ArgumentException("A new ViewpointDecoration event must have a viewpoint..");
        }

        if ($data["viewpoint"] instanceof Viewpoint === false) {
            throw new ArgumentException(sprintf(
                "data[viewpoint] must be an instance of %s, %s given.",
                Viewpoint::class,
                get_class($data["viewpoint"])
            ));
        }

        parent::__construct($data);
    }
}