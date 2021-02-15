<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Commands\SceneCommands;

use LotGD\Core\Console\Command\Scene\SceneShowCommand;
use LotGD\Core\Tests\CoreModelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class SceneShowCommandTest extends CoreModelTestCase
{
    /** @var string default data set */
    protected $dataset = "scene-2";

    protected function getCommand(): CommandTester
    {
        return new CommandTester(new SceneShowCommand($this->g));
    }

    public function testIfCommandFailsIfNoSceneIdWasGiven()
    {
        $command = $this->getCommand();

        $this->expectException(RuntimeException::class);
        $command->execute([]);
    }

    public function testIfCommandSucceedsIfIdWasFound()
    {
        $command = $this->getCommand();
        $command->execute([
            "id" => "30000000-0000-0000-0000-000000000006"
        ]);
        $output = $command->getDisplay();

        $this->assertSame(Command::SUCCESS, $command->getStatusCode());

        $this->assertStringContainsString("Connection Test Scene", $output);
        $this->assertStringContainsString("30000000-0000-0000-0000-000000000006", $output);
        $this->assertStringContainsString("LotGD\\Core\\Tests\\SceneTemplates\\Village", $output);
        $this->assertStringContainsString("This is a connection test scene", $output);

        $this->assertStringContainsString("Group One (id=lotgd/tests/testscene/one)", $output);
        $this->assertStringContainsString("Group Two (id=lotgd/tests/testscene/two)", $output);
        $this->assertStringContainsString("Group Three (id=lotgd/tests/testscene/three)", $output);

        $this->assertStringContainsString("this <=> The Village (id=30000000-0000-0000-0000-000000000001)", $output);
        $this->assertStringContainsString("this (on lotgd/tests/testscene/three) => The Weaponry (id=30000000-0000-0000-0000-000000000003)", $output);
        $this->assertStringContainsString("this (on lotgd/tests/testscene/three) <= The Forest (id=30000000-0000-0000-0000-000000000002)", $output);
    }
}