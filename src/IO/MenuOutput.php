<?php

namespace App\IO;

use JMS\Serializer\Annotation as JMS;

/**
 * Class MenuOutput
 *
 * Stub of MenuItem collection structure.
 */
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
     * @JMS\Type("array<App\IO\MenuItem>")
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
