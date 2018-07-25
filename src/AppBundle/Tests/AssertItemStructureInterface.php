<?php

namespace AppBundle\Tests;

interface AssertItemStructureInterface
{
    /**
     * Asserts a single item structure.
     *
     * @param array $item
     */
    public function assertItemStructure(array $item);
}
