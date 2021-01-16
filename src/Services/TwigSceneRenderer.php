<?php
declare(strict_types=1);


namespace LotGD\Core\Services;


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
        $template = $this->twig->createTemplate($string);

        $templateValues = [
            "Character" => $this->game->getCharacter(),
            "Scene" => $scene,
        ];

        // @Todo: Event to add property to the template

        try {
            // This will throw a SecurityError
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

        // @ToDo: Event to change Security Policy

        return new SecurityPolicy($tags, $filters, $methods, $properties, $functions);
    }
}