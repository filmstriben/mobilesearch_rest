<?php

namespace AppBundle\IO;

use JMS\Serializer\Annotation as JMS;

class Input
{
    /**
     * Credentials object.
     *
     * @var string
     * @JMS\Type("asd")
     */
    private $credentials;

    /**
     * @var string
     */
    private $body;
}
