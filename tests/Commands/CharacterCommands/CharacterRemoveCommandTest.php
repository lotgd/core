<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\CharacterCommands;

use LotGD\Core\Console\Command\Character\CharacterRemoveCommand;
use LotGD\Core\Models\Character;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CharacterRemoveCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "character";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new CharacterRemoveCommand($this->g));
    }

    public function testIfCommandFailesIfCharacterIdHasNotBeenFound()
    {
        $command = $this->getCommand();
        $command->execute(["id" => "10000000-0000-0000-0000-000000000000"]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
    }

    public function testIfNormalCharacterGetsDeleted()
    {
        $command = $this->getCommand();
        $command->execute(["id" => "10000000-0000-0000-0000-000000000001"]);
        $output = $command->getDisplay();

        // Assertions
        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringContainsString(" was successfully removed.", $output);
        $this->assertStringContainsString("Testcharacter 1", $output);

        // Check database, too
        $em = $this->g->getEntityManager();
        $em->clear();
        $character = $em->getRepository(Character::class)->findWithSoftDeleted("10000000-0000-0000-0000-000000000001");
        $this->assertNull($character);
    }

    public function testIfSoftDeletedCharacterGetsDeleted()
    {
        $command = $this->getCommand();
        $command->execute(["id" => "10000000-0000-0000-0000-000000000003"]);
        $output = $command->getDisplay();

        // Assertions
        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringContainsString("Character was marked as soft-deleted.", $output);
        $this->assertStringContainsString(" was successfully removed.", $output);
        $this->assertStringContainsString("Testcharacter 3", $output);

        // Check database, too
        $em = $this->g->getEntityManager();
        $em->clear();
        $character = $em->getRepository(Character::class)->findWithSoftDeleted("10000000-0000-0000-0000-000000000003");
        $this->assertNull($character);
    }

    public function testIfSoftDeletionFlagOnlySoftDeletesACharacter()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "10000000-0000-0000-0000-000000000002",
            "--soft" => true,
        ]);
        $output = $command->getDisplay();

        // Assertions
        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringContainsString("was successfully soft-deleted.", $output);
        $this->assertStringContainsString("Testcharacter 2", $output);

        // Check database, too
        $em = $this->g->getEntityManager();
        $em->clear();
        $character = $em->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000002");
        $this->assertNull($character);
        $character = $em->getRepository(Character::class)->findWithSoftDeleted("10000000-0000-0000-0000-000000000002");
        $this->assertNotNull($character);
    }
}