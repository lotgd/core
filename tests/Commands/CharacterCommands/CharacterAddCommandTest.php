<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\CharacterCommands;

use LotGD\Core\Console\Command\Character\CharacterAddCommand;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\Repositories\CharacterRepository;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class CharacterAddCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "character";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new CharacterAddCommand($this->g));
    }

    public function testIfCommandFailsIfNoNameWasGiven()
    {
        $command = $this->getCommand();

        $this->expectException(RuntimeException::class);
        $command->execute([]);
    }

    public function testIfCharacterGetsCreatedWithOnlyName()
    {
        /** @var CharacterRepository $repository */
        $repository = $this->g->getEntityManager()->getRepository(Character::class);
        $command = $this->getCommand();
        $command->execute([
            "name" => "Gandalf the First",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringContainsString("was successfully created.", $output);

        // Check the database, too.
        $this->g->getEntityManager()->clear();
        $character = $repository->findOneBy(["name" => "Gandalf the First"]);
        $this->assertNotNull($character);
        $this->assertSame("Gandalf the First", $character->getName());
        $this->assertSame(1, $character->getLevel());
        $this->assertSame(10, $character->getMaxHealth());
        $this->assertSame(10, $character->getHealth());
    }

    public function testIfCommandFailsIfLevelIsBelow1()
    {
        $command = $this->getCommand();
        $levels = [-10, -5, 0];

        foreach ($levels as $level) {
            $command->execute([
                "name" => "Gandalf the Failed",
                "--level" => $level,
            ]);
            $output = $command->getDisplay();

            $this->assertSame(Command::FAILURE, $command->getStatusCode());
            $this->assertStringContainsString("[ERROR]", $output);
            $this->assertStringContainsString("Level must at least be 1.", $output);
        }
    }

    public function testIfCommandFailsWhenMaxHealthIsBelow0()
    {
        $command = $this->getCommand();
        $levels = [-100, -50, -1];

        foreach ($levels as $level) {
            $command->execute([
                "name" => "Gandalf the Unhealthy",
                "--maxHealth" => $level,
            ]);
            $output = $command->getDisplay();

            $this->assertSame(Command::FAILURE, $command->getStatusCode());
            $this->assertStringContainsString("[ERROR]", $output);
            $this->assertStringContainsString("Maximum health must be at least 0.", $output);
        }
    }

    public function testIfCommandWarnsWhenMaxHealthIs0()
    {
        /** @var CharacterRepository $repository */
        $repository = $this->g->getEntityManager()->getRepository(Character::class);

        $command = $this->getCommand();

        $command->execute([
            "name" => "Gandalf the Unhealthy",
            "--maxHealth" => 0,
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[WARNING]", $output);
        $this->assertStringContainsString("The character will have 0 max health and will be permanently dead.", $output);

        // Check the database, too.
        $this->g->getEntityManager()->clear();
        $character = $repository->findOneBy(["name" => "Gandalf the Unhealthy"]);
        $this->assertNotNull($character);
        $this->assertSame("Gandalf the Unhealthy", $character->getName());
        $this->assertSame(1, $character->getLevel());
        $this->assertSame(0, $character->getMaxHealth());
        $this->assertSame(0, $character->getHealth());
    }
}