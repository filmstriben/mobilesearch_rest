<?php

namespace AppBundle\Command;

use AppBundle\Controller\ImageController;
use AppBundle\Services\ImageConverterException;
use Imagine\Exception\RuntimeException;
use Imagine\Image\ImageInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class ConvertImagesCommand.
 *
 * Command to generate a set of images from existing original files.
 */
class ConvertImagesCommand extends ContainerAwareCommand {

    protected $agency = '150027';

    protected $sizes = ['371x206', '742x412', '1920x1080', '3840x2160'];

    protected $formats = ['jpeg', 'webp'];

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

        $finder = new Finder();
        $finder
            ->name('/\.(jpg|jpeg|gif|png)$/')
            ->in($baseImageDirectory)
            ->depth('== 0');

        /** @var \AppBundle\Services\ImageConverterInterface $imageConverter */
        $imageConverter = $this->getContainer()->get('image_converter');

        $progress = 0;
        $total = $finder->count();

        foreach ($finder as $file) {
            $imagePath = $baseImageDirectory.'/'.$file->getFilename();

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
                        continue(3);
                    }
                }
            }

            $progress++;
            $output->writeln("Processing: " . $file);
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

