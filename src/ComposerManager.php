<?php
declare(strict_types=1);

namespace LotGD\Core;

use Composer\Composer;
use LotGD\Core\Exceptions\LibraryDoesNotExistException;

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
    public function getComposer(): Composer
    {
        if ($this->composer === null) {
            $this->composer = \Composer\Factory::create(new \Composer\IO\NullIO());
        }
        return $this->composer;
    }

    /**
     * Return the Composer package for the corresponding library, in vendor/module format.
     * @return PackageInterface Package corresponding to this library.
     * @throws LibraryDoesNotExistException
     */
    public function getPackageForLibrary(string $library): PackageInterface
    {
        // TODO: should probably do something better than O(n) here.
        $packages = $this->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages();
        foreach ($packages as $p) {
            if ($p->getName() === $library) {
               return $p;
            }
        }
        throw new LibraryDoesNotExistException();
    }

    /**
     * Return a list of the configured packages which are LotGD modules (type = 'lotgd-module').
     * @return array Array of \Composer\PackageInterface.
     */
    public function getModulePackages(): array
    {
        $result = array();
        $packages = $this->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages();
        foreach ($packages as $p) {
            if ($p->getType() === 'lotgd-module') {
                array_push($result, $p);
            }
        }

        return $result;
    }
}
