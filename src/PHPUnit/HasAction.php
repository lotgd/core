<?php
declare(strict_types=1);

namespace LotGD\Core\PHPUnit;

use LotGD\Core\Action;
use LotGD\Core\Models\Viewpoint;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\InvalidArgumentException;

class HasAction extends Constraint
{
    public function __construct(
        private array $actionParams,
        private ?string $groupTitle = null,
    ) {

    }

    public function count(): int
    {
        return 1;
    }

    public function toString(): string
    {
        if ($this->groupTitle) {
            return "contains action {$this->actionParams[0]}: {$this->actionParams[1]} under the groupTitle {$this->groupTitle}";
        }

        return "contains action {$this->actionParams[0]}: {$this->actionParams[1]}";
    }

    public function evaluate($other, string $description = '', bool $returnResult = false): ?bool
    {
        $action = $this->searchAction(
            viewpoint: $other,
            actionParams: $this->actionParams,
            groupTitle: $this->groupTitle
        );

        if (is_null($action)) {
            if ($returnResult) {
                return false;
            } else {
                throw new ExpectationFailedException(trim($description));
            }
        } else {
            return true;
        }
    }

    protected function searchAction(Viewpoint $viewpoint, array $actionParams, ?string $groupTitle = null): ?Action
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
                if ($action->$methodToCheck() == $valueToHave and (!is_null($groupTitle) or $group->getTitle() === $groupTitle)) {
                    $found = $action;
                }
            }
        }

        return $found;
    }
}