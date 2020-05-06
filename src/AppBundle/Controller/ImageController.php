<?php

namespace AppBundle\Controller;

use AppBundle\Document\ServiceHit;
use AppBundle\Services\ImageConverterException;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class ImageController.
 *
 * TODO: Test coverage.
 */
class ImageController extends Controller
{
    const FILES_STORAGE_DIR = 'storage/images';

    protected $response;

    protected $publicCache = 60 * 60 * 24 * 30;

    protected $fileSystem;

    /**
     * ImageController constructor.
     */
    public function __construct()
    {
        $this->fileSystem = new Filesystem();
    }

    /**
     * Replaced by '/files/{agency}/{filename}' route.
     *
     * @ApiDoc(
     *     description="Fetches an, optionally re-sized, image.",
     *     section="Images",
     *     requirements={
     *         {
     *             "name"="agency",
     *             "dataType"="string",
     *             "description"="Agency number."
     *         },
     *         {
     *             "name"="resize",
     *             "dataType"="string",
     *             "description"="Re-size image.",
     *             "requirement"="{\d}+x{\d}+"
     *         },
     *         {
     *             "name"="filename",
     *             "dataType"="string",
     *             "description"="File name."
     *         }
     *     },
     *     deprecated="true"
     * )
     * @Route("/files/{agency}/{resize}/{filename}", defaults={"resize":"0x0"}, requirements={
     *      "resize":"\d{1,4}x\d{1,4}"
     * })
     * @Method({"GET"})
     */
    public function imageAction(Request $request, $agency, $resize, $filename)
    {
        return $this->forward('AppBundle:Image:imageNew', [
            'request' => $request,
            'agency' => $agency,
            'filename' => $filename,
        ]);
    }

