<?php

namespace AppBundle\Services;

use Imagine\Exception\RuntimeException;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Imagine\Imagick\Imagine as ImagickImagine;
use Imagine\Gd\Imagine as GdImagine;

/**
 * Class ImageConverter.
 */
class ImageConverter implements ImageConverterInterface {

    const ASPECT_PRECISION = 3;

    protected $samplingFilter;

    protected $imagine;

    protected $quality;

    protected $format;

    /**
     * ImageConverter constructor.
     */
    public function __construct()
    {
        $this->imagine = /*extension_loaded('imagick') ? new ImagickImagine() :*/ new GdImagine();

        $this->setSamplingFilter(ImageInterface::FILTER_CATROM);
        $this->setFormat('jpeg');
        $this->setQuality(75);
    }

    /**
     * {@inheritDoc}
     */
    public function setFormat($format)
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

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setQuality($quality)
    {
        if (empty($quality)) {
            $quality = 75;
        } else {
            $quality = $quality > 100 ? 100 : $quality;
            $quality = $quality < 1 ? 1 : $quality;
        }

        $this->quality = $quality;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setSamplingFilter($filter)
    {
        $this->samplingFilter = $filter;

        if (empty($filter) || $this->imagine instanceof GdImagine) {
            $this->samplingFilter = ImageInterface::FILTER_UNDEFINED;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function convert($source, $target, $width, $height, $sharpen = true, $strip = true)
    {
        try {
            $image = $this->imagine->open($source);
        }
        catch (RuntimeException $exception) {
            throw new ImageConverterException("Failed to open source image '{$source}' with exception: {$exception->getMessage()}");
        }

        try {
            $this->resizeImage($image, $width, $height);
        }
        catch (RuntimeException $exception) {
            throw new ImageConverterException("Failed to resize image '{$source}' with exception: {$exception->getMessage()}");
        }

        if ($sharpen) {
            $image->effects()->sharpen();
        }

        // Clear meta-data to save bandwidth.
        if ($strip) {
            $image->strip();
        }

        // TODO: Preserve ICC profile, if any.

        try {
            $image->save($target, [
                'quality' => $this->quality,
                'format' => $this->format,
            ]);
        } catch (\Exception $exception) {
            throw new ImageConverterException("Failed to save source image '{$source}' to target '{$target}' with exception(s): {$exception->getMessage()}, {$exception->getPrevious()->getMessage()}");
        }
    }

    /**
     * Re-sizes and crops an image object.
     *
     * @param \Imagine\Image\ImageInterface $image
     *   Image object.
     * @param int $wantedWidth
     *   Image target width.
     * @param int $wantedHeight
     *   Image target height.
     *
     * @return \Imagine\Image\ImageInterface
     *   Imagine image object.
     */
    protected function resizeImage(ImageInterface $image, $wantedWidth = 0, $wantedHeight = 0)
    {
        $imageManipulations = $this->getResizeDimensions(
            $image->getSize()->getWidth(),
            $image->getSize()->getHeight(),
            (int) $wantedWidth,
            (int) $wantedHeight
        );

        $image->resize($imageManipulations['resize'], $this->samplingFilter);
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
     * @param int $originalWidth
     *   Original image width.
     * @param int $originalHeight
     *   Original image height.
     * @param int $targetWidth
     *   Target image width.
     * @param int $targetHeight
     *   Target image height.
     *
     * @return array
     *   A set of instructions needed to be applied to original image.
     *   - resize: size of the image to crop from (Box object).
     *   - crop: coordinates where to crop the image (Point object).
     *   - final_size: Requested image size dimensions.
     */
    private function getResizeDimensions($originalWidth, $originalHeight, $targetWidth, $targetHeight)
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
}
