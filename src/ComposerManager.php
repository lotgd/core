<?php
declare(strict_types=1);

namespace LotGD\Core;

use Composer\Composer;
use Monolog\Logger;

use LotGD\Core\Exceptions\LibraryDoesNotExistException;

/**
 * Helps perform tasks with the composer configuration.
 */
class ComposerManager
{
    private $logger;
    private $composer;

    /**
     * Creates a new ComposerManager.
     * @param Monlog\Logger $logger A logger instance for messaging.
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
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
