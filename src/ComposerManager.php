<?php
declare(strict_types=1);

namespace LotGD\Core;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\CompletePackageInterface;
use Composer\Package\PackageInterface;
use Exception;

use LotGD\Core\Exceptions\InvalidConfigurationException;
use LotGD\Core\Exceptions\LibraryDoesNotExistException;

/**
 * Helps perform tasks with the composer configuration.
 */
class ComposerManager
{
    private ?Composer $composer;

    /**
     * Construct a manager with an optional working directory where composer.json
     * lives.
     * @param string $cwd
     */
    public function __construct(private string $cwd) {}

    /**
     * Returns a Composer instance to perform underlying operations on. Be careful.
     * @return Composer An instance of Composer.
     */
    public function getComposer(): Composer
    {
        if ($this->composer === null) {
            // Verify location of composer.json.
            $composerConfigPath = $this->cwd . \DIRECTORY_SEPARATOR . "composer.json";
            if (!\file_exists($composerConfigPath)) {
                throw new InvalidConfigurationException("composer.json cannot be found at {$composerConfigPath}.");
            }

            $factory = new Factory();
            $this->composer = $factory->createComposer(new NullIO(), $composerConfigPath, false, $this->cwd);
        }

        return $this->composer;
    }

    /**
     * Return the Composer package for the corresponding library, in vendor/module format.
     * @param string $library
     * @throws LibraryDoesNotExistException
     * @return CompletePackageInterface Package corresponding to this library.
     */
    public function getPackageForLibrary(string $library): CompletePackageInterface
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
     * Return all the packages installed in the current setup.
     * @return PackageInterface[]
     */
    public function getPackages(): array
    {
        return \array_merge(
            [$this->getComposer()->getPackage()],
            $this->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages()
        );
    }

    /**
     * Return a list of the configured packages which are LotGD modules (type = 'lotgd-module').
     * @return PackageInterface[]
     */
    public function getModulePackages(): array
    {
        $result = [];
        $packages = $this->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages();
        foreach ($packages as $p) {
            if ($p->getType() === 'lotgd-module') {
                \array_push($result, $p);
            }
        }

        return $result;
    }

    /**
     * Find the filesystem path where the code for a namespace can be found.
     * @param string $namespace The namespace to translate.
     * @return string|null Path representing $namespace or null if $namespace
     * cannot be found or if the path does not exist.
     */
    public function translateNamespaceToPath(string $namespace): ?string
    {
        // Find the directory for this namespace by using the autoloader's
        // classmap.
        $autoloader = require($this->findAutoloader());
        $prefixes = $autoloader->getPrefixesPsr4();

        // Standardize the namespace to remove any leading \ and add a trailing \
        $n = $namespace;
        if ('\\' == $n[0]) {
            $n = \substr($n, 1);
        }
        if (\strlen($n) > 0 && '\\' != $n[\strlen($n) - 1]) {
            $n .= '\\';
        }

        $split = \explode('\\', $n);
        $suffix = \array_splice($split, -1, 1); // starts with ['']
        $path = null;
        while (!empty($split)) {
            $key = \implode('\\', $split) . '\\';
            $dir = \implode(\DIRECTORY_SEPARATOR, $suffix);
            // Prefix to directory mappings are arrays in Composer's
            // ClassLoader object. Not sure why. This might break in
            // some unforseen case.
            if (isset($prefixes[$key]) && \is_dir($prefixes[$key][0] .  \DIRECTORY_SEPARATOR . $dir)) {
                $path = $prefixes[$key][0] .  \DIRECTORY_SEPARATOR . $dir;
                break;
            }
            $suffix = \array_merge($suffix, \array_splice($split, -1, 1));
        }

        if ($path == null) {
            return null;
        }
        $path = \realpath($path);
        if ($path == false) {
            return null;
        }
        return $path;
    }

    /**
     * Returns a path (could be relative) to the proper autoload.php file in
     * the current setup.
     * @return string
     */
    public function findAutoloader(): string
    {
        // Dance to find the autoloader.
        // TOOD: change this to open up the Composer config and use $c['config']['vendor-dir'] instead of "vendor"
        $order = [
            \implode(\DIRECTORY_SEPARATOR, [$this->cwd, "vendor", "autoload.php"]),
            \implode(\DIRECTORY_SEPARATOR, [__DIR__, "..", "vendor", "autoload.php"]),
            \implode(\DIRECTORY_SEPARATOR, [__DIR__, "..", "autoload.php"]),
        ];

        foreach ($order as $path) {
            if (\file_exists($path)) {
                return $path;
            }
        }
        throw new Exception("Cannot find autoload.php in any of its usual locations.");
    }
}
