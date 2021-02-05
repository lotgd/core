<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Character;

use LotGD\Core\Console\Command\BaseCommand;
use LotGD\Core\Models\Character;
use Symfony\Component\Console\Input\InputArgument;

class CharacterBaseCommand extends BaseCommand
{
    /**
     * @return InputArgument
     */
    protected function getCharacterIdArgumentDefinition(): InputArgument
    {
        return new InputArgument(
            name: "id",
            mode: InputArgument::REQUIRED,
            description: "Character ID",
        );
    }

    /**
     * @param string $id
     * @return Character|null
     */
    protected function getCharacter(string $id): ?Character
    {
        /** @var Character|null $character */
        $character = $this->game->getEntityManager()->getRepository(Character::class)->find($id);
        return $character;
    }
}