<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\CharacterCommands;

use LotGD\Core\Console\Command\Character\CharacterResetViewpointCommand;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CharacterResetViewpointCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "viewpoints";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new CharacterResetViewpointCommand($this->g));
    }

    public function testIfCommandFailesIfCharacterIdHasNotBeenFound()
    {
        $command = $this->getCommand();
        $command->execute(["id" => "10000000-0000-0000-0000-000000000000"]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
    }

    public function testIfCommandReportsIfCharacterDoesNotHaveAViewpoint()
    {
        $command = $this->getCommand();
        $command->execute(["id" => "10000000-0000-0000-0000-000000000001"]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringNotContainsString("[OK]", $output);
        $this->assertStringContainsString("[INFO]", $output);
        $this->assertStringContainsString("Character does not have a viewpoint yet.", $output);
    }

    public function testIfCommandSucceedsOfCharacterHasAViewpoint()
    {
        $command = $this->getCommand();
        $command->execute(["id" => "10000000-0000-0000-0000-000000000002"]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringNotContainsString("[INFO]", $output);
        $this->assertStringContainsString("Viewpoint was successfully reset.", $output);
    }
}