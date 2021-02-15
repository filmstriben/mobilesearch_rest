<?php

namespace App\IO;

use JMS\Serializer\Annotation as JMS;

/**
 * Class ConfigurationOutput.
 *
 * Stub of ConfigurationOutput collection structure.
 */
class ConfigurationOutput
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
     * A set of configuration options, if any.
     *
     * @var array
     * @JMS\Type("array")
     */
    private $items;
}
