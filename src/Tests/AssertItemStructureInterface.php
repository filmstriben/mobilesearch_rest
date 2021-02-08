<?php

namespace App\Tests;

/**
 * Interface AssertItemStructureInterface
 *
 * Allows to assert item structure.
 */
interface AssertItemStructureInterface
{
    /**
     * Asserts a single item structure.
     *
     * @param array $item
     */
    public function assertItemStructure(array $item);
}
