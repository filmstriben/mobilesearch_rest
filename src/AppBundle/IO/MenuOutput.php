<?php

namespace AppBundle\IO;

use JMS\Serializer\Annotation as JMS;

class MenuOutput
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
     * @JMS\Type("array<AppBundle\IO\MenuItem>")
     */
    private $items;
}
