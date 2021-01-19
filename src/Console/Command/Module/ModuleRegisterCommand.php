<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Module;

use Exception;
use LotGD\Core\Console\Command\BaseCommand;
use LotGD\Core\Exceptions\ClassNotFoundException;
use LotGD\Core\Exceptions\InvalidConfigurationException;
use LotGD\Core\Exceptions\ModuleAlreadyExistsException;
use LotGD\Core\Exceptions\WrongTypeException;
use LotGD\Core\LibraryConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Danerys command to register and initiate any newly installed modules.
 */
class ModuleRegisterCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('module:register')
            ->setDescription('Register and initialize any newly installed modules')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $modules = $this->game->getComposerManager()->getModulePackages();

        $globalFlawless = true;
        $registered = [];
        foreach ($modules as $p) {
            $flawless = $this->registerModule($p->getName(), $io, $registered);

            $globalFlawless &= $flawless;
        }

        if (!$globalFlawless) {
            $io->warning("Some module were not registered properly.");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Register a given package as a module if it is of type lotdg-module. Resolves dependencies and skips already registered packages.
     * @param string $packageName
     * @param SymfonyStyle $io
     * @param array $registered
     * @return bool True if registering was flawless
     * @throws InvalidConfigurationException
     * @throws WrongTypeException
     * @throws Exception
     */
    protected function registerModule(
        string $packageName,
        SymfonyStyle $io,
        array &$registered
    ): bool {
        $composerRepository = $this->game->getComposerManager()->getComposer()
            ->getRepositoryManager()->getLocalRepository();

        $package = $composerRepository->findPackage($packageName, "*");

        # Skip if not a lotgd-module
        if ($package->getType() !== "lotgd-module") {
            return true;
        }

        # Skip if already registered
        if (!empty($registered[$packageName])) {
            return true;
        }

        $io->text("Reading module {$packageName} {$package->getPrettyVersion()}");

        # Try to load module configuration ($moduleRoot/lotgd.yml)
        try {
            $library = new LibraryConfiguration($this->game->getComposerManager(), $package, $this->game->getCWD());
        } catch (InvalidConfigurationException) {
            $io->error("\tModule {$packageName} does not have a valid lotgd.yml in its root.");
            return false;
        }

        # Register dependencies first.
        $dependencyFlawless = true;
        $dependencies = $package->getRequires();
        foreach ($dependencies as $dependency) {
            $dependencyFlawless &= $this->registerModule($dependency->getTarget(), $io, $registered);
        }

        # If $dependencyFlawless is not true anymore (as true & false == 0), we should abort as a dependency was not met.
        if (!$dependencyFlawless) {
            $io->warning("\t{$packageName} was not completely installed, as one of its dependencies had an "
                ."error during registration.");
            return false;
        }

        try {
            $this->game->getModuleManager()->register($library);
            $io->success("\tRegistered new module {$packageName}");
        } catch (ModuleAlreadyExistsException $e) {
            $io->note("\tSkipping already registered module {$packageName}");
        } catch (ClassNotFoundException $e) {
            $io->error("\tError installing module {$packageName}: {$e->getMessage()}");
            return false;
        }

        $registered[$packageName] = true;

        return true;
    }
}
