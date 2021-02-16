<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\CharacterCommands;

use LotGD\Core\Console\Command\Character\CharacterConfigSetCommand;
use LotGD\Core\EventManager;
use LotGD\Core\Events\EventContextData;
use LotGD\Core\Game;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;

class CharacterConfigSetCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "character";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new CharacterConfigSetCommand($this->g));
    }

    public function testIfCommandEmitsEvent()
    {
        /** @var Game $game */
        $game = $this->g;

        $characters = [
            ["10000000-0000-0000-0000-000000000001", "Testcharacter 1", "test", "0.126"],
            ["10000000-0000-0000-0000-000000000002", "Testcharacter 2", "test-other", "hi"],
        ];

        foreach ($characters as [$character, $displayName, $setting, $value]) {
            $eventManager = $this->getMockBuilder(EventManager::class)
                ->disableOriginalConstructor()
                ->setMethods(array('publish'))
                ->getMock();
            $eventManager->expects($this->once())
                ->method('publish')
                ->with(
                    $this->equalTo("h/lotgd/core/cli/character-config-set"),
                    $this->callback(function (EventContextData $eventContextData) use ($character, $displayName, $setting, $value) {
                        $pass = 1;

                        $pass &= $eventContextData->has("character") === true;
                        $pass &= $eventContextData->get("character")->getDisplayName() === $displayName;

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
            $this->assertStringContainsString("Character {$displayName}", $output);
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
            "id" => "10000000-0000-0000-0000-000000000001",
            "setting" => "Setting",
            "value" => 13,
        ]);

        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("Character Testcharacter 1", $output);
        $this->assertStringNotContainsString("[ERROR]", $output);
        $this->assertStringNotContainsString("Setting does not exist.", $output);
    }
}