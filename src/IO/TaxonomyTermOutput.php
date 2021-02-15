<?php

namespace App\IO;

use JMS\Serializer\Annotation as JMS;

/**
 * Class TaxonomyTermOutput
 *
 * Stub of taxonomy terms collection structure.
 */
class TaxonomyTermOutput
{
    /**
     * Response status.
     *
     * @var bool
     * @JMS\Type("boolean")
     */
    private $status;

    /**
     * Response message, if any.
     *
     * @var string
     * @JMS\Type("string")
     */
    private $message;

    /**
     * A set of response items, if any.
     *
     * @var array
     * @JMS\Type("array<string>")
     */
    private $items;
}
