<?php
declare(strict_types=1);

namespace LotGD\Core\PHPUnit;

use Doctrine\Common\Collections\Collection;
use LotGD\Core\Action;
use LotGD\Core\Exceptions\ActionNotFoundException;
use LotGD\Core\Exceptions\SceneNotFoundException;
use LotGD\Core\Game;
use LotGD\Core\Models\BattleEvents\BuffMessageEvent;
use LotGD\Core\Models\Viewpoint;
use PDO;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\Count;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\Constraint\LogicalNot;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LotGDTestCase extends TestCase
{
    /**
     * Asserts if tables from a given PDO connection are equal to the original dataset
     *
     * @param array $before Previous dataset, in the style of $before[table] => [row1, row2], where each row is an associative array with each column name.
     * @param PDO $pdo
     * @param array|null $restrictToTables
     */
    public function assertDataWasKeptIntact(array $before, PDO $pdo, ?array $restrictToTables = null): void
    {
        foreach ($before as $table => $rowsBefore) {
            // Ignore table if $restrictToTables is an array and the table is not on the list.
            if (is_array($restrictToTables) and empty($restrictToTables[$table])) {
                continue;
            }

            // Get all rows from table
            $query = $pdo->query("SELECT * FROM `$table`");
            $rowsAfter = $query->fetchAll(PDO::FETCH_ASSOC);

            // Assert equal row counts
            static::assertThat(
                value: $rowsAfter,
                constraint: new Count(count($rowsBefore)),
                message: "Database assertion: Table <$table> does not match the expected number of rows.
                Expected was <".count($rowsBefore).">, but found was <".count($rowsAfter).">"
            );

            // Assert equal contents
            foreach ($rowsBefore as $key => $rowBefore) {
                foreach ($rowBefore as $field => $value) {
                    static::assertThat(
                        value: $rowsAfter[$key][$field],
                        constraint: new IsEqual($value),
                        message: "Database assertion: In table <$table>, field <$field> does not match expected value <$value>,
                        is <{$rowsAfter[$key][$field]}> instead."
                    );
                }
            }
        }

        $this->addToAssertionCount(1);
    }

    /**
     * Asserts that a certain BuffMessageEvent with a specific text is contained in the lst of events
     * @param Collection $events The list of events
     * @param string $battleEventText The text to test for
     * @param int $timesAtLeast Mininum number of times the message is expected to be in the event list
     * @param ?int $timesAtMax Maximum number of times the message is expected to be in the event list, or $timesAtLeast if null.
     */
    public function assertBuffEventMessageExists(
        Collection $events,
        string $battleEventText,
        int $timesAtLeast = 1,
        int $timesAtMax = null
    ) {
        $eventCounter = 0;
        foreach($events as $event) {
            if ($event instanceof BuffMessageEvent) {
                if ($battleEventText === $event->getMessage()) {
                    $eventCounter++;
                }
            }
        }

        if ($timesAtMax === null) {
            $timesAtMax = $timesAtLeast;
        }

        static::assertThat(
            value: $eventCounter,
            constraint: static::greaterThanOrEqual($timesAtLeast),
            message: "The desired message {$battleEventText} has been found to exist less than {$timesAtLeast} times",
        );

        static::assertThat(
            value: $eventCounter,
            constraint: static::lessThanOrEqual($timesAtMax),
            message: "The desired message {$battleEventText} has been found to exist more than {$timesAtLeast} times",
        );

        $this->addToAssertionCount(1);
    }

    /**
     * Helper method to take an action, or a series of actions, on a given viewpoint.
     *
     * @param Game $game
     * @param Viewpoint $viewpoint
     * @param array $actions
     * @throws ActionNotFoundException
     * @throws SceneNotFoundException
     */
    public function takeActions(Game $game, Viewpoint $viewpoint, array $actions)
    {
        foreach ($actions as $action) {
            foreach ($viewpoint->getActionGroups() as $group) {
                foreach ($group->getActions() as $a) {
                    if ($a->getDestinationSceneId() == $action or $a->getTitle() == $action) {
                        $game->takeAction($a->getId());
                        break 2;
                    }
                }
            }
        }
    }

    public function getAction(Viewpoint $viewpoint, array $actionParams, ?string $groupTitle = null): ?Action
    {
        if (count($actionParams) != 2) {
            throw InvalidArgumentException::create(2, "$actionParams is expected to be an array of exactly 2 items.");
        }

        if (is_string($actionParams[0]) === false) {
            throw InvalidArgumentException::create(2, "$actionParams[0] is expected to be a method.");
        }

        $methodToCheck = $actionParams[0];
        $valueToHave = $actionParams[1];
        $checkedOnce = false;


        $groups = $viewpoint->getActionGroups();
        $found = null;

        foreach ($groups as $group) {
            $actions = $group->getActions();
            foreach ($actions as $action) {
                if ($checkedOnce === false and method_exists($action, $methodToCheck) === false) {
                    throw InvalidArgumentException::create(2, "$actionParams[0] must be a valid method of " . Action::class . ".");
                } else {
                    $checkedOnce = True;
                }

                # Using KNF, !A or B is only false if A is true and B is not.
                if ($action->$methodToCheck() == $valueToHave and (is_null($groupTitle) or $group->getTitle() === $groupTitle)) {
                    $found = $action;
                }
            }
        }

        return $found;
    }

    /**
     * Asserts that a Viewpoint does not contain a given action.
     *
     * @param Viewpoint $viewpoint
     * @param array $actionParams
     * @param string|null $groupTitle
     * @param string $message
     */
    public function assertNotHasAction(
        Viewpoint $viewpoint,
        array $actionParams,
        ?string $groupTitle = null,
        string $message = '',
    ): void {
        $constraint = new LogicalNot(
            new HasAction($actionParams, $groupTitle)
        );

        static::assertThat($viewpoint, $constraint, $message);

        $this->addToAssertionCount(1);
    }

    /**
     * Asserts that a Viewpoint contains a given action.
     *
     * @param Viewpoint $viewpoint
     * @param array $actionParams, [$method, $value], where $method is a method of LotGD\Core\Action. Eg, ["getTitle", "The Forest"]
     * @param string|null $groupTitle
     * @param string $message
     */
    public function assertHasAction(
        Viewpoint $viewpoint,
        array $actionParams,
        ?string $groupTitle = null,
        string $message = '',
    ): void {
        $constraint = new HasAction($actionParams, $groupTitle);

        static::assertThat($viewpoint, $constraint, $message);

        $this->addToAssertionCount(1);
    }
}

