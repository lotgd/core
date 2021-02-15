<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\SceneCommands;

use LotGD\Core\Console\Command\Scene\SceneRemoveCommand;
use LotGD\Core\Models\Scene;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class SceneRemoveCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "scene-2";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new SceneRemoveCommand($this->g));
    }

    public function testIfCommandFailsIfNoSceneIdWasGiven()
    {
        $command = $this->getCommand();

        $this->expectException(RuntimeException::class);
        $command->execute([]);
    }

    public function testIfCommandFailsIfSceneIdDoesNotExist()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "30000000-0000-0000-0000-000000000000",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
        $this->assertStringContainsString("[ERROR]", $output);
        $this->assertStringContainsString("The scene with the ID 30000000-0000-0000-0000-000000000000 was not found.", $output);
        $this->assertStringNotContainsString("[OK]", $output);
    }

    public function testIfCommandFailsIfSceneWasMarkedAsNotRemoveable()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "30000000-0000-0000-0000-000000000005",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
        $this->assertStringContainsString("[ERROR]", $output);
        $this->assertStringContainsString("The scene with the ID 30000000-0000-0000-0000-000000000005 was marked as not ", $output);
        $this->assertStringNotContainsString("[OK]", $output);
    }

    public function testIfCommandSucceeds()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "30000000-0000-0000-0000-000000000001",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringContainsString("was successfully removed.", $output);
        $this->assertStringNotContainsString("[ERROR]", $output);

        // Assert database result
        $this->getEntityManager()->clear();

        $this->assertNull($this->getEntityManager()->getRepository(Scene::class)->find("30000000-0000-0000-0000-000000000001"));
    }
}