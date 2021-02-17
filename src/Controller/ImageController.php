<?php

namespace App\Controller;

use App\Document\ServiceHit;
use App\Services\ImageConverterException;
use App\Services\ImageConverterInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use OpenApi\Annotations as OA;


/**
 * Class ImageController.
 *
 * TODO: Test coverage.
 */
class ImageController extends AbstractController
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
     * Fetch an image.
     *
     * @Route("/files/{agency}/{resize}/{filename}", defaults={"resize":"0x0"}, requirements={
     *      "resize":"\d{1,4}x\d{1,4}"
     * }, methods={"GET"})
     * @OA\Get(
     *     description="",
     *     tags={"Image"},
     *     deprecated=true
     * )
     */
    public function imageAction(Request $request, $agency, $resize, $filename)
    {
        return $this->forward('App\Controller\ImageController:imageNewAction', [
            'request' => $request,
            'agency' => $agency,
            'filename' => $filename,
        ]);
    }

    /**
     * Fetch an image. To convert between formats (jpeg|png|gif|webp), change the request filename extension.
     *
     * @Route("/files/{agency}/{filename}", methods={"GET"})
     * @OA\Get(
     *     description="",
     *     tags={"Image"},
     *     @OA\Parameter(
     *         in="query",
     *         name="w",
     *         description="Target width. Min - 0, Max - 3840.",
     *         @OA\Schema(
     *             type="integer",
     *             minimum=0,
     *             maximum=3840
     *         )
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="h",
     *         description="Target height. Min - 0, Max - 3840.",
     *         @OA\Schema(
     *             type="integer",
     *             minimum=0,
     *             maximum=3840
     *         )
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="q",
     *         description="Quality. Min - 2, Max - 100",
     *         @OA\Schema(
     *             type="integer",
     *             default=75,
     *             minimum=2,
     *             maximum=100
     *         )
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="s",
     *         description="Sharpen.",
     *         @OA\Schema(
     *             type="boolean",
     *             default=true
     *         )
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="r",
     *         description="Sampling filter.",
     *         @OA\Schema(
     *             type="string",
     *             default="undefined"
     *         ),
     *         @OA\Examples(
     *              summary="Undefined",
     *              value="undefined"
     *         ),
     *         @OA\Examples(
     *              summary="Point",
     *              value="point"
     *         ),
     *         @OA\Examples(
     *              summary="Box",
     *              value="box"
     *         ),
     *         @OA\Examples(
     *              summary="Triangle",
     *              value="triangle"
     *         ),
     *         @OA\Examples(
     *              summary="Hermite",
     *              value="hermite"
     *         ),
     *         @OA\Examples(
     *              summary="Hamming",
     *              value="hamming"
     *         ),
     *         @OA\Examples(
     *              summary="Hanning",
     *              value="hanning"
     *         ),
     *         @OA\Examples(
     *              summary="Blackman",
     *              value="blackman"
     *         ),
     *         @OA\Examples(
     *              summary="Gaussian",
     *              value="gaussian"
     *         ),
     *         @OA\Examples(
     *              summary="Quadratic",
     *              value="quadratic"
     *         ),
     *         @OA\Examples(
     *              summary="Cubic",
     *              value="cubic"
     *         ),
     *         @OA\Examples(
     *              summary="Catrom",
     *              value="catrom"
     *         ),
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="f",
     *         description="Force reload.",
     *         @OA\Schema(
     *             type="boolean",
     *             default=false
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Result image.",
     *         @OA\Schema(
     *             type="string",
     *             format="binary"
     *         )
     *     )
     * )
     */
    public function imageNewAction(
        Request $request,
        $agency,
        $filename,
        ManagerRegistry $dm,
        LoggerInterface $logger,
        ImageConverterInterface $imageConverter
    ) {
        if ($this->getParameter('track_hits')) {
            /** @var \App\Repositories\ServiceHitRepository $repository */
            $repository = $dm->getRepository(ServiceHit::class);
            $repository->trackHit('image_request', $request->getRequestUri());
        }

        $quality = (int)$request->query->get('q', 75);
        $quality = ($quality > 1 && $quality < 101) ? $quality : 75;

        $sampleFilter = $request->query->get('r');
        $sharpen = filter_var($request->query->get('s', true), FILTER_VALIDATE_BOOLEAN);

        $format = $request->query->get('o');

        $force = filter_var($request->query->get('f'), FILTER_VALIDATE_BOOLEAN);

        $targetWidth = (int)$request->query->get('w');
        $targetHeight = (int)$request->query->get('h');

        // Legacy support.
        $resize = $request->query->get('resize', $request->attributes->get('resize'));
        if (!empty($resize)) {
            [$targetWidth, $targetHeight] = explode('x', $resize);
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
            $logger->error("Resized images path '{$resizedImageDirectory}' could not be created or is not writeable.");

            return new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
        } else {
            if ($this->getParameter('track_hits')) {
                /** @var \App\Repositories\ServiceHitRepository $repository */
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
