<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command\Database;

use LotGD\Core\Console\Command\BaseCommand;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->setDescription('Updates the database schema manually.')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entityManager = $this->game->getEntityManager();
        $metaData = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema($metaData);

        $entityManager->flush();

        return Command::SUCCESS;
    }
}
