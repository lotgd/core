<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\SceneCommands;

use LotGD\Core\Console\Command\Scene\SceneAddConnectionGroupCommand;
use LotGD\Core\Models\Scene;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class SceneAddConnectionGroupCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "scene-2";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new SceneAddConnectionGroupCommand($this->g));
    }

    public function testIfCommandFailsIfNoNameWasGiven()
    {
        $command = $this->getCommand();

        $this->expectException(RuntimeException::class);
        $command->execute([]);
    }

    public function testIfCommandFailsIfSceneWasNotFound()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "30000000-0000-0000-0000-000000000000",
            "groupName" => "a/group/name",
            "groupTitle" => "The Abyss",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
        $this->assertStringContainsString("[ERROR]", $output);
        $this->assertStringNotContainsString("[OK]", $output);
        $this->assertStringContainsString("The requested scene with the ID 30000000-0000-0000-0000-000000000000 was not found.", $output);
    }

    public function testIfCommandGetsSuccessfullyAddedToScene()
    {
        $repository = $this->g->getEntityManager()->getRepository(Scene::class);
        $command = $this->getCommand();
        $command->execute([
            "id" => "30000000-0000-0000-0000-000000000001",
            "groupName" => "a/group/name",
            "groupTitle" => "The Abyss",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringNotContainsString("[ERROR]", $output);
        $this->assertStringContainsString("successfully added.", $output);

        // Check the database, too.
        $this->g->getEntityManager()->clear();
        /** @var Scene $scene */
        $scene = $repository->find("30000000-0000-0000-0000-000000000001");
        $this->assertNotNull($scene);
        $this->assertTrue($scene->hasConnectionGroup("a/group/name"));
        $this->assertNotNull($scene->getConnectionGroup("a/group/name"));
        $this->assertSame("The Abyss", $scene->getConnectionGroup("a/group/name")?->getTitle());
    }

    public function testIfCommandFailsIfGroupNameIsAlreadyInUse()
    {
        $repository = $this->g->getEntityManager()->getRepository(Scene::class);
        $command = $this->getCommand();
        $command->execute([
            "id" => "30000000-0000-0000-0000-000000000001",
            "groupName" => "lotgd/tests/village/outside",
            "groupTitle" => "The Abyss",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
        $this->assertStringNotContainsString("[OK]", $output);
        $this->assertStringContainsString("[ERROR]", $output);
        $this->assertStringContainsString("Cannot add a second group with the same name to this scene.", $output);
    }
}