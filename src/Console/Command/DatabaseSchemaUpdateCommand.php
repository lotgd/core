<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use LotGD\Core\Console\Main;
use LotGD\Core\Game;

/**
 * Danerys command to initiate the database with default values.
 */
class DatabaseSchemaUpdateCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('database:schemaUpdate')
             ->setDescription('Updates the database schema manually.');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager = $this->game->getEntityManager();
        $metaData = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema($metaData);

        $entityManager->flush();
    }
}
