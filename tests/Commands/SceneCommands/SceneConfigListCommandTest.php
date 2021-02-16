<?php
declare(strict_types=1);

use LotGD\Core\Console\Command\Scene\SceneConfigListCommand;
use LotGD\Core\EventManager;
use LotGD\Core\Events\EventContextData;
use LotGD\Core\Game;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;

class SceneConfigListCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "scene-2";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new SceneConfigListCommand($this->g));
    }

    public function testIfCommandRunsWithoutRegisteredEvents()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "30000000-0000-0000-0000-000000000001",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("There are no scene settings available.", $output);
    }

    public function testIfCommandEmitsEvent()
    {
        /** @var Game $game */
        $game = $this->g;

        $scenes = [
            ["30000000-0000-0000-0000-000000000001", "The Village", "tests/village"],
            ["30000000-0000-0000-0000-000000000002", "The Forest", "tests/forest"],
        ];

        foreach ($scenes as [$scene, $sceneTitle, $path]) {
            $eventManager = $this->getMockBuilder(EventManager::class)
                ->disableOriginalConstructor()
                ->setMethods(['publish'])
                ->getMock();
            $eventManager->expects($this->once())
                ->method('publish')
                ->with(
                    $this->equalTo("h/lotgd/core/cli/scene-config-list/{$path}"),
                    $this->callback(function (EventContextData $eventContextData) use ($scene, $sceneTitle) {
                        $pass = 1;

                        $pass &= $eventContextData->has("scene") === true;
                        $pass &= $eventContextData->get("scene")->getTitle() === $sceneTitle;

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
                "id" => $scene,
            ]);

            $output = $command->getDisplay();

            $this->assertSame(Command::SUCCESS, $command->getStatusCode());
            $this->assertStringContainsString("Scene {$sceneTitle}", $output);
            $this->assertStringContainsString("There are no scene settings available.", $output);
        }
    }

    public function testIfCommandDisplaysSettingsWhenGivenByEvent()
    {
        /** @var Game $game */
        $game = $this->g;
        $eventManager = $this->getMockBuilder(EventManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['publish'])
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
            "id" => "30000000-0000-0000-0000-000000000001",
        ]);

        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("setting1", $output);
        $this->assertStringContainsString("0.00000000000000000000000001", $output);
        $this->assertStringContainsString("float 0..1, chance to succeed", $output);
        $this->assertStringContainsString("setting2", $output);
        $this->assertStringContainsString("DragonMillions", $output);
        $this->assertStringContainsString("string, name of the lottery", $output);
        $this->assertStringNotContainsString("There are no scene settings available.", $output);
    }
}
