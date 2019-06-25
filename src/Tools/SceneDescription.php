<?php
declare(strict_types=1);

namespace LotGD\Core\Tools;

/**
 * Abstracts a scene description and provides tools to modify the text more easily.
 * Class SceneDescription.
 */
class SceneDescription
{
    private $description = [];

    /**
     * SceneDescription constructor.
     * @param string $description
     */
    public function __construct(string $description)
    {
        $this->description = $this->splitIntoParagraphs($description);
    }

    /**
     * Converts the description to a string.
     * @return string
     */
    public function __toString(): string
    {
        return $this->getDescriptionBack();
    }

    /**
     * Converts the description to a string.
     * @return string
     */
    public function getDescriptionBack(): string
    {
        return \implode("\n\n", $this->description);
    }

    /**
     * Adds a paragraph to the description. If the paragraph contains \n\n, it gets broken into multiple paragraphs first.
     * @param string $paragraph
     */
    public function addParagraph(string $paragraph): void
    {
        $paragraph = $this->splitIntoParagraphs($paragraph);
        $this->description = \array_merge($this->description, $paragraph);
    }

    /**
     * Splits a given string into an array ("paragraphs").
     *
     * This method takes a string, normalizes line ends and then splits it at every double line break (\n\n).
     * @param string $input
     * @return array
     */
    private function splitIntoParagraphs(string $input): array
    {
        $input = \str_replace("\r\n", "\n", $input);
        $input = \str_replace("\r", "\n", $input);

        $parts = \explode("\n\n", $input);
        foreach ($parts as $key => $part) {
            if (\strlen($part) === 0) {
                unset($parts[$key]);
            }
        }

        return $parts;
    }
}
