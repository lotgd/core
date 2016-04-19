<?php
declare (strict_types = 1);

namespace LotGD\Core;

use LotGD\Core\Models\Scene;
use LotGD\Core\Models\Action;

/**
 * Primary method of interacting with LotGD. Construct a Game and use it for
 * your main loop in the crate.
 */
class Game
{
  /**
   * Get the screne for the current user. This is idempotent (i.e., same thing will
   * be returned with no side effects, other than possible logging) until an
   * action is taken (see takeAction).
   *
   * @return Scene
   */
    public function getScene(): Scene
    {
    }

  /**
   * Change the state of the game by taking an action currently available to the
   * user. Returns a possible action to take as a result of this action.
   *
   * @return Action|null
   */
    public function takeAction(Action $action): Action
    {
    }
}
