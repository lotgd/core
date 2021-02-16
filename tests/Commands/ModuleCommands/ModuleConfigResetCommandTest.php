<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\ModuleCommands;

use LotGD\Core\Console\Command\Module\ModuleConfigResetCommand;
use LotGD\Core\EventManager;
use LotGD\Core\Events\EventContextData;
use LotGD\Core\Game;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;

class ModuleConfigResetCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "module-2";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new ModuleConfigResetCommand($this->g));
    }

    public function testIfCommandEmitsEvent()
    {
        /** @var Game $game */
        $game = $this->g;

        $modules = [
            ["lotgd/tests", "test"],
            ["lotgd/tests-other", "test-other"],
        ];

        foreach ($modules as [$module, $setting]) {
            $eventManager = $this->getMockBuilder(EventManager::class)
                ->disableOriginalConstructor()
                ->setMethods(array('publish'))
                ->getMock();
            $eventManager->expects($this->once())
                ->method('publish')
                ->with(
                    $this->equalTo("h/lotgd/core/cli/module-config-reset/{$module}"),
                    $this->callback(function (EventContextData $eventContextData) use ($module, $setting) {
                        $pass = 1;

                        $pass &= $eventContextData->has("module") === true;
                        $pass &= $eventContextData->get("module")->getLibrary() === $module;

                        $pass &= $eventContextData->has("io") === true;
                        $pass &= $eventContextData->get("io") instanceof SymfonyStyle;

                        $pass &= $eventContextData->has("setting") === true;
                        $pass &= $eventContextData->get("setting") === $setting;

                        $pass &= $eventContextData->has("return") === true;
                        $pass &= $eventContextData->get("return") === 1;

                        $pass &= $eventContextData->has("reason") === true;
                        $pass &= $eventContextData->get("reason") === "Setting does not exist.";

                        return $pass == true;
                    }),
                )->will($this->returnArgument(1));
            $game->setEventManager($eventManager);

            $command = $this->getCommand();
            $command->execute([
                "moduleName" => $module,
                "setting" => $setting,
            ]);

            $output = $command->getDisplay();

            $this->assertSame(Command::FAILURE, $command->getStatusCode());
            $this->assertStringContainsString("Module lotgd/tests", $output);
            $this->assertStringContainsString("[ERROR]", $output);
            $this->assertStringNotContainsString("[OK]", $output);
            $this->assertStringContainsString("Setting does not exist.", $output);
        }
    }

    public function testIfCommandSucceedsWhenReturnedCallbackIsSetToSuccess()
    {
        /** @var Game $game */
        $game = $this->g;
        $eventManager = $this->getMockBuilder(EventManager::class)
            ->disableOriginalConstructor()
            ->setMethods(array('publish'))
            ->getMock();
        $eventManager->expects($this->once())
            ->method('publish')
            ->will($this->returnCallback(function (string $a, EventContextData $b) {
                return $b->set("return", Command::SUCCESS);
            }));
        $game->setEventManager($eventManager);

        $command = $this->getCommand();
        $command->execute([
            "moduleName" => "lotgd/tests",
            "setting" => "Setting",
        ]);

        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("Module lotgd/tests", $output);
        $this->assertStringNotContainsString("[ERROR]", $output);
        $this->assertStringNotContainsString("Setting does not exist.", $output);
    }
}