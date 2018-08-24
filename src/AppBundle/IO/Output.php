<?php

namespace AppBundle\IO;

use JMS\Serializer\Annotation as JMS;

/**
 * Class Output
 *
 * Stub of generic item collection structure.
 */
class Output
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
     * NOT USED.
     *
     * @var array
     * @JMS\Type("array")
     */
    private $items;
}
