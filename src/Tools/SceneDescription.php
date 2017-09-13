<?php
declare(strict_types=1);

namespace LotGD\Core\Tools;


class SceneDescription
{
    private $description = [];

    public function __construct(string $description)
    {
        $this->description = $this->splitIntoParagraphs($description);
    }

    public function __toString(): string
    {
        return $this->getDescriptionBack();
    }

    public function addParagraph(string $paragraph): void
    {
        $paragraph = $this->splitIntoParagraphs($paragraph);
        $this->description = array_merge($this->description, $paragraph);
    }

    public function getDescriptionBack(): string
    {
        return implode("\n\n", $this->description);
    }

    private function splitIntoParagraphs(string $input): array
    {
        $input = str_replace("\r\n", "\n", $input);
        $input = str_replace("\r", "\n", $input);

        return explode("\n\n", $input);
    }
}