<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Class EnsureIndexCommand.
 *
 * Command to create default db index.
 */
class EnsureIndexCommand extends ContainerAwareCommand {
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('fil:index:create')
            ->setDescription('Ensures fulltext index to aid content search.');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();
        $finder
            ->in('app/Resources')
            ->name('content_index.json');

        foreach ($finder as $file) {
            $contents = $file->getContents();
            $indexDefinition = json_decode($contents, TRUE);

            if (FALSE === $indexDefinition) {
                $output->writeln('Failed to restore index.');
                break;
            }

            $connection = $this->getContainer()->get('doctrine_mongodb')->getConnection();
            /** @var \MongoClient $mongo */
            $mongo = $connection->getMongo();
            /** @var \MongoDB $db */
            $db = $mongo->selectDB($this->getContainer()->getParameter('mongo_db'));
            /** @var \MongoCollection $collection */
            $collection = $db->selectCollection('Content');

            $createIndexResult = $collection->createIndex($indexDefinition[0], $indexDefinition[1]);
            if (array_key_exists('note', $createIndexResult)) {
                $output->writeln($createIndexResult['note']);
            }
        }
    }
}

