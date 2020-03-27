<?php

namespace AppBundle\Controller;

use Imagine\Imagick\Imagine as ImagickImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ImageController.
 */
class ImageController extends Controller
{
    const ASPECT_PRECISION = 3;

    protected $filesStorageDir = '../web/storage/images';

    protected $response;

    protected $quality = 75;

    protected $sharpen = true;

    protected $sampleFilter = ImageInterface::FILTER_CATROM;

    protected $format = 'webp';

    protected $publicCache = 60 * 60 * 24 * 30;

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
     * Various resampling algorithms would deliver slightly different results and will vary image size slightly.<br />
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
     *             "description"="Convert image format. Default - 'webp'.",
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
        $resize = $request->query->get('resize', $request->attributes->get('resize'));

        if ($quality = (int)$request->query->get('q')) {
            $this->setQuality($quality);
        }

        if ($sampleFilter = $request->query->get('r')) {
            $this->setSamplingFilter($sampleFilter);
        }

        if ($sharpen = $request->query->get('s')) {
            $this->setSharpen($sharpen);
        }

        if ($output = $request->query->get('o')) {
            $this->setFormat($output);
        }

        if ($force = $request->query->get('f')) {
            $force = filter_var($force, FILTER_VALIDATE_BOOLEAN);
        }

        $targetWidth = (int)$request->query->get('w');
        $targetHeight = (int)$request->query->get('h');
        // TODO: Legacy support.
        if (!empty($resize)) {
            list($targetWidth, $targetHeight) = explode('x', $resize);
        }

        // Keep a separate directory for a specific size.
        $subDirectory = implode('x', [$targetWidth, $targetHeight]);

        $imagePath = $this->filesStorageDir.'/'.$agency.'/'.$filename;

        $response = new Response();
        $fs = new Filesystem();

        // Both sides are zero, therefore serve original image.
        $serveOriginal = $targetWidth + $targetHeight === 0;
        if (!$serveOriginal && $this->checkThumbnailSubdir($subDirectory, $agency) && $fs->exists($imagePath)) {
            list($baseName, $extension) = explode('.', $filename);
            $filename = $baseName.'.'.$this->format;
            $imageResizedPath = $this->filesStorageDir.'/'.$agency.'/'.$subDirectory.'/'.$this->quality.'_'.$filename;

            if (!$fs->exists($imageResizedPath) || true === $force) {
                $imagineInstance = new ImagickImagine();
                $image = $imagineInstance->open($imagePath);

                $this->resizeImage($image, $targetWidth, $targetHeight);

                if ($this->sharpen) {
                    $image->effects()->sharpen();
                }

                // Clear meta-data to save bandwidth.
                $image->strip();

                try {
                    $image->save($imageResizedPath, [
                        'quality' => $this->quality,
                        'format' => $this->format,
                    ]);
                } catch (\Exception $exception) {
                    /** @var \Psr\Log\LoggerInterface $logger */
                    $logger = $this->container->get('logger');
                    $logger->warning($imageResizedPath . ': ' . $exception->getMessage());
                }
            }

            $imagePath = $imageResizedPath;
        }

