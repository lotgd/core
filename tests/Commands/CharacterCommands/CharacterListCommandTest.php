<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\CharacterCommands;

use LotGD\Core\Console\Command\Character\CharacterListCommand;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CharacterListCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "character";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new CharacterListCommand($this->g));
    }

    public function testIfCommandRunsWithoutArguments()
    {
        $command = $this->getCommand();
        $command->execute([]);
        $output = $command->getDisplay();

        // Assertions
        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("Testcharacter 1", $output);
        $this->assertStringContainsString("Testcharacter 2", $output);
        $this->assertStringNotContainsString("Testcharacter 3", $output); // 3 is soft-deleted
        $this->assertStringContainsString("Testcharacter 4", $output);
    }

    public function testIfIncludeSoftDeletedOptionsAlsoShowsSoftDeletedCharacters()
    {
        $command = $this->getCommand();
        $command->execute([
            "--includeSoftDeleted" => true
        ]);
        $output = $command->getDisplay();

        // Assertions
        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("Testcharacter 1", $output);
        $this->assertStringContainsString("Testcharacter 2", $output);
        $this->assertStringContainsString("*Testcharacter 3", $output);
        $this->assertStringContainsString("Testcharacter 4", $output);
    }

    public function testIfOnlySoftDeletedOptionOptionOnlyShowsSoftDeletedEntries()
    {
        $command = $this->getCommand();
        $command->execute([
            "--onlySoftDeleted" => true
        ]);
        $output = $command->getDisplay();

        // Assertions
        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringNotContainsString("Testcharacter 1", $output);
        $this->assertStringNotContainsString("Testcharacter 2", $output);
        $this->assertStringContainsString("Testcharacter 3", $output);
        $this->assertStringNotContainsString("*Testcharacter 3", $output);
        $this->assertStringNotContainsString("Testcharacter 4", $output);
    }
}