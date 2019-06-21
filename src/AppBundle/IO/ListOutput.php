<?php

namespace AppBundle\IO;

use JMS\Serializer\Annotation as JMS;

/**
 * Class ListOutput
 *
 * Stub of ListItem collection output structure.
 */
class ListOutput
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
     * @JMS\Type("array<AppBundle\IO\ListItem>")
     */
    private $items;

    /**
     * Total number of available items.
     *
     * @var array
     * @JMS\Type("integer")
     */
    private $hits;
}
