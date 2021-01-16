<?php
declare(strict_types=1);


namespace LotGD\Core\Services;


use LotGD\Core\Events\EventContextData;
use LotGD\Core\Exceptions\InsecureTwigTemplateError;
use LotGD\Core\Game;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\Scene;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityPolicy;

class TwigSceneRenderer
{
    private Environment $twig;

    public function __construct(
        private Game $game
    ) {
        $this->twig = new Environment(new TwigNullLoader());

        $securityPolicy = $this->getSecurityPolicy();

        # Add Sandbox extension
        $this->twig->addExtension(new SandboxExtension($securityPolicy, sandboxed: true));
    }

    public function render(string $string, Scene $scene, bool $ignoreErrors = false): string
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

        $templateValues = [
            "Character" => $this->game->getCharacter(),
            "Scene" => $scene,
        ];

        // Publish event to change $templateValues
        $eventManager = $this->game->getEventManager();
        $contextData = EventContextData::create(["templateValues" => $templateValues]);
        $newContextData = $eventManager->publish("h/lotgd/core/scene-renderer/templateValues", $contextData);
        $templateValues = $newContextData->get("templateValues");

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

    public function getSecurityPolicy(): SecurityPolicy
    {
        $tags = ["if"];
        $filters = ["lower", "upper", "escape"];
        $functions = ["range"];
        $methods = [
            Character::class => ["getDisplayName", "getLevel", "isAlive", "getHealth", "getMaxHealth", "getProperty"],
            Scene::class => ["getProperty"],
        ];
        $properties = [
            "Character" => ["displayName", "level", "health", "maxHealth"],
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
        $newContextData = $eventManager->publish("h/lotgd/core/scene-renderer/securityPolicy", $contextData);

        // Set changed values from the event.
        $tags = $newContextData->get("tags");
        $filters = $newContextData->get("filters");
        $functions = $newContextData->get("functions");
        $methods = $newContextData->get("methods");
        $properties = $newContextData->get("properties");

        return new SecurityPolicy($tags, $filters, $methods, $properties, $functions);
    }
}