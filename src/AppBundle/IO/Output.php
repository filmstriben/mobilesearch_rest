<?php

namespace AppBundle\IO;

use JMS\Serializer\Annotation as JMS;

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
     * A set of response items, NOT USED.
     *
     * @var array
     * @JMS\Type("array")
     */
    private $items;
}
