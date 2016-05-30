<?php
declare (strict_types=1);

namespace LotGD\Core;

use Doctrine\ORM\EntityManagerInterface;

use LotGD\Core\Models\Module;
use LotGD\Core\Exceptions\ModuleAlreadyExistsException;
use LotGD\Core\Exceptions\ModuleDoesNotExistException;
use Composer\Package\PackageInterface;

/**
 * Handles the adding and removing of modules to the game.
 */
class ModuleManager
{
    private $g;

    /**
     * @param Game $g The game.
     */
    public function __construct(Game $g)
    {
        $this->g = $g;
    }

    private static function getPackageSubscriptions(PackageInterface $package): array
    {
        $extra = $package->getExtra();
        if (!empty($extra['subscriptions'])) {
            $subscriptions = array();

            // Minimal scrub to the subscriptions list.
            foreach ($extra['subscriptions'] as $s) {
                if (!isset($s['pattern']) ||
                    !isset($s['class']) ||
                    !is_string($s['pattern']) ||
                    !is_string($s['class']))
                {
                    // TODO: log this but continue on.
                    continue;
                }
                array_push($subscriptions, $s);
            }
            return $subscriptions;
        } else {
            return array();
        }
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
    public function register(string $library, PackageInterface $package)
    {
        $m = $this->g->getEntityManager()->getRepository(Module::class)->find($library);
        if ($m) {
            throw new ModuleAlreadyExistsException($library);
        } else {
            // TODO: handle error cases here.
            $m = new Module($library);
            $m->save($this->g->getEntityManager());

            // Subscribe to the module's events.
            $subscriptions = ModuleManager::getPackageSubscriptions($package);
            foreach ($subscriptions as $s) {
                $pattern = $s['pattern'];
                $class = $s['class'];

                $this->g->getEventManager()->subscribe($pattern, $class, $library);
            }
        }
    }

    /**
     * Called when a module is removed from the system. Performs teardown tasks like
     * unregistering the events this module responds to.
     *
     * @param string $library Name of the module, in 'vendor/module-name' format.
     * @param PackageInterface $package Composer package containing this module.
     * @throws ModuleDoesNotExistException if the module is not installed.
     */
    public function unregister(string $library, PackageInterface $package)
    {
        $m = $this->g->getEntityManager()->getRepository(Module::class)->find($library);
        if (!$m) {
            throw new ModuleDoesNotExistException($library);
        } else {
            // TODO: handle error cases here.
            $m->delete($this->g->getEntityManager());

            // Unsubscribe the module's events.
            $subscriptions = ModuleManager::getPackageSubscriptions($package);
            foreach ($subscriptions as $s) {
                $pattern = $s['pattern'];
                $class = $s['class'];

                try {
                    $this->g->getEventManager()->unsubscribe($pattern, $class, $library);
                } catch (SubscriptionNotFoundException $e) {
                    // TODO: log this but continue on.
                }
            }
        }
    }

    /**
     * Returns the list of currently registered modules.
     * @return array<Module> Array of modules.
     */
    public function getModules(): array {
        return $this->g->getEntityManager()->getRepository(Module::class)->findAll();
    }
}
