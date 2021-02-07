<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\CharacterCommands;

use LotGD\Core\Console\Command\Module\ModuleConfigListCommand;
use LotGD\Core\Console\Command\Module\ModuleConfigSetCommand;
use LotGD\Core\Console\Command\Module\ModuleListCommand;
use LotGD\Core\EventHandler;
use LotGD\Core\EventManager;
use LotGD\Core\Events\EventContext;
use LotGD\Core\Events\EventContextData;
use LotGD\Core\Game;
use LotGD\Core\Models\Module;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;

class ModuleConfigSetCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "module-2";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new ModuleConfigSetCommand($this->g));
    }

    public function testIfCommandEmitsEvent()
    {
        /** @var Game $game */
        $game = $this->g;

        $modules = [
            ["lotgd/tests", "test", "0.126"],
            ["lotgd/tests-other", "test-other", "hi"],
        ];

        foreach ($modules as [$module, $setting, $value]) {
            $eventManager = $this->getMockBuilder(EventManager::class)
                ->disableOriginalConstructor()
                ->setMethods(array('publish'))
                ->getMock();
            $eventManager->expects($this->once())
                ->method('publish')
                ->with(
                    $this->equalTo("h/lotgd/core/cli/module-config-set/{$module}"),
                    $this->callback(function (EventContextData $eventContextData) use ($module, $setting, $value) {
                        $pass = 1;

                        $pass &= $eventContextData->has("module") === true;
                        $pass &= $eventContextData->get("module")->getLibrary() === $module;

                        $pass &= $eventContextData->has("io") === true;
                        $pass &= $eventContextData->get("io") instanceof SymfonyStyle;

                        $pass &= $eventContextData->has("setting") === true;
                        $pass &= $eventContextData->get("setting") === $setting;

                        $pass &= $eventContextData->has("value") === true;
                        $pass &= $eventContextData->get("value") === $value;

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
                "value" => $value,
            ]);

            $output = $command->getDisplay();

            $this->assertSame(Command::FAILURE, $command->getStatusCode());
            $this->assertStringContainsString("Module {$module}", $output);
            $this->assertStringContainsString("[ERROR]", $output);
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
            "value" => 13,
        ]);

        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("Module lotgd/tests", $output);
        $this->assertStringNotContainsString("[ERROR]", $output);
        $this->assertStringNotContainsString("Setting does not exist.", $output);
    }
}