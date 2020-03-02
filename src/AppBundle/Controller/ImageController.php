<?php

namespace AppBundle\Controller;

use Imagine\Exception\Exception as ImagineExc;
use Imagine\Imagick\Imagine as ImagickImagine;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
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
    protected $options = [
        'quality' => 90,
        'sample_filter' => ImageInterface::FILTER_LANCZOS,
        'effect_sharpen' => false,
    ];

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
     * <p>resize parameter, when omitted, will default to original (full) image size.</p>
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
     *             "name"="resize",
     *             "dataType"="string",
     *             "description"="Image size (WIDTHxHEIGHT).",
     *             "required"=false,
     *             "format"="{\d}+x{\d}+"
     *         },
     *     }
     * )
     * @Route("/files/{agency}/{filename}")
     * @Method({"GET"})
     */
    public function imageNewAction(Request $request, $agency, $filename)
    {
        $this->response = new Response();

        $filePath = $this->filesStorageDir.'/'.$agency.'/'.$filename;

        $resize = $request->query->get('resize', $request->attributes->get('resize'));

        $quality = $request->query->get('q', 90);
        $this->setQuality($quality);

        $sampleFilter = $request->query->get('r', ImageInterface::FILTER_LANCZOS);
        $this->setSamplingFilter($sampleFilter);

        $sharpen = $request->query->get('s', false);
        $this->setSharpen($sharpen);

        $force = $request->query->get('f', false);
        $force = filter_var($force, FILTER_VALIDATE_BOOLEAN);

        $dimensions = $this->getSizeFromParam($resize);
        // Keep a separate directory for a specific size.
        $subDirectory = implode('x', $dimensions);
        // If resize parameter is received, try parse it and apply the style to
        // the image.
        if (!empty($dimensions) && implode($dimensions) != '00' && $this->checkThumbnailSubdir($subDirectory, $agency)) {
            $resizedFilePath = $this->filesStorageDir.'/'.$agency.'/'.$subDirectory.'/'.$this->options['quality'].'_'.$filename;

            $fs = new Filesystem();
            // Both when image exits or it's smaller/bigger counterpart
            // was created - replace the filepath with the result image.
            if ($fs->exists($resizedFilePath) && false === $force) {
                $filePath = $resizedFilePath;
            } elseif ($this->resizeImage($filePath, $resizedFilePath, $dimensions)) {
                $filePath = $resizedFilePath;
            }
        }

        $this->serveImage($filePath);

        return $this->response;
    }

    /**
     * Resizes and saves images.
     *
     * @param string $source          Original image path.
     * @param string $target          Target path for re-sized images.
     * @param array $wantedDimensions Desired width and height.
     *
     * @return boolean
     *   Resize action result.
     */
    protected function resizeImage($source, $target, array $wantedDimensions)
    {
        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = $this->get('logger');

        if (extension_loaded('imagick')) {
            $imagine = new ImagickImagine();
        } else {
            $imagine = new GdImagine();
        }

        try {
            $image = $imagine->open($source);
            $imageSize = $image->getSize();
            $originalSize = [
                'width' => $imageSize->getWidth(),
                'height' => $imageSize->getHeight(),
            ];
            $imageManipulations = $this->getResizeDimensions($originalSize, $wantedDimensions);
            if ($imagine instanceof ImagickImagine) {
                $image->resize($imageManipulations['resize'], $this->options['sample_filter']);
            } else {
                $image->resize($imageManipulations['resize']);
            }

            $image->crop($imageManipulations['crop'], $imageManipulations['final_size']);

            if ($this->options['effect_sharpen']) {
                $image->effects()->sharpen();
            }

            $image->save($target, $this->options);
        } catch (ImagineExc $e) {
            $logger->error('Failed to resize image "'.$source.'" with exception: '.$e->getMessage());

            return false;
        }

        return true;
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
     * @param array $targetSize   Desired target size (width and height).
     *
     * @return array              A set of instructions needed to be applied to original image.
     *                            - resize: size of the image to crop from (Box object).
     *                            - crop: coordinates where to crop the image (Point object).
     *                            - final_size: Requested image size dimensions.
     */
    protected function getResizeDimensions(array $originalSize, array $targetSize)
    {
        list ($originalWidth, $originalHeight) = array_values($originalSize);
        list ($targetWidth, $targetHeight) = array_values($targetSize);
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
     * Serves the image to the browse output.
     *
     * This serves status code 200 if OK, or 404 if the image is not found.
     * Adequate headers are passed as well.
     *
     * @param string $path Image path.
     */
    protected function serveImage($path)
    {
        try {
            $file = new File($path);
            $this->response->headers->set('Content-Type', $file->getMimeType());
            $this->response->headers->set('Cache-Control', 'max-age=86400, public');
            $this->response->headers->set('Expires', gmdate(DATE_RFC1123, time() + 86400));

            $this->response->setStatusCode(Response::HTTP_OK);
            $this->response->setContent(file_get_contents($path));
        } catch (FileNotFoundException $e) {
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            $this->response->setContent('File not found.');
        }
    }

    /**
     * Check and optionally prepare the directory where resized images
     * are stored.
     *
     * @param string $name    File name.
     * @param string $agency  Agency id.
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

        $this->options['quality'] = $quality;
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

        $this->options['sample_filter'] = $sampleFilter;
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
        $this->options['effect_sharpen'] = filter_var($sharpen, FILTER_VALIDATE_BOOLEAN);
    }
}
