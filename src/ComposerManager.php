<?php
declare(strict_types=1);

namespace LotGD\Core;

class ComposerManager
{
    private $g;
    private $composer;

    /**
     * @param $g The game.
     */
    public function __construct(Game $g)
    {
        $this->g = $g;
    }

    /**
     * Returns a Composer instance to perform underlying operations on. Be careful.
     * @return Composer An instance of Composer.
     */
    public function getComposer()
    {
        if ($this->composer === null) {
            $this->composer = \Composer\Factory::create(new \Composer\IO\NullIO());
        }
        return $this->composer;
    }
}
