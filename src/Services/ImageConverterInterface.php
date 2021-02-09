<?php

namespace App\Services;

use Imagine\Image\ImageInterface;

/**
 * Interface ImageConverterInterface.
 */
interface ImageConverterInterface {
    /**
     * Attemps to convert and save an image.
     *
     * @param string $source
     *   Original image path.
     * @param $target
     *   Image target path.
     * @param int $width
     *   Target width.
     * @param int $height
     *   Target height.
     * @param bool $sharpen
     *   Enhance details.
     * @param bool $strip
     *   Strip metadata.
     *
     * @throws \App\Services\ImageConverterException
     *
     * @return void
     */
    public function convert($source, $target, $width, $height, $sharpen = true, $strip = true);

    /**
     * Sets image quality.
     *
     * @param int $quality
     *   Image quality.
     *
     * @return $this
     */
    public function setQuality($quality);

    /**
     * Sets image format.
     *
     * @param string $format
     *   Image format.
     *
     * @return $this
     */
    public function setFormat($format);

    /**
     * Sets sampling filter.
     *
     * @param string $filter
     *   Filter definition.
     *
     * @return $this
     */
    public function setSamplingFilter($filter);
}
