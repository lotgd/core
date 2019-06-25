<?php
declare(strict_types=1);

namespace LotGD\Core\Events;

use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\Scene;

/**
 * NewViewpoint data container which is used if no scene has ever been visited.
 *
 * Fields are:
 *  character   Character
 *  scene       Scene|null
 */
class NewViewpointData extends EventContextData
{
    /**
     * NewViewpoint constructor.
     * @param array $data
     * @throws ArgumentException In case $data contains invalid data.
     */
    protected function __construct(array $data)
    {
        if (\array_keys($data) !== ["character", "scene"]) {
            throw new ArgumentException("A NewViewpoint event must have only character and scene.");
        }

        if (!$data["character"] instanceof Character) {
            throw new ArgumentException(\sprintf(
                "NewViewpoint data[character] must be an instance of %s, %s given.",
                Character::class,
                \get_class($data)
            ));
        }

        if ($data["scene"] !== null and !$data["scene"] instanceof Scene) {
            throw new ArgumentException(\sprintf(
                "NewViewpoint data[scene] must be an instance of %s or null, %s given.",
                Scene::class,
                \get_class($data)
            ));
        }

        parent::__construct($data);
    }
}
