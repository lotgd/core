<?php
declare(strict_types=1);


namespace LotGD\Core\Services;


use Doctrine\DBAL\Exception as DBALException;
use LotGD\Core\Action;
use LotGD\Core\Events\EventContextData;
use LotGD\Core\Exceptions\CharacterNotFoundException;
use LotGD\Core\Exceptions\InsecureTwigTemplateError;
use LotGD\Core\Game;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\Scene;
use LotGD\Core\Models\Viewpoint;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Twig\Extension\SandboxExtension;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityPolicy;

class TwigSceneRenderer
{
    private Environment $twig;
    private array $templateValues;

    public function __construct(
        private Game $game
    ) {
        $this->twig = new Environment(new TwigNullLoader());

        // Add a hook for additional fields
        // This is for global changes only. Viewpoint-dependent changes should try to store the important values within
        // the viewpoint itself.
        $eventManager = $this->game->getEventManager();
        $contextData = EventContextData::create(["templateValues" => []]);

        // Use try-catch here in case no database has yet been created. See #162
        try {
            $newContextData = $eventManager->publish("h/lotgd/core/scene-renderer/templateValues", $contextData);
            $this->templateValues = $newContextData->get("templateValues") ?? [];
        } catch (DBALException) {
            $this->templateValues = [];
        }

        // Add Sandbox extension
        $securityPolicy = $this->getSecurityPolicy();
        $this->twig->addExtension(new SandboxExtension($securityPolicy, sandboxed: true));
    }

    /**
     * Renders a given string in the context if a given viewpoint.
     *
     * @param string $string
     * @param Viewpoint $viewpoint
     * @param bool $ignoreErrors If set to true, errors are ignored and the unparsed string will be returned instead.
     * @param array $templateValues
     * @return string
     * @throws InsecureTwigTemplateError
     * @throws CharacterNotFoundException
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function render(string $string, Viewpoint $viewpoint, bool $ignoreErrors = false, array $templateValues = []): string
    {
        // We catch here "Tag" errors. If error, we'll exit either by returning the input ($ignoreError === true) or
        // throwing an exception.
        try {
            $template = $this->twig->createTemplate($string);
        } catch (SecurityError $e) {
            if ($ignoreErrors) {
                return $string;
            } else {
                throw new InsecureTwigTemplateError("Template contains illegal calls: {$e->getMessage()}");
            }
        }

        $defaultTemplateValues = [
            "Character" => $this->game->getCharacter(),
            "Scene" => $viewpoint->getScene(),
            "Viewpoint" => $viewpoint,
        ];

        // Merges additional template values with important ones.
        $templateValues = array_merge($this->templateValues, $defaultTemplateValues, $templateValues);

        // Try to render the template
        try {
            // This could throw a SecurityError
            $result = $template->render($templateValues);
        } catch (SecurityError $e) {
            if ($ignoreErrors) {
                return $string;
            } else {
                throw new InsecureTwigTemplateError("Template contains illegal calls: {$e->getMessage()}");
            }
        }

        return $result;
    }

    /**
     * Returns the current security policy.
     * This method provides a hook.
     * @return SecurityPolicy
     */
    public function getSecurityPolicy(): SecurityPolicy
    {
        $tags = ["if"];
        $filters = ["lower", "upper", "escape", "round"];
        $functions = ["range"];
        $methods = [
            Character::class => ["getDisplayName", "getLevel", "isAlive", "getHealth", "getMaxHealth", "getProperty"],
            Scene::class => ["getProperty"],
            Viewpoint::class => ["getData"],
            Action::class => ["getParameters"],
        ];
        $properties = [
            "Character" => ["displayName", "level", "alive", "health", "maxHealth", "property"],
            "Viewpoint" => ["data"],
            "Action" => ["parameters"],
        ];

        // Publish event to change $templateValues
        $eventManager = $this->game->getEventManager();
        $contextData = EventContextData::create([
            "tags" => $tags,
            "filters" => $filters,
            "functions" => $functions,
            "methods" => $methods,
            "properties" => $properties,
        ]);

        // Use try-catch here in case no database has yet been created. See #162
        try {
            $newContextData = $eventManager->publish("h/lotgd/core/scene-renderer/securityPolicy", $contextData);
        } catch (DBALException) {
            $this->templateValues = [];
        }

        // Set changed values from the event.
        $tags = $newContextData->get("tags");
        $filters = $newContextData->get("filters");
        $functions = $newContextData->get("functions");
        $methods = $newContextData->get("methods");
        $properties = $newContextData->get("properties");

        return new SecurityPolicy($tags, $filters, $methods, $properties, $functions);
    }
}