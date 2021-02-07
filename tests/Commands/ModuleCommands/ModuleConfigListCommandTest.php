<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\CharacterCommands;

use LotGD\Core\Console\Command\Module\ModuleConfigListCommand;
use LotGD\Core\EventManager;
use LotGD\Core\Events\EventContextData;
use LotGD\Core\Game;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;

class ModuleConfigListCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "module-2";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new ModuleConfigListCommand($this->g));
    }

    public function testIfCommandRunsWithoutRegisteredEvents()
    {
        $command = $this->getCommand();
        $command->execute([
            "moduleName" => "lotgd/tests"
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("This module does not provide any settings.", $output);
    }

    public function testIfCommandEmitsEvent()
    {
        /** @var Game $game */
        $game = $this->g;

        $modules = [
            "lotgd/tests",
            "lotgd/tests-other"
        ];

        foreach ($modules as $module) {
            $eventManager = $this->getMockBuilder(EventManager::class)
                ->disableOriginalConstructor()
                ->setMethods(array('publish'))
                ->getMock();
            $eventManager->expects($this->once())
                ->method('publish')
                ->with(
                    $this->equalTo("h/lotgd/core/cli/module-config-list/{$module}"),
                    $this->callback(function (EventContextData $eventContextData) use ($module) {
                        $pass = 1;

                        $pass &= $eventContextData->has("module") === true;
                        $pass &= $eventContextData->get("module")->getLibrary() === $module;

                        $pass &= $eventContextData->has("io") === true;
                        $pass &= $eventContextData->get("io") instanceof SymfonyStyle;

                        $pass &= $eventContextData->has("settings") === true;
                        $pass &= $eventContextData->get("settings") === [];

                        return $pass == true;
                    }),
                )->will($this->returnArgument(1));
            $game->setEventManager($eventManager);

            $command = $this->getCommand();
            $command->execute([
                "moduleName" => $module,
            ]);

            $output = $command->getDisplay();

            $this->assertSame(Command::SUCCESS, $command->getStatusCode());
            $this->assertStringContainsString("Module {$module}", $output);
            $this->assertStringContainsString("This module does not provide any settings.", $output);
        }
    }

    public function testIfCommandDisplaysSettingsWhenGivenByEvent()
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
                $newSettings = [
                    ["setting1", "0.00000000000000000000000001", "float 0..1, chance to succeed"],
                    ["setting2", "DragonMillions", "string, name of the lottery"],
                ];
                $settings = $b->get("settings");
                $settings = [...$settings, ...$newSettings];

                return $b->set("settings", $settings);
            }));
        $game->setEventManager($eventManager);

        $command = $this->getCommand();
        $command->execute([
            "moduleName" => "lotgd/tests"
        ]);

        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("setting1", $output);
        $this->assertStringContainsString("0.00000000000000000000000001", $output);
        $this->assertStringContainsString("float 0..1, chance to succeed", $output);
        $this->assertStringContainsString("setting2", $output);
        $this->assertStringContainsString("DragonMillions", $output);
        $this->assertStringContainsString("string, name of the lottery", $output);
        $this->assertStringNotContainsString("This module does not provide any settings.", $output);
    }
}