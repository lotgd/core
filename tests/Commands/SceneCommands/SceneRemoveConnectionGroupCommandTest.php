<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\SceneCommands;

use LotGD\Core\Console\Command\Scene\SceneRemoveConnectionGroupCommand;
use LotGD\Core\Models\Scene;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class SceneRemoveConnectionGroupCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "scene-2";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new SceneRemoveConnectionGroupCommand($this->g));
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
            "groupName" => "nobody/nothing",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
        $this->assertStringContainsString("[ERROR]", $output);
        $this->assertStringContainsString("The scene with the ID 30000000-0000-0000-0000-000000000000 was not found.", $output);
        $this->assertStringNotContainsString("[OK]", $output);
    }

    public function testIfCommandFailsWhenSceneDoesNotHaveGivenConnectionGroup()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "30000000-0000-0000-0000-000000000001",
            "groupName" => "nobody/nothing",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
        $this->assertStringContainsString("[ERROR]", $output);
        $this->assertStringContainsString("The scene 30000000-0000-0000-0000-000000000001 does not have a connection group with the name", $output);
        $this->assertStringNotContainsString("[OK]", $output);
    }

    public function testIfCommandSuccessfullyRemovesGroup()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "30000000-0000-0000-0000-000000000001",
            "groupName" => "lotgd/tests/village/outside",
         ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringNotContainsString("[ERROR]", $output);
        $this->assertStringNotContainsString("//", $output);
        $this->assertStringContainsString(" was successfully removed", $output);

        // Assert on database level (make sure command calls flush)
        $this->getEntityManager()->clear();
        /** @var Scene $scene */
        $scene = $this->getEntityManager()->getRepository(Scene::class)->find("30000000-0000-0000-0000-000000000001");
        $this->assertNotNull($scene);
        $this->assertFalse($scene->hasConnectionGroup("lotgd/tests/village/outside"));
    }

    public function testIfCommandSuccessfullyRemovesGroupAndMovesConnectionDirectlyToTheOutgoingScene()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "30000000-0000-0000-0000-000000000001",
            "groupName" => "lotgd/tests/village/market",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringNotContainsString("[ERROR]", $output);
        $this->assertStringContainsString("//", $output);
        $this->assertStringContainsString("Updated connection to", $output);
        $this->assertStringContainsString(" was successfully removed", $output);

        // Assert on database level (make sure command calls flush)
        $this->getEntityManager()->clear();
        /** @var Scene $scene */
        $scene = $this->getEntityManager()->getRepository(Scene::class)->find("30000000-0000-0000-0000-000000000001");
        $otherScene = $this->getEntityManager()->getRepository(Scene::class)->find("30000000-0000-0000-0000-000000000003");
        $this->assertNotNull($scene);
        $connection = $scene->getConnectionTo($otherScene);
        $this->assertNotNull($connection);
        $this->assertNull($connection->getOutgoingConnectionGroupName());
        $this->assertFalse($scene->hasConnectionGroup("lotgd/tests/village/market"));
    }

    public function testIfCommandSuccessfullyRemovesGroupAndMovesConnectionDirectlyToTheIncomingScene()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "30000000-0000-0000-0000-000000000003",
            "groupName" => "lotgd/tests/weaponry/category",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringNotContainsString("[ERROR]", $output);
        $this->assertStringContainsString("//", $output);
        $this->assertStringContainsString("Updated connection to", $output);
        $this->assertStringContainsString(" was successfully removed", $output);

        // Assert on database level (make sure command calls flush)
        $this->getEntityManager()->clear();
        /** @var Scene $scene */
        $scene = $this->getEntityManager()->getRepository(Scene::class)->find("30000000-0000-0000-0000-000000000001");
        $otherScene = $this->getEntityManager()->getRepository(Scene::class)->find("30000000-0000-0000-0000-000000000003");
        $this->assertNotNull($scene);
        $connection = $scene->getConnectionTo($otherScene);
        $this->assertNotNull($connection);
        $this->assertNull($connection->getIncomingConnectionGroupName());
        $this->assertFalse($scene->hasConnectionGroup("lotgd/tests/weaponry/category"));
    }
}