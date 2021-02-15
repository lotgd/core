<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\SceneCommands;

use LotGD\Core\Console\Command\Scene\SceneListCommand;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class SceneListCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "scene-2";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new SceneListCommand($this->g));
    }

    public function testIfCommandSucceedsWithoutAArguments()
    {
        $command = $this->getCommand();
        $command->execute([]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());

        $dataset = $this->getDataSet();
        $databaseScenes = $dataset["scenes"];
        $databaseSceneConnections = $dataset["scene_connections"];
        $connections = [];
        foreach ($databaseSceneConnections as $connection) {
            if (!isset($connections[$connection["outgoingScene"]])) {
                $connections[$connection["outgoingScene"]] = 0;
            }

            $connections[$connection["outgoingScene"]] += 1;


            if (!isset($connections[$connection["incomingScene"]])) {
                $connections[$connection["incomingScene"]] = 0;
            }

            $connections[$connection["incomingScene"]] += 1;
        }

        foreach ($databaseScenes as $scene) {
            // Assert details on the list
            $this->assertStringContainsString($scene["id"], $output);
            $this->assertStringContainsString($scene["title"], $output);
            if ($scene["template"]) {
                $this->assertStringContainsString($scene["template"], $output);
            }
            if (isset($connections[$scene["id"]])) {
                $this->assertStringContainsString((string)$connections[$scene["id"]], $output);
            } else {
                $this->assertStringContainsString("0", $output);
            }
        }
    }
}