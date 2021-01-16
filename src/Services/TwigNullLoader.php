<?php
declare(strict_types=1);


namespace LotGD\Core\Services;


use Twig\Error\LoaderError;
use Twig\Loader\LoaderInterface;
use Twig\Source;

class TwigNullLoader implements LoaderInterface
{
    public function getSourceContext(string $name): Source
    {
        throw new LoaderError("Should not get called.");
    }

    public function getCacheKey(string $name): string
    {
        throw new LoaderError("Should not get called.");
    }

    public function isFresh(string $name, int $time): bool
    {
        return true;
    }

    public function exists(string $name)
    {
        return false;
    }
}