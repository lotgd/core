<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\SceneCommands;

use LotGD\Core\Console\Command\Scene\SceneDisconnectCommand;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class SceneDisconnectCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "scene-2";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new SceneDisconnectCommand($this->g));
    }

    public function testIfCommandFailsIfNoSceneWasGiven()
    {
        $command = $this->getCommand();

        $this->expectException(RuntimeException::class);
        $command->execute([]);
    }

    public function testIfCommandFailsIfScene1WasNotGiven()
    {
        $command = $this->getCommand();

        $this->expectException(RuntimeException::class);
        $command->execute([
            "scene2" => "A"
        ]);
    }

    public function testIfCommandFailsIfScene2WasNotGiven()
    {
        $command = $this->getCommand();

        $this->expectException(RuntimeException::class);
        $command->execute([
            "scene1" => "A"
        ]);
    }

    public function testIfCommandFailsIfScene1WasNotFound()
    {
        $command = $this->getCommand();
        $command->execute([
            "scene1" => "30000000-0000-0000-0000-000000000000",
            "scene2" => "30000000-0000-0000-0000-000000000005",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
        $this->assertStringContainsString("[ERROR]", $output);
        $this->assertStringContainsString("Scene with id 30000000-0000-0000-0000-000000000000 was not found.", $output);
        $this->assertStringNotContainsString("[OK]", $output);
    }

    public function testIfCommandFailsIfScene2WasNotFound()
    {
        $command = $this->getCommand();
        $command->execute([
            "scene1" => "30000000-0000-0000-0000-000000000005",
            "scene2" => "30000000-0000-0000-0000-000000000000",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
        $this->assertStringContainsString("[ERROR]", $output);
        $this->assertStringContainsString("Scene with id 30000000-0000-0000-0000-000000000000 was not found.", $output);
        $this->assertStringNotContainsString("[OK]", $output);
    }

    public function testIfCommandFailsWhenBothScenesDontShareAConnection()
    {
        $command = $this->getCommand();
        $command->execute([
            "scene1" => "30000000-0000-0000-0000-000000000005",
            "scene2" => "30000000-0000-0000-0000-000000000001",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
        $this->assertStringContainsString("[ERROR]", $output);
        $this->assertStringContainsString("The given scenes do not share a connection.", $output);
        $this->assertStringNotContainsString("[OK]", $output);
    }

    public function testIfCommandSucceedsWhenBothScenesShareAConnection()
    {
        $command = $this->getCommand();
        $command->execute([
            "scene1" => "30000000-0000-0000-0000-000000000001",
            "scene2" => "30000000-0000-0000-0000-000000000002",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringContainsString("The connections between the two given scenes was removed.", $output);
        $this->assertStringNotContainsString("[ERROR]", $output);
    }
}