    /**
     * <p>If one of the size parameters - '<strong>w</strong>' or '<strong>h</strong>' - are omitted or one of them is equal to '0',
     * the another size parameter is adjusted automatically to maintain aspect ratio of the image.</p>
     * <p>If both size parameters are omitted or are equal to '0', original image is served instead and all other parameters are discarded.</p>
     * <p> Resample  - '<strong>r</strong>' - parameter can be one of following:
     * 'point', 'box', 'triangle', 'hermite', 'hanning', 'hamming', 'blackman', 'gaussian', 'quadratic', 'cubic', 'catrom',
     * 'mitchell', 'lanczos', 'bessel' or 'sinc'.<br />
     * Various re-sampling algorithms would deliver slightly different results and will vary image size slightly.<br />
     * To obtain sharper images, use 'point', 'lanczos' or 'sinc'. <br />
     * Softer or blurry images can be obtained by using 'cubic' or 'triangle' resampling.</p>
     *
     * @ApiDoc(
     *     description="Fetches image and, optionally, re-sizes it.",
     *     section="Images",
     *     requirements={
     *         {
     *             "name"="agency",
     *             "dataType"="string",
     *             "description"="Agency number."
     *         },
     *         {
     *             "name"="filename",
     *             "dataType"="string",
     *             "description"="File name."
     *         },
     *     },
     *     parameters={
     *         {
     *             "name"="w",
     *             "dataType"="int",
     *             "description"="Target image width",
     *             "required"=false,
     *             "format"="{\d}"
     *         },
     *         {
     *             "name"="h",
     *             "dataType"="int",
     *             "description"="Target image height",
     *             "required"=false,
     *             "format"="{\d}"
     *         },
     *         {
     *             "name"="q",
     *             "dataType"="int",
     *             "description"="Target image quality. Range from '1' (worst) to '100' (best). Default - '75'.",
     *             "required"=false,
     *             "format"="{\d}"
     *         },
     *         {
     *             "name"="o",
     *             "dataType"="string",
     *             "description"="Convert image format. Default - 'jpeg'.",
     *             "required"=false
     *         },
     *         {
     *             "name"="r",
     *             "dataType"="string",
     *             "description"="Apply custom resampling algorithm. Default - 'catrom'.",
     *             "required"=false
     *         },
     *         {
     *             "name"="s",
     *             "dataType"="boolean",
     *             "description"="Apply additional sharpening to target image. Default - 'true'.",
     *             "required"=false
     *         },
     *     }
     * )
     * @Route("/files/{agency}/{filename}")
     * @Method({"GET"})
     */
    public function imageNewAction(Request $request, $agency, $filename)
    {
        if ($this->getParameter('track_hits')) {
            $dm = $this->get('doctrine_mongodb')->getManager();
            /** @var \AppBundle\Repositories\ServiceHitRepository $repository */
            $repository = $dm->getRepository(ServiceHit::class);
            $repository->trackHit('image_request', $request->getRequestUri());
        }

        $quality = (int)$request->query->get('q', 75);
        $quality = ($quality > 1 && $quality < 101) ? $quality : 75;

        $sampleFilter = $request->query->get('r');
        $sharpen = filter_var($request->query->get('s'), FILTER_VALIDATE_BOOLEAN);

        $format = $request->query->get('o');

        $force = filter_var($request->query->get('f'), FILTER_VALIDATE_BOOLEAN);

        $targetWidth = (int)$request->query->get('w');
        $targetHeight = (int)$request->query->get('h');

        // Legacy support.
        $resize = $request->query->get('resize', $request->attributes->get('resize'));
        if (!empty($resize)) {
            list($targetWidth, $targetHeight) = explode('x', $resize);
        }

        $targetWidth = ($targetWidth > -1 && $targetWidth < 3841) ? $targetWidth : 0;
        $targetHeight = ($targetHeight > -1 && $targetHeight < 3841) ? $targetHeight : 0;

        // Both sides are zero, the API would not return full-size images.
        if ($targetWidth + $targetHeight === 0) {
            return new Response('Both width and height can not be zero.', Response::HTTP_BAD_REQUEST);
        }

        $baseImageDirectory = $this->getParameter('web_dir').'/'.self::FILES_STORAGE_DIR.'/'.$agency;

        $imagePath = $baseImageDirectory.'/'.$filename;
        // Keep a separate directory for a specific size.
        $resizedImageDirectory = $baseImageDirectory.'/'.implode('x', [$targetWidth, $targetHeight]);

        $splFile = new \SplFileInfo($baseImageDirectory.'/'.$filename);
        // TODO: This overrides the query param format.
        $format = $splFile->getExtension();
        $baseName = $splFile->getBasename('.'.$format);

        $format = $this->validateImageFormat($format);
        $resizedFilename = $baseName.'.'.$format;
        $imageResizedPath = $resizedImageDirectory.'/'.$resizedFilename;

        if ($this->fileSystem->exists($imageResizedPath) && false === $force) {
            $imagePath = $imageResizedPath;
        } elseif (false === ($imagePath = $this->tryImageFile($imagePath))) {
            return new Response('File not found.', Response::HTTP_NOT_FOUND);
        } elseif (!$this->prepareDirectory($resizedImageDirectory)) {
            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->error("Resized images path '{$resizedImageDirectory}' could not be created or is not writeable.");

            return new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
        } else {
            /** @var \AppBundle\Services\ImageConverterInterface $imageConverter */
            $imageConverter = $this->get('image_converter');

            if ($this->getParameter('track_hits')) {
                $dm = $this->get('doctrine_mongodb')->getManager();
                /** @var \AppBundle\Repositories\ServiceHitRepository $repository */
                $repository = $dm->getRepository(ServiceHit::class);
                $repository->trackHit('image_convert', $request->getRequestUri());
            }

            try {
                $imageConverter
                    ->setQuality($quality)
                    ->setFormat($format)
                    ->setSamplingFilter($sampleFilter)
                    ->convert($imagePath, $imageResizedPath, $targetWidth, $targetHeight, $sharpen, true);

                $imagePath = $imageResizedPath;
            } catch (ImageConverterException $exception) {
                /** @var \Psr\Log\LoggerInterface $logger */
                $logger = $this->get('logger');
                $logger->error($exception->getMessage());

                return new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $eTag = $this->fileContentsHash($imagePath);

        $now = new \DateTime();
        $now->setTimestamp(filemtime($imagePath));

        $response = new StreamedResponse();
        $response->setCache([
            'etag' => $eTag,
            'last_modified' => $now,
            'max_age' => $this->publicCache,
            's_maxage' => $this->publicCache,
            'public' => true,
        ]);
        $response->setStatusCode(Response::HTTP_OK);
        $notModified = $response->isNotModified($request);

        $response->setCallback(function () use ($imagePath, $notModified) {
            if (!$notModified) {
                readfile($imagePath);
            }
        });

        // Webp images deliver a non-image mime type, so override this one.
        $mime = mime_content_type($imagePath);
        if ('application/octet-stream' === $mime && 'webp' === $format) {
            $mime = 'image/webp';
        }
        $response->headers->set('Content-Type', $mime);


        return $response;
    }

    /**
     * Generate file contents hash.
     *
     * Used to generate ETag's.
     *
     * @param string $path
     *   File path.
     *
     * @return bool|string
     *   False if file does not exist, or hash of the contents.
     */
    protected function fileContentsHash($path)
    {
        return md5_file($path);
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
                /** @var \Psr\Log\LoggerInterface $logger */
                $logger = $this->container->get('logger');
                $logger->error($exception->getMessage());

                return false;
            }
        }

        return $exists && is_writable($path);
    }

    /**
     * Attempts to find similar named images.
     *
     * This will seek images with same basenames in same directory,
     * eventually returning first occurrence.
     *
     * @param $imageFile
     *   Sample file path.
     *
     * @return bool|string
     *   False if no match found, or path to first occurrence.
     */
    public function tryImageFile($imageFile)
    {
        $splFile = new \SplFileInfo($imageFile);
        $baseName = $splFile->getBasename('.'.$splFile->getExtension());

        $matches = glob($splFile->getPath().'/'.$baseName.'.{jpeg,jpg,webp,gif,png}', GLOB_BRACE);

        return !empty($matches) && is_readable($matches[0]) ? $matches[0] : false;
    }

    /**
     * Validates image format.
     *
     * @param string $format
     *   Format to validate.
     *
     * @return string
     *   Valid format.
     */
    public function validateImageFormat($format)
    {
        return in_array($format, ['jpeg','jpg','png','gif','webp']) ? $format : 'jpeg';
    }
}
