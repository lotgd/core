<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\CharacterCommands;

use LotGD\Core\Console\Command\Character\CharacterEditCommand;
use LotGD\Core\Game;
use LotGD\Core\Models\Character;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CharacterEditCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "character";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new CharacterEditCommand($this->g));
    }

    public function testIfWrongCharacterIdReturnsInFailure()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "10000000-0000-0000-0000-000000000000",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
        $this->assertStringContainsString("[ERROR]", $output);
        $this->assertStringContainsString("not found", $output);
    }

    public function testIfExistingCharacterIsFound()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "10000000-0000-0000-0000-000000000001",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[NOTE]", $output);
        $this->assertStringContainsString("Nothing was changed", $output);
    }

    public function testIfChangingLevelToValuesBelowOneResultsInAnError()
    {
        $command = $this->getCommand();
        $levels_to_test = [-5, 0];
        foreach ($levels_to_test as $level) {
            $command = $this->getCommand();
            $command->execute([
                "id" => "10000000-0000-0000-0000-000000000001",
                "--level" => $level,
            ]);
            $output = $command->getDisplay();

            $this->assertSame(Command::FAILURE, $command->getStatusCode());
            $this->assertStringContainsString("[ERROR]", $output);
            $this->assertStringContainsString("below 1", $output);
            $this->assertStringNotContainsString("Nothing was changed", $output);

            $character = $this->g->getEntityManager()->getRepository(Character::class)
                ->find("10000000-0000-0000-0000-000000000001");
            $this->assertSame(1, $character->getLevel());
        }
    }

    public function testIfChangingLevelToValuesBetween1And15Succeeds()
    {
        $command = $this->getCommand();
        $levels_to_test = range(1, 15);
        foreach ($levels_to_test as $level) {
            $command = $this->getCommand();
            $command->execute([
                "id" => "10000000-0000-0000-0000-000000000001",
                "--level" => $level,
            ]);
            $output = $command->getDisplay();

            $this->assertSame(Command::SUCCESS, $command->getStatusCode());

            if ($level === 1) {
                $this->assertStringContainsString("[NOTE]", $output);
                $this->assertStringContainsString("Nothing was changed", $output);
            } else {
                $this->assertStringContainsString("[OK]", $output);
                $this->assertStringContainsString("The character was successfully changed", $output);
                $this->assertStringNotContainsString("Nothing was changed", $output);
            }

            // Test if change was reflected in the model, too.
            $this->g->getEntityManager()->clear(); // important to refetch the model. Makes sure the command flushes.
            $character = $this->g->getEntityManager()->getRepository(Character::class)
                ->find("10000000-0000-0000-0000-000000000001");
            $this->assertSame($level, $character->getLevel());
        }
    }

    public function testIfCharacterAtFullHealthDoesNotChangeAnything()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "10000000-0000-0000-0000-000000000002",
            "--heal" => null,
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[NOTE]", $output);
        $this->assertStringContainsString("Character is already at full health.", $output);
        $this->assertStringContainsString("Nothing was changed", $output);
    }

    public function testIfDeadCharacterCannotGetHealed()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "10000000-0000-0000-0000-000000000001",
            "--heal" => null,
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
        $this->assertStringContainsString("[ERROR]", $output);
        $this->assertStringContainsString("Cannot heal a dead character. Use --revive instead.", $output);
        $this->assertStringNotContainsString("Nothing was changed", $output);
    }

    public function testIfCharacterBelowFullHealthGetsHealed()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "10000000-0000-0000-0000-000000000004",
            "--heal" => null,
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringContainsString("Character was restored to full health (80 to 90).", $output);
        $this->assertStringNotContainsString("Nothing was changed", $output);

        // Test if change was reflected in the model, too.
        $this->g->getEntityManager()->clear(); // important to refetch the model. Makes sure the command flushes.
        $character = $this->g->getEntityManager()->getRepository(Character::class)
            ->find("10000000-0000-0000-0000-000000000004");
        $this->assertSame($character->getMaxHealth(), $character->getHealth());
    }

    public function testIfAliveCharacterCannotGetRevived()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "10000000-0000-0000-0000-000000000002",
            "--revive" => "",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
        $this->assertStringContainsString("[ERROR]", $output);
        $this->assertStringContainsString("Character is already alive. Use --heal instead.", $output);
        $this->assertStringNotContainsString("Nothing was changed", $output);
    }

    public function testIfDeadCharacterGetsCompletelyRevivedWhenReviveValueIsNotGiven()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "10000000-0000-0000-0000-000000000001",
            "--revive" => null,
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringContainsString("Character was revived with 100 of health points (max: 100).", $output);
        $this->assertStringNotContainsString("Nothing was changed", $output);

        $this->g->getEntityManager()->clear(); // important to refetch the model. Makes sure the command flushes.
        $character = $this->g->getEntityManager()->getRepository(Character::class)
            ->find("10000000-0000-0000-0000-000000000001");
        $this->assertSame($character->getMaxHealth(), $character->getHealth());
    }

    public function testIfDeadCharacterGetsHealedAccordingToTheDesiredAmount()
    {
        $amounts = [
            [0, 1],
            [0.1, 10],
            [0.5, 50],
            [0.99, 99],
            ["10%", 10],
            ["50 %", 50],
            ["50.5%", 51],
        ];
        $command = $this->getCommand();

        foreach ($amounts as [$amount, $newHealth]) {
            // Kill character first.
            $this->g->getEntityManager()->getRepository(Character::class)
                ->find("10000000-0000-0000-0000-000000000001")->setHealth(0);

            $command->execute([
                "id" => "10000000-0000-0000-0000-000000000001",
                "--revive" => strval($amount),
            ]);
            $output = $command->getDisplay();

            $this->assertSame(Command::SUCCESS, $command->getStatusCode());
            $this->assertStringContainsString("[OK]", $output);
            $this->assertStringContainsString("Character was revived with {$newHealth} of health points (max: 100).", $output);
            $this->assertStringNotContainsString("Nothing was changed", $output);


            $this->g->getEntityManager()->clear(); // important to refetch the model. Makes sure the command flushes.
            $character = $this->g->getEntityManager()->getRepository(Character::class)
                ->find("10000000-0000-0000-0000-000000000001");
            $this->assertSame($newHealth, $character->getHealth());
        }
    }

    public function testIfADeadCharacterCannotGetKilled()
    {
        // Kill character first.
        $this->g->getEntityManager()->getRepository(Character::class)
            ->find("10000000-0000-0000-0000-000000000001")->setHealth(0);
        $this->g->getEntityManager()->flush();
        $this->g->getEntityManager()->clear();

        $command = $this->getCommand();
        $command->execute([
            "id" => "10000000-0000-0000-0000-000000000001",
            "--kill" => true,
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
        $this->assertStringContainsString("[ERROR]", $output);
        $this->assertStringContainsString("What is dead may never die.", $output);
        $this->assertStringNotContainsString("Nothing was changed", $output);

        $this->g->getEntityManager()->clear();
        $character = $this->g->getEntityManager()->getRepository(Character::class)
            ->find("10000000-0000-0000-0000-000000000001");

        $this->assertFalse($character->isAlive());
        $this->assertSame(0, $character->getHealth());
    }

    public function testIfALivingCharacterCanGetKilled()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "10000000-0000-0000-0000-000000000002",
            "--kill" => true,
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringContainsString("Character was killed.", $output);
        $this->assertStringNotContainsString("Nothing was changed", $output);


        $character = $this->g->getEntityManager()->getRepository(Character::class)
            ->find("10000000-0000-0000-0000-000000000002");

        $this->assertFalse($character->isAlive());
        $this->assertSame(0, $character->getHealth());
    }

    public function testThatMaxHealthCannotBeSetSmallerThanZero()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "10000000-0000-0000-0000-000000000002",
            "--maxHealth" => -1,
        ]);
        $output = $command->getDisplay();


        $this->assertSame(Command::FAILURE, $command->getStatusCode());
        $this->assertStringContainsString("[ERROR]", $output);
        $this->assertStringContainsString("Cannot set maximum health below 0.", $output);
        $this->assertStringNotContainsString("Nothing was changed", $output);
    }

    public function testThatMaxHealthCanBeChangedAndThatCharacterHealthChangesProportionally()
    {
        $tests = [
            [200, 50, 100],
            [200, 100, 200],
            [200, 0, 0],
        ];
        $command = $this->getCommand();

        foreach ($tests as [$newMaxHealth, $healthBefore, $healthAfter]) {
            // Get a character and set the health accordingly
            $character = $this->g->getEntityManager()->getRepository(Character::class)
                ->find("10000000-0000-0000-0000-000000000001");
            $character->setHealth($healthBefore);
            $character->setMaxHealth(100);
            $this->g->getEntityManager()->flush();
            $this->g->getEntityManager()->clear();

            // Run the command
            $command->execute([
                "id" => "10000000-0000-0000-0000-000000000001",
                "--maxHealth" => $newMaxHealth,
            ]);
            $output = $command->getDisplay();

            // Assert the output
            $this->assertSame(Command::SUCCESS, $command->getStatusCode());
            $this->assertStringContainsString("[OK]", $output);
            $this->assertStringContainsString("Character has new maximum health of {$newMaxHealth} (current health is {$healthAfter}).", $output);
            $this->assertStringNotContainsString("Nothing was changed", $output);

            // Assert that the change is reflected in character model
            $this->g->getEntityManager()->clear();
            $character = $this->g->getEntityManager()->getRepository(Character::class)
                ->find("10000000-0000-0000-0000-000000000001");

            $this->assertSame($newMaxHealth, $character->getMaxHealth());
            $this->assertSame($healthAfter, $character->getHealth());
        }
    }
}