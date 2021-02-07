<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\CharacterCommands;

use LotGD\Core\Console\Command\Module\ModuleListCommand;
use LotGD\Core\Models\Module;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ModuleListCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "module-2";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new ModuleListCommand($this->g));
    }

    public function testIfCommandRunsWithModulesInstalled()
    {
        $command = $this->getCommand();
        $command->execute([]);
        $output = $command->getDisplay();

        // Assertions
        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("lotgd/tests", $output);
        $this->assertStringContainsString("lotgd/tests-other", $output);
        $this->assertStringNotContainsString("No modules installed.", $output);
    }

    public function testIfCommandRunsWithNoModulesInstalled()
    {
        // Remove modules first
        $modules = $this->g->getEntityManager()->getRepository(Module::class)->findAll();
        foreach ($modules as $module) {
            $this->g->getEntityManager()->remove($module);
            $this->g->getEntityManager()->flush();
        }

        // Run command
        $command = $this->getCommand();
        $command->execute([]);
        $output = $command->getDisplay();

        // Assert
        $this->assertStringContainsString("No modules installed.", $output);
    }
}