        if (!$fs->exists($imagePath)) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
            $response->setContent('File not found.');
        } else {
            $response->headers->set('Content-Type', 'image/'.$this->format);
            $response->headers->set('Cache-Control', 'max-age='.$this->publicCache.', public');
            $response->headers->set('Expires', gmdate(DATE_RFC1123, time() + $this->publicCache));

            $imageContents = file_get_contents($imagePath);
            $eTag = sha1($imageContents);
            $response->headers->set('ETag', $eTag);

            $response->setStatusCode(Response::HTTP_OK);
            $response->setContent(file_get_contents($imagePath));
        }

        return $response;
    }

    /**
     * Resizes and saves images.
     *
     * @param string $source Original image path.
     * @param string $target Target path for re-sized images.
     * @param array $wantedDimensions Desired width and height.
     *
     * @return \Imagine\Image\ImageInterface
     *   Imagine image object.
     */
    protected function resizeImage(ImageInterface $image, $wantedWidth = 0, $wantedHeight = 0)
    {
        $imageManipulations = $this->getResizeDimensions(
            $image->getSize()->getWidth(),
            $image->getSize()->getHeight(),
            $wantedWidth,
            $wantedHeight
        );
        $image->resize($imageManipulations['resize'], $this->sampleFilter);

        $image->crop($imageManipulations['crop'], $imageManipulations['final_size']);

        return $image;
    }

    /**
     * Calculates the required sizes for image manipulations.
     *
     * This method will resize the image keeping the aspect ratio of the
     * original image. If original and target ratio match, the image is scaled
     * directly to requested sizes.
     * If target ratio is different, the image is scaled to fit the smallest
     * side and cropped from the center of the image.
     *
     * @param array $originalSize Original image size (width and height).
     * @param array $targetSize Desired target size (width and height).
     *
     * @return array              A set of instructions needed to be applied to original image.
     *                            - resize: size of the image to crop from (Box object).
     *                            - crop: coordinates where to crop the image (Point object).
     *                            - final_size: Requested image size dimensions.
     */
    protected function getResizeDimensions($originalWidth, $originalHeight, $targetWidth, $targetHeight)
    {
        // Calculate the aspect ratios of original and target sizes.
        $originalAspect = round($originalWidth / $originalHeight, self::ASPECT_PRECISION);

        if (!$targetHeight) {
            $targetHeight = round($targetWidth / $originalAspect);
        } elseif (!$targetWidth) {
            $targetWidth = round($targetHeight * $originalAspect);
        }

        $targetAspect = round($targetWidth / $targetHeight, self::ASPECT_PRECISION);

        // Store default values which will be used by default.
        $resizeBox = new Box($targetWidth, $targetHeight);
        $finalImageSize = clone $resizeBox;
        $cropPoint = new Point(0, 0);

        // If the aspect ratios do not match, means that
        // the image must be adjusted to maintain adequate proportions.
        if ($originalAspect != $targetAspect) {
            // Get the smallest side of the image.
            // This is required to calculate target resize of the
            // image to crop from, so at least one side fits.
            $_x = $originalWidth / $targetWidth;
            $_y = $originalHeight / $targetHeight;
            $min = min($_x, $_y);

            $box_width = (int)round($originalWidth / $min);
            $box_height = (int)round($originalHeight / $min);

            $resizeBox = new Box($box_width, $box_height);

            // Get the coordinates where from to crop the final portion.
            // This one crops from the center of the resized image.
            $crop_x = $box_width / 2 - $targetWidth / 2;
            $crop_y = 0; // $box_height / 2 - $targetHeight / 2;

            $cropPoint = new Point($crop_x, $crop_y);
        }

        return [
            'resize' => $resizeBox,
            'crop' => $cropPoint,
            'final_size' => $finalImageSize,
        ];
    }

    /**
     * Check and optionally prepare the directory where resized images
     * are stored.
     *
     * @param string $name File name.
     * @param string $agency Agency id.
     * @param boolean $create Whether to create the directories.
     *
     * @return boolean
     */
    protected function checkThumbnailSubdir($name, $agency, $create = true)
    {
        $fs = new Filesystem();
        $path = $this->filesStorageDir.'/'.$agency.'/'.$name;
        $exists = $fs->exists($path);

        if (!$exists && $create) {
            try {
                $fs->mkdir($path);
                $exists = true;
            } catch (IOException $e) {
                return false;
            }
        }

        return $exists;
    }

    /**
     * Parses the desired image size from query string parameter.
     *
     * The parameter must be in form WIDTHxHEIGHT.
     *
     * @param string $resizeParam Query string resize parameter.
     *
     * @return array              Required width and height of the image.
     */
    protected function getSizeFromParam($resizeParam)
    {
        $dimensions = [];
        $sizes = [];
        if (!empty($resizeParam) && preg_match('/^(\d+)x(\d+)$/', $resizeParam, $sizes)) {
            $dimensions = [
                'width' => (int)$sizes[1],
                'height' => (int)$sizes[2],
            ];
        }

        return $dimensions;
    }

    /**
     * Sets image output quality.
     *
     * Internal use only.
     *
     * @param int $quality
     *   Quality range, from 1 to 100.
     */
    private function setQuality($quality = 90)
    {
        if (empty($quality)) {
            $quality = 90;
        } else {
            $quality = $quality > 100 ? 100 : $quality;
            $quality = $quality < 1 ? 1 : $quality;
        }

        $this->quality = $quality;
    }

    /**
     * Sets sampling filter.
     *
     * Internal use only.
     *
     * @param string $sampleFilter
     *   A filter name, as Imagine provides.
     *
     * @see \Imagine\Image\ImageInterface
     */
    private function setSamplingFilter($sampleFilter = ImageInterface::FILTER_UNDEFINED)
    {
        $supportedFilters = [
            ImageInterface::FILTER_UNDEFINED,
            ImageInterface::FILTER_BESSEL,
            ImageInterface::FILTER_BLACKMAN,
            ImageInterface::FILTER_BOX,
            ImageInterface::FILTER_CATROM,
            ImageInterface::FILTER_CUBIC,
            ImageInterface::FILTER_GAUSSIAN,
            ImageInterface::FILTER_HANNING,
            ImageInterface::FILTER_HAMMING,
            ImageInterface::FILTER_HERMITE,
            ImageInterface::FILTER_LANCZOS,
            ImageInterface::FILTER_MITCHELL,
            ImageInterface::FILTER_POINT,
            ImageInterface::FILTER_QUADRATIC,
            ImageInterface::FILTER_SINC,
            ImageInterface::FILTER_TRIANGLE,
        ];

        $sampleFilter = in_array($sampleFilter, $supportedFilters) ? $sampleFilter : ImageInterface::FILTER_UNDEFINED;

        $this->sampleFilter = $sampleFilter;
    }

    /**
     * Applies additional sharpening.
     *
     * Internal use only.
     *
     * @param bool $sharpen
     *   TRUE to sharpen, FALSE to leave as is.
     */
    private function setSharpen($sharpen = false)
    {
        $this->sharpen = filter_var($sharpen, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Convert image to specific format.
     *
     * Internal use only.
     *
     * @param string $format
     *   Desired image format.
     */
    private function setFormat($format)
    {
        $allowedFormats = [
            'jpeg',
            'jpg',
            'gif',
            'png',
            'webp',
        ];
        $format = in_array($format, $allowedFormats) ? $format : 'jpeg';

        $this->format = $format;
    }
}
