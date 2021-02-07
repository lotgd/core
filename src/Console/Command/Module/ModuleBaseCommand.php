<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Module;

use Doctrine\Persistence\ObjectRepository;
use LotGD\Core\Console\Command\BaseCommand;
use LotGD\Core\Models\Module;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class ModuleBaseCommand extends BaseCommand
{
    protected ?string $namespace = "module";

    /**
     * @return InputArgument
     */
    protected function getModuleNameArgumentDefinition(): InputArgument
    {
        return new InputArgument(
            name: "moduleName",
            mode: InputArgument::REQUIRED,
            description: "Name of the module, in vendor/package format",
        );
    }

    protected function getModuleRepository(): ObjectRepository
    {
        return $this->game->getEntityManager()->getRepository(Module::class);
    }

    protected function getModuleModel(InputInterface $input): ?Module
    {
        return $this->game->getModuleManager()->getModule($input->getArgument("moduleName"));
    }
}