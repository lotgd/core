<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\SceneCommands;

use LotGD\Core\Console\Command\Scene\SceneAddCommand;
use LotGD\Core\Models\Scene;
use LotGD\Core\Tests\CoreModelTestCase;
use LotGD\Core\Tests\SceneTemplates\NewSceneSceneTemplate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class SceneAddCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "scene-2";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new SceneAddCommand($this->g));
    }

    public function testIfCommandFailsIfNoNameWasGiven()
    {
        $command = $this->getCommand();

        $this->expectException(RuntimeException::class);
        $command->execute([]);
    }

    public function testIfSceneGetsCreatedWithOnlyName()
    {
        $repository = $this->g->getEntityManager()->getRepository(Scene::class);
        $command = $this->getCommand();
        $command->execute([
            "title" => "A scene.",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringContainsString("was successfully created.", $output);

        // Check the database, too.
        $this->g->getEntityManager()->clear();
        /** @var Scene $scene */
        $scene = $repository->findOneBy(["title" => "A scene."]);
        $this->assertNotNull($scene);
        $this->assertSame("A scene.", $scene->getTitle());
        $this->assertSame("", $scene->getDescription());
        $this->assertNull($scene->getTemplate());
    }

    public function testIfSceneGetsCreatedWithDescription()
    {
        $repository = $this->g->getEntityManager()->getRepository(Scene::class);
        $command = $this->getCommand();
        $command->execute([
            "title" => "Another scene.",
            "description" => "The scenery is nice."
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringContainsString("was successfully created.", $output);

        // Check the database, too.
        $this->g->getEntityManager()->clear();
        /** @var Scene $scene */
        $scene = $repository->findOneBy(["title" => "Another scene."]);
        $this->assertNotNull($scene);
        $this->assertSame("Another scene.", $scene->getTitle());
        $this->assertSame("The scenery is nice.", $scene->getDescription());
        $this->assertNull($scene->getTemplate());
    }

    public function testIfSceneGetsCreatedWithValidSceneTemplate()
    {
        $repository = $this->g->getEntityManager()->getRepository(Scene::class);
        $command = $this->getCommand();
        $command->execute([
            "title" => "A templated scene.",
            "description" => "The scenery is nice.",
            "--template" => "LotGD\\Core\\Tests\\SceneTemplates\\NewSceneSceneTemplate"
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringContainsString("was successfully created.", $output);
        $this->assertStringNotContainsString("[WARNING]", $output);
        $this->assertStringNotContainsString("has not been found. Set to NULL instead.", $output);

        // Check the database, too.
        $this->g->getEntityManager()->clear();
        /** @var Scene $scene */
        $scene = $repository->findOneBy(["title" => "A templated scene."]);
        $this->assertNotNull($scene);
        $this->assertSame("A templated scene.", $scene->getTitle());
        $this->assertSame("The scenery is nice.", $scene->getDescription());
        $this->assertSame(NewSceneSceneTemplate::class, $scene->getTemplate()?->getClass());
    }

    public function testIfSceneGetsCreatedWithInvalidSceneTemplateButThrowsWarning()
    {
        $repository = $this->g->getEntityManager()->getRepository(Scene::class);
        $command = $this->getCommand();
        $command->execute([
            "title" => "A wrongly templated scene.",
            "description" => "The scenery is nice.",
            "--template" => "LotGD\\Core\\Tests\\SceneTemplates\\Darn"
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringContainsString("was successfully created.", $output);
        $this->assertStringContainsString("[WARNING]", $output);
        $this->assertStringContainsString("has not been found. Set to NULL instead.", $output);

        // Check the database, too.
        $this->g->getEntityManager()->clear();
        /** @var Scene $scene */
        $scene = $repository->findOneBy(["title" => "A wrongly templated scene."]);
        $this->assertNotNull($scene);
        $this->assertSame("A wrongly templated scene.", $scene->getTitle());
        $this->assertSame("The scenery is nice.", $scene->getDescription());
        $this->assertNull($scene->getTemplate());
    }
}