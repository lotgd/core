<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\SceneCommands;

use LotGD\Core\Console\Command\Character\CharacterConfigResetCommand;
use LotGD\Core\Console\Command\Scene\SceneConfigResetCommand;
use LotGD\Core\EventManager;
use LotGD\Core\Events\EventContextData;
use LotGD\Core\Game;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;

class SceneConfigResetCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "scene-2";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new SceneConfigResetCommand($this->g));
    }

    public function testIfCommandEmitsEvent()
    {
        /** @var Game $game */
        $game = $this->g;

        $scenes = [
            ["30000000-0000-0000-0000-000000000001", "The Village", "tests/village", "test"],
            ["30000000-0000-0000-0000-000000000002", "The Forest", "tests/forest", "test-other"],
        ];

        foreach ($scenes as [$scene, $sceneTitle, $path, $setting]) {
            $eventManager = $this->getMockBuilder(EventManager::class)
                ->disableOriginalConstructor()
                ->setMethods(array('publish'))
                ->getMock();
            $eventManager->expects($this->once())
                ->method('publish')
                ->with(
                    $this->equalTo("h/lotgd/core/cli/scene-config-reset/{$path}"),
                    $this->callback(function (EventContextData $eventContextData) use ($scene, $sceneTitle, $setting) {
                        $pass = 1;

                        $pass &= $eventContextData->has("scene") === true;
                        $pass &= $eventContextData->get("scene")->getTitle() === $sceneTitle;

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
                "id" => $scene,
                "setting" => $setting,
            ]);

            $output = $command->getDisplay();

            $this->assertSame(Command::FAILURE, $command->getStatusCode());
            $this->assertStringContainsString("Scene {$sceneTitle}", $output);
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
            "id" => "30000000-0000-0000-0000-000000000001",
            "setting" => "Setting",
        ]);

        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("Scene The Village", $output);
        $this->assertStringNotContainsString("[ERROR]", $output);
        $this->assertStringNotContainsString("Setting does not exist.", $output);
    }
}