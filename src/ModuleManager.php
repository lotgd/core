<?php
declare (strict_types=1);

namespace LotGD\Core;

use Composer\Package\PackageInterface;
use Doctrine\ORM\EntityManagerInterface;

use LotGD\Core\PackageConfiguration;
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
     * Construct a module manager.
     * @param Game $g The game.
     */
    public function __construct(Game $g)
    {
        $this->g = $g;
    }

    /**
     * Called when a module is added to the system. Performs setup tasks like
     * registering the events this module responds to.
     *
     * @param LibraryConfiguration $library LibraryConfiguration representing this module.
     * @throws ModuleAlreadyExistsException if the module is already installed.
     * @throws ClassNotFoundException if an event subscription class cannot be resolved.
     * @throws WrongTypeException if an event subscription class does not implement the Module
     * interface or the pattern is not a valid regular expression.
     */
    public function register(LibraryConfiguration $library)
    {
        $name = $library->getName();
        $package = $library->getComposerPackage();

        $m = $this->g->getEntityManager()->getRepository(ModuleModel::class)->find($name);
        if ($m) {
            throw new ModuleAlreadyExistsException($name);
        } else {
            // TODO: handle error cases here.
            $m = new ModuleModel($name);
            $m->save($this->g->getEntityManager());

            $class = $library->getRootNamespace() . 'Module';
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
            $subscriptions = $library->getSubscriptionPatterns();
            foreach ($subscriptions as $s) {
                $this->g->getEventManager()->subscribe($s, $class, $name);
            }

            // Run the module's onRegister handler.
            $class::onRegister($this->g, $m);
        }
    }

    /**
     * Called when a module is removed from the system. Performs teardown tasks like
     * unregistering the events this module responds to.
     *
     * @param LibraryConfiguration $library LibraryConfiguration representing this module.
     * @throws ModuleDoesNotExistException if the module is not installed.
     */
    public function unregister(LibraryConfiguration $library)
    {
        $name = $library->getName();
        $package = $library->getComposerPackage();

        $m = $this->g->getEntityManager()->getRepository(ModuleModel::class)->find($name);
        if (!$m) {
            throw new ModuleDoesNotExistException($name);
        } else {
            $class = $library->getRootNamespace() . 'Module';

            // Unsubscribe the module's events.
            $subscriptions = $library->getSubscriptionPatterns();
            foreach ($subscriptions as $s) {
                try {
                    $this->g->getEventManager()->unsubscribe($s, $class, $name);
                } catch (SubscriptionNotFoundException $e) {
                    $this->g->getLogger()->error("Could not find subscription {$s} in library {$name} to unsubscribe.");
                }
            }

            // Run the module's onUnregister handler.
            $class::onUnregister($this->g, $m);

            // TODO: handle error cases here.
            $m->delete($this->g->getEntityManager());
        }
    }

    /**
     * Returns the list of currently registered modules.
     * @return array<Module> Array of modules.
     */
    public function getModules(): array
    {
        return $this->g->getEntityManager()->getRepository(ModuleModel::class)->findAll();
    }

    /**
     * Returns the module with the specified library name, in vendor/module format.
     * @param string $library
     * @return Module
     */
    public function getModule(string $library): ModuleModel
    {
        return $this->g->getEntityManager()->getRepository(ModuleModel::class)->find($library);
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
            $library = new LibraryConfiguration($this->g->getComposerManager(), $p, $this->g->getCWD());
            $name = $library->getName();
            $class = $library->getRootNamespace() . 'Module';

            $expectedSubscriptions = $library->getSubscriptionPatterns();
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
                        $c->getLibrary() === $p->getName()) {
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
