<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\CharacterCommands;

use LotGD\Core\Console\Command\Character\CharacterConfigSetCommand;
use LotGD\Core\Console\Command\Scene\SceneConfigSetCommand;
use LotGD\Core\EventManager;
use LotGD\Core\Events\EventContextData;
use LotGD\Core\Game;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;

class SceneConfigSetCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "scene-2";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new SceneConfigSetCommand($this->g));
    }

    public function testIfCommandEmitsEvent()
    {
        /** @var Game $game */
        $game = $this->g;

        $characters = [
            ["30000000-0000-0000-0000-000000000001", "The Village", "tests/village", "test", "0.126"],
            ["30000000-0000-0000-0000-000000000002", "The Forest", "tests/forest", "test-other", "hi"],
        ];

        foreach ($characters as [$character, $displayName, $path, $setting, $value]) {
            $eventManager = $this->getMockBuilder(EventManager::class)
                ->disableOriginalConstructor()
                ->setMethods(array('publish'))
                ->getMock();
            $eventManager->expects($this->once())
                ->method('publish')
                ->with(
                    $this->equalTo("h/lotgd/core/cli/character-config-set/{$path}"),
                    $this->callback(function (EventContextData $eventContextData) use ($character, $displayName, $setting, $value) {
                        $pass = 1;

                        $pass &= $eventContextData->has("scene") === true;
                        $pass &= $eventContextData->get("scene")->getTitle() === $displayName;

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
                "id" => $character,
                "setting" => $setting,
                "value" => $value,
            ]);

            $output = $command->getDisplay();

            $this->assertSame(Command::FAILURE, $command->getStatusCode());
            $this->assertStringContainsString("Scene {$displayName}", $output);
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
            "id" => "30000000-0000-0000-0000-000000000001",
            "setting" => "Setting",
            "value" => 13,
        ]);

        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("Scene The Village", $output);
        $this->assertStringNotContainsString("[ERROR]", $output);
        $this->assertStringNotContainsString("Setting does not exist.", $output);
    }
}