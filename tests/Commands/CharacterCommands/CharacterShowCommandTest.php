<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\CharacterCommands;

use LotGD\Core\Console\Command\Character\CharacterShowCommand;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CharacterShowCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "viewpoints";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new CharacterShowCommand($this->g));
    }

    public function testIfCommandFailesIfCharacterIdHasNotBeenFound()
    {
        $command = $this->getCommand();
        $command->execute(["id" => "10000000-0000-0000-0000-000000000000"]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
    }

    public function testIfCharacterShowCommandShowsCharacterInformationIfCharacterExists()
    {
        $command = $this->getCommand();
        $command->execute(["id" => "10000000-0000-0000-0000-000000000001"]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("About Character Char without a Scene", $output);
        $this->assertStringContainsString("No viewpoint yet", $output);
    }

    public function testIfCharacterWithViewpointGetsViewpointDisplayed()
    {
        $command = $this->getCommand();
        $command->execute(["id" => "10000000-0000-0000-0000-000000000002"]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("About Character Char with a Scene", $output);
        $this->assertStringNotContainsString("No viewpoint yet", $output);
        $this->assertStringContainsString("The Village", $output);
        $this->assertStringContainsString("This is the village.", $output);
    }

    public function testIfCharacterWithViewpointGetsOnlyViewpointDisplayedIfOptionIsGiven()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "10000000-0000-0000-0000-000000000002",
            "--onlyViewpoint" => true,
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringNotContainsString("About Character Char with a Scene", $output);
        $this->assertStringNotContainsString("No viewpoint yet", $output);
        $this->assertStringContainsString("The Village", $output);
        $this->assertStringContainsString("This is the village.", $output);
    }
}