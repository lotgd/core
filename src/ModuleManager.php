<?php
declare (strict_types=1);

namespace LotGD\Core;

use Composer\Package\PackageInterface;
use Doctrine\ORM\EntityManagerInterface;

use LotGD\Core\BootstrapInterface;
use LotGD\Core\Exceptions\KeyNotFoundException;
use LotGD\Core\Exceptions\ModuleAlreadyExistsException;
use LotGD\Core\Exceptions\ModuleDoesNotExistException;
use LotGD\Core\Models\Module as ModuleModel;

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
        $name = $package->getName();
        $extra = $package->getExtra();
        if (!empty($extra['subscriptions'])) {
            $subscriptions = array();

            // Minimal scrub to the subscriptions list.
            foreach ($extra['subscriptions'] as $s) {
                if (!is_string($s))
                {
                    $this->g->getLogger()->error("Module {$name} has invalid event subscription: {$s}.");
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
     * @throws WrongTypeException if an event subscription class does not implement the Module
     * interface or the pattern is not a valid regular expression.
     */
    public function register(string $library, PackageInterface $package)
    {
        $m = $this->g->getEntityManager()->getRepository(ModuleModel::class)->find($library);
        if ($m) {
            throw new ModuleAlreadyExistsException($library);
        } else {
            // TODO: handle error cases here.
            $m = new ModuleModel($library);
            $m->save($this->g->getEntityManager());

            $name = $package->getName();

            $class = $package->getExtra()['class'];
            if (!isset($package->getExtra()['class']) ||
                !is_string($class))
            {
                throw new KeyNotFoundException("Module {$name} is missing a valid 'class' entry in its extra field.");
            }
            try {
                $klass = new \ReflectionClass($class);
            } catch (\LogicException $e) {
                throw new ClassNotFoundException("Could not load class ${class} while registering module {$name}.");
            } catch (\ReflectionException $e) {
                throw new ClassNotFoundException("Could not find class ${class} while registering module {$name}.");
            }

            // Verify that the class is a module class.
            $interface = Module::class;
            if (!$klass->implementsInterface($interface)) {
                throw new WrongTypeException("Class {$class} does not implement {$interface} while registering module {$name}.");
            }

            // Subscribe to the module's events.
            $subscriptions = ModuleManager::getPackageSubscriptions($package);
            foreach ($subscriptions as $s) {
                $this->g->getEventManager()->subscribe($s, $class, $library);
            }

            // Run the module's onRegister handler.
            $class::onRegister($this->g);
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
        $m = $this->g->getEntityManager()->getRepository(ModuleModel::class)->find($library);
        if (!$m) {
            throw new ModuleDoesNotExistException($library);
        } else {
            // TODO: handle error cases here.
            $m->delete($this->g->getEntityManager());

            $class = $package->getExtra()['class'];

            // Unsubscribe the module's events.
            $subscriptions = ModuleManager::getPackageSubscriptions($package);
            foreach ($subscriptions as $s) {
                try {
                    $this->g->getEventManager()->unsubscribe($s, $class, $library);
                } catch (SubscriptionNotFoundException $e) {
                    // TODO: log this but continue on.
                }
            }

            // Run the module's onUnregister handler.
            $class::onUnregister($this->g);
        }
    }

    /**
     * Returns the list of currently registered modules.
     * @return array<Module> Array of modules.
     */
    public function getModules(): array {
        return $this->g->getEntityManager()->getRepository(ModuleModel::class)->findAll();
    }

    /**
     * Validate that all modules are installed correctly. Currently checks for
     * all the proper event subscriptions.
     * @return array of strings describing issues. An empty array is returned
     * on successful validation.
     */
    public function validate(): array
    {
        $result = array();

        $modules = $this->getModules();
        $packages = $this->g->getComposerManager()->getModulePackages();

        // Quick validation for the count of the modules.
        $diff = count($packages) - count($modules);
        if ($diff < 0) {
            $d = -$diff;
            array_push($result, "Error: Found {$d} more installed modules than there are configured with Composer.");
        }
        if ($diff > 0) {
            array_push($result, "Error: Found {$diff} more modules configured with Composer than installed.");
        }

        // Validate event subscriptions.
        // TODO: Replace this n^2 algorithm to valiate event subscriptions with something faster. :)
        $currentSubscriptions = $this->g->getEventManager()->getSubscriptions();
        foreach ($packages as $p) {
            $name = $p->getName();
            $class = $p->getExtra()['class'];

            $expectedSubscriptions = ModuleManager::getPackageSubscriptions($p);
            $currentSubscriptionsForThisPackage = array_filter($currentSubscriptions, function($s) use ($name) {
                return $s->getLibrary() === $name;
            });

            if (count($currentSubscriptionsForThisPackage) > count($expectedSubscriptions)) {
                array_push($result, "Warning: There are event subscriptions for module ${name} not present in the configuration for that module.");
            }
            foreach ($expectedSubscriptions as $e) {
                $found = false;
                foreach ($currentSubscriptionsForThisPackage as $c) {
                    // Count the subscriptions for this
                    if ($c->getPattern() === $e &&
                        $c->getClass() === $class &&
                        $c->getLibrary() === $p->getName())
                    {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $pattern = $e;
                    $class = $class;
                    array_push($result, "Error: Couldn't find subscription ({$pattern} => ${class}) for module {$name}.");
                }
            }
        }

        return $result;
    }
}
