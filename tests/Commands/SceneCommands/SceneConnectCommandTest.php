<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\SceneCommands;

use LotGD\Core\Console\Command\Scene\SceneConnectCommand;
use LotGD\Core\Models\Scene;
use LotGD\Core\Models\SceneConnection;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class SceneConnectCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "scene-2";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new SceneConnectCommand($this->g));
    }

    public function testIfCommandFailsIfNoOutgoingOrIncomingSceneWasGiven()
    {
        $command = $this->getCommand();

        $this->expectException(RuntimeException::class);
        $command->execute([]);
    }

    public function testIfCommandFailsIfNoIncomingSceneWasGiven()
    {
        $command = $this->getCommand();

        $this->expectException(RuntimeException::class);
        $command->execute([
            "outgoing" => "30000000-0000-0000-0000-000000000005",
        ]);
    }

    public function testIfCommandFailsIfNoOutgoingWasGiven()
    {
        $command = $this->getCommand();

        $this->expectException(RuntimeException::class);
        $command->execute([
            "incoming" => "30000000-0000-0000-0000-000000000004",
        ]);
    }

    public function testIfCommandFailsIfOutgoingSceneDoesNotExist()
    {
        $command = $this->getCommand();
        $command->execute([
            "outgoing" => "30000000-0000-0000-0000-000000000000",
            "incoming" => "30000000-0000-0000-0000-000000000004",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
        $this->assertStringNotContainsString("[OK]", $output);
        $this->assertStringContainsString("[ERROR]", $output);
    }

    public function testIfCommandFailsIfIncomingSceneDoesNotExist()
    {
        $command = $this->getCommand();
        $command->execute([
            "outgoing" => "30000000-0000-0000-0000-000000000005",
            "incoming" => "30000000-0000-0000-0000-000000000000",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
        $this->assertStringNotContainsString("[OK]", $output);
        $this->assertStringContainsString("[ERROR]", $output);
    }

    public function testIfScenesGetConnected()
    {
        $command = $this->getCommand();
        $command->execute([
            "outgoing" => "30000000-0000-0000-0000-000000000005",
            "incoming" => "30000000-0000-0000-0000-000000000004",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringNotContainsString("[ERROR]", $output);
        $this->assertStringContainsString("The two scenes were successfully connected.", $output);

        $this->getEntityManager()->clear();
        $sceneRepository = $this->getEntityManager()->getRepository(Scene::class);

        /** @var Scene $outgoing */
        $outgoing = $sceneRepository->find("30000000-0000-0000-0000-000000000005");
        /** @var Scene $outgoing */
        $incoming = $sceneRepository->find("30000000-0000-0000-0000-000000000004");

        $this->assertTrue($outgoing->isConnectedTo($incoming));
    }

    public function testIfCommandFailsIfScenesAreAlreadyConnected()
    {
        $command = $this->getCommand();
        $command->execute([
            "outgoing" => "30000000-0000-0000-0000-000000000001",
            "incoming" => "30000000-0000-0000-0000-000000000002",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::FAILURE, $command->getStatusCode());
        $this->assertStringNotContainsString("[OK]", $output);
        $this->assertStringContainsString("[ERROR]", $output);
        $this->assertStringContainsString("Scenes were not connected. Reason: The given scene (ID 30000000-0000-0000-0000-000000000002) is already", $output);
        $this->assertStringContainsString("connected to this (ID 30000000-0000-0000-0000-000000000001) one..", $output);
    }

    public function testIfScenesGetConnectedOnOutgoingConnectionGroup()
    {
        $command = $this->getCommand();
        $command->execute([
            "outgoing" => "30000000-0000-0000-0000-000000000001",
            "incoming" => "30000000-0000-0000-0000-000000000005",
            "--outgoingGroupName" => "lotgd/tests/village/outside",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringNotContainsString("[ERROR]", $output);
        $this->assertStringContainsString("The two scenes were successfully connected.", $output);

        $this->getEntityManager()->clear();
        $sceneRepository = $this->getEntityManager()->getRepository(Scene::class);

        /** @var Scene $outgoing */
        $outgoing = $sceneRepository->find("30000000-0000-0000-0000-000000000001");
        /** @var Scene $outgoing */
        $incoming = $sceneRepository->find("30000000-0000-0000-0000-000000000005");

        $this->assertTrue($outgoing->isConnectedTo($incoming));

        $thatOne = null;
        /** @var SceneConnection $connection */
        foreach ($outgoing->getConnections() as $connection) {
            if (
                $connection->getOutgoingConnectionGroupName() === "lotgd/tests/village/outside"
                and $connection->getIncomingScene() === $incoming
            ) {
                $thatOne = true;
            }
        }

        $this->assertTrue($thatOne);
    }

    public function testIfScenesGetConnectedOnIncomingConnectionGroup()
    {
        $command = $this->getCommand();
        $command->execute([
            "outgoing" => "30000000-0000-0000-0000-000000000005",
            "incoming" => "30000000-0000-0000-0000-000000000002",
            "--incomingGroupName" => "lotgd/tests/forest/category",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringNotContainsString("[ERROR]", $output);
        $this->assertStringContainsString("The two scenes were successfully connected.", $output);

        $this->getEntityManager()->clear();
        $sceneRepository = $this->getEntityManager()->getRepository(Scene::class);

        /** @var Scene $outgoing */
        $outgoing = $sceneRepository->find("30000000-0000-0000-0000-000000000005");
        /** @var Scene $outgoing */
        $incoming = $sceneRepository->find("30000000-0000-0000-0000-000000000002");

        $this->assertTrue($outgoing->isConnectedTo($incoming));

        $thatOne = null;
        /** @var SceneConnection $connection */
        foreach ($outgoing->getConnections() as $connection) {
            if (
                $connection->getIncomingConnectionGroupName() === "lotgd/tests/forest/category"
                and $connection->getIncomingScene() === $incoming
            ) {
                $thatOne = true;
            }
        }

        $this->assertTrue($thatOne);
    }

    public function testIfScenesGetConnectedOnBothConnectionGroup()
    {
        $command = $this->getCommand();
        $command->execute([
            "outgoing" => "30000000-0000-0000-0000-000000000002",
            "incoming" => "30000000-0000-0000-0000-000000000003",
            "--outgoingGroupName" => "lotgd/tests/forest/category",
            "--incomingGroupName" => "lotgd/tests/weaponry/category",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringNotContainsString("[ERROR]", $output);
        $this->assertStringContainsString("The two scenes were successfully connected.", $output);

        $this->getEntityManager()->clear();
        $sceneRepository = $this->getEntityManager()->getRepository(Scene::class);

        /** @var Scene $outgoing */
        $outgoing = $sceneRepository->find("30000000-0000-0000-0000-000000000002");
        /** @var Scene $outgoing */
        $incoming = $sceneRepository->find("30000000-0000-0000-0000-000000000003");

        $this->assertTrue($outgoing->isConnectedTo($incoming));

        $thatOne = null;
        /** @var SceneConnection $connection */
        foreach ($outgoing->getConnections() as $connection) {
            if (
                $connection->getOutgoingConnectionGroupName() === "lotgd/tests/forest/category"
                and $connection->getIncomingConnectionGroupName() === "lotgd/tests/weaponry/category"
                and $connection->getIncomingScene() === $incoming
            ) {
                $thatOne = true;
            }
        }

        $this->assertTrue($thatOne);
    }

    public function testIfUnidirectionalSceneConnectionWorks()
    {
        $command = $this->getCommand();
        $command->execute([
            "outgoing" => "30000000-0000-0000-0000-000000000004",
            "incoming" => "30000000-0000-0000-0000-000000000002",
            "--directionality" => "1",
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());
        $this->assertStringContainsString("[OK]", $output);
        $this->assertStringNotContainsString("[ERROR]", $output);
        $this->assertStringContainsString("The two scenes were successfully connected.", $output);

        $this->getEntityManager()->clear();
        $sceneRepository = $this->getEntityManager()->getRepository(Scene::class);

        /** @var Scene $outgoing */
        $outgoing = $sceneRepository->find("30000000-0000-0000-0000-000000000004");
        /** @var Scene $outgoing */
        $incoming = $sceneRepository->find("30000000-0000-0000-0000-000000000002");

        $thatOne = null;
        /** @var SceneConnection $connection */
        foreach ($outgoing->getConnections() as $connection) {
            if (
                $connection->getIncomingScene() === $incoming
                and $connection->isDirectionality(1)
            ) {
                $thatOne = true;
            }
        }

        $this->assertTrue($thatOne);
    }
}