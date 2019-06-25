<?php
declare(strict_types=1);

namespace LotGD\Core\Events;

use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Models\Scene;
use LotGD\Core\Models\Viewpoint;

/**
 * NavigateToScene data container which can be used for navigational events.
 *
 * Fields are:
 *  referrer    Scene|null
 *  viewpoint   Viewpoint
 *  scene       Scene
 *  parameters  array
 *  redirect    Scene|null
 */
class NavigateToSceneData extends EventContextData
{
    /**
     * NavigateToScene constructor.
     * @param array $data Must contain fields referrer, viewpoint, scene, parameters and redirect; none more.
     * @throws ArgumentException
     */
    protected function __construct(array $data)
    {
        $mustHaveForm = ["referrer", "viewpoint", "scene", "parameters", "redirect"];
        $doesHaveForm = \array_keys($data);
        \sort($mustHaveForm);
        \sort($doesHaveForm);

        if ($doesHaveForm !== $mustHaveForm) {
            throw new ArgumentException("A new NavigateToScene event must have referrer, viewpoint, scene, parameters and redirect.");
        }

        if ($data["referrer"] instanceof Scene === false and $data["referrer"] !== null) {
            throw new ArgumentException(\sprintf(
                "data[referrer] must be an instance of %s, %s given.",
                Scene::class,
                \get_class($data["referrer"])
            ));
        }

        if ($data["scene"] instanceof Scene === false) {
            throw new ArgumentException(\sprintf(
                "data[scene] must be an instance of %s, %s given.",
                Scene::class,
                \get_class($data["scene"])
            ));
        }

        if ($data["viewpoint"] instanceof Viewpoint === false) {
            throw new ArgumentException(\sprintf(
                "data[viewpoint] must be an instance of %s, %s given.",
                Viewpoint::class,
                \get_class($data["viewpoint"])
            ));
        }

        parent::__construct($data);
    }
}
