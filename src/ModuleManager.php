<?php
declare (strict_types=1);

namespace LotGD\Core;

use LotGD\Core\Models\Module;
use LotGD\Core\Exceptions\ModuleAlreadyExistsException;
use LotGD\Core\Exceptions\ModuleDoesNotExistException;
use Composer\Package\PackageInterface;

/**
 * Handles the adding and removing of modules to the game.
 */
class ModuleManager
{
    private static function getPackageSubscriptions(PackageInterface $package)
    {
        $extra = $package->getExtra();
        return $extra['subscriptions'];
    }

  /**
   * Called when a module is added to the system. Performs setup tasks like
   * registering the events this module responds to.
   *
   * @param string $library Name of the module, in 'vendor/module-name' format.
   * @param PackageInterface $package Composer package containing this module.
   * @throws ModuleAlreadyExistsException if the module is already installed.
   * @throws ClassNotFoundException if an event subscription class cannot be resolved.
   * @throws WrongTypeException if an event subscription class does not implement the EventHandler
   * interface or the pattern is not a valid regular expression.
   */
    public static function register(string $library, PackageInterface $package)
    {
        $m = Module::find($library);
        if ($m) {
            throw new ModuleAlreadyExistsException($library);
        } else {
            // TODO: handle error cases here.
            Module::create([
            "library" => $library
            ]);

            EventManager $em = new EventManager();

            // Subscribe to the module's events.
            $subscriptions = ModuleManager::getPackageSubscriptions($package);
            foreach ($subscriptions as $s) {
                $pattern = $s['pattern'];
                $class = $s['class'];

                $em->subscribe($pattern, $class);
            }
        }
    }

  /**
   * Called when a module is removed from the system. Performs teardown tasks like
   * unregistering the events this module responds to.
   *
   * @param string $library Name of the module, in 'vendor/module-name' format.
   * @throws ModuleDoesNotExistException if the module is not installed.
   */
    public static function unregister(string $library)
    {
        $m = Module::find($library);
        if (!$m) {
            throw new ModuleDoesNotExistException($library);
        } else {
            // TODO: handle error cases here.
            $m->delete();

            // Subscribe to the module's events.
            $subscriptions = ModuleManager::getPackageSubscriptions($package);
            foreach ($subscriptions as $s) {
                $pattern = $s['pattern'];
                $class = $s['class'];

                try {
                    $em->unsubscribe($pattern, $class);
                } catch (SubscriptionNotFoundException $e) {
                    // TODO: log this but continue on.
                }
            }
        }
    }
}
