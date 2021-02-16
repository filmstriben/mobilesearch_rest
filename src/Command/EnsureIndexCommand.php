<?php

namespace App\Command;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;

/**
 * Class EnsureIndexCommand.
 *
 * Command to create default db index.
 */
class EnsureIndexCommand extends Command {
    /**
     * @var \Doctrine\Bundle\MongoDBBundle\ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface
     */
    protected $parameterBag;

    /**
     * EnsureIndexCommand constructor.
     *
     * @param \Doctrine\Bundle\MongoDBBundle\ManagerRegistry $doctrine
     * @param \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $parameterBag
     */
    public function __construct(ManagerRegistry $doctrine, ParameterBagInterface $parameterBag)
    {
        $this->doctrine = $doctrine;
        $this->parameterBag = $parameterBag;

        parent::__construct();
    }

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
            ->in('config')
            ->name('content_index.json');

        foreach ($finder as $file) {
            $contents = $file->getContents();
            $indexDefinition = json_decode($contents, TRUE);

            if (FALSE === $indexDefinition) {
                $output->writeln('Failed to restore index.');
                break;
            }

            /** @var \MongoDB\Client $connection */
            $connection = $this->doctrine->getConnection();

            $database = $connection->selectDatabase($this->parameterBag->get('mongodb_database'));
            $collection = $database->selectCollection('Content');

            $collection->createIndex($indexDefinition[0], $indexDefinition[1]);
        }
    }
}

