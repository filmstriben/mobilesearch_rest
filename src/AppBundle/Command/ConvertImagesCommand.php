<?php

namespace AppBundle\Command;

use AppBundle\Controller\ImageController;
use AppBundle\Document\Content;
use AppBundle\Services\ImageConverterException;
use Imagine\Exception\RuntimeException;
use Imagine\Image\ImageInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ConvertImagesCommand.
 *
 * Command to generate a set of images from existing original files.
 */
class ConvertImagesCommand extends ContainerAwareCommand {

    protected $agency = '150027';

    // TODO: Make a command argument.
    protected $sizes = ['371x206'];

    protected $formats = ['jpeg'];

    protected $quality = 75;

    protected $fileSystem;

    /**
     * {@inheritDoc}
     */
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->fileSystem = new Filesystem();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('fil:images:create')
            ->setDescription('Converts all existing original images into their web-ready variants.');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $baseImageDirectory = $this->getContainer()->getParameter('web_dir').'/'.ImageController::FILES_STORAGE_DIR.'/'.$this->agency;

        foreach ($this->sizes as $size) {
            $resizedImageDirectory = $baseImageDirectory.'/'.$size;

            $this->prepareDirectory($resizedImageDirectory);
        }

        $doctrine = $this->getContainer()->get('doctrine_mongodb');
        $em = $doctrine->getManager();
        /** @var \AppBundle\Document\Content[] $movies */
        $movies = $em->getRepository(Content::class)
            ->findBy([
                'type' => 'os',
            ]);

        /** @var \AppBundle\Services\ImageConverterInterface $imageConverter */
        $imageConverter = $this->getContainer()->get('image_converter');

        $progress = 0;
        $total = count($movies);
        foreach ($movies as $movie) {
            $movieFields = $movie->getFields();

            $output->writeln("Movie: " . $movieFields['title']['value']);

            $images = array_key_exists('field_images', $movieFields) ? $movieFields['field_images']['value'] : [];

            foreach ($images as $image) {
                $filename = explode('/', $image)[2];
                $imagePath = $baseImageDirectory.'/'.$filename;

                if (!$this->fileSystem->exists($imagePath)) {
                    continue;
                }

                $output->writeln("Processing image: " . $imagePath);

                $file = new \SplFileInfo($imagePath);
                foreach ($this->sizes as $size) {
                    list($w, $h) = explode('x', $size);

                    foreach ($this->formats as $format) {
                        $filename = $file->getBasename('.'.$file->getExtension()).'.'.$format;
                        $resizedImageDirectory = $baseImageDirectory.'/'.$size;
                        $imageResizedPath = $resizedImageDirectory.'/'.$this->quality.'_'.$filename;

                        if ($this->fileSystem->exists($imageResizedPath)) {
                            continue;
                        }

                        try {
                            $imageConverter
                                ->setQuality($this->quality)
                                ->setFormat($format)
                                ->setSamplingFilter(ImageInterface::FILTER_CATROM)
                                ->convert($imagePath, $imageResizedPath, $w, $h);
                        }
                        catch (ImageConverterException $exception) {
                            $output->writeln("Error converting file '{$imagePath}' with exception: {$exception->getMessage()}");
                            exit(0);
                        }
                    }
                }
            }

            $progress++;
            $output->writeln("Progress: " . number_format($progress / $total, 6) * 100 . '%');
        }
    }

    /**
     * Checks and optionally prepares the directory where resized images
     * are stored.
     *
     * @param string $path
     *   Directory path.
     * @param boolean $create
     *   Whether to create the directories.
     *
     * @return boolean
     */
    protected function prepareDirectory($path, $create = true)
    {
        $exists = $this->fileSystem->exists($path);

        if (!$exists && $create) {
            try {
                $this->fileSystem->mkdir($path);
                $exists = true;
            } catch (IOException $exception) {
                return false;
            }
        }

        return $exists;
    }
}

