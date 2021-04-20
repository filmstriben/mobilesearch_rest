<?php

namespace App\Services;

/**
 * Class MatchOrderer.
 *
 * Orders the results in an user-defined exact way.
 */
class MatchOrderer
{
    /**
     * Checks whether sorting param is match sorting.
     *
     * @param string $order
     *   Sorting type.
     *
     * @return array
     *   Field values in target order.
     */
    private function isMatchSorting(string $order): array
    {
        $orderMatches = [];
        if (preg_match('~match\(([0-9,]+)\)~', strtolower($order), $orderMatches)) {
            return array_filter(
                array_filter(explode(',', $orderMatches[1])),
                'is_numeric'
            );
        }

        return [];
    }

    /**
     * Rearranges the data result in concordance with input order.
     *
     * @param \App\Document\Content[] $data
     *   Data set to rearrange.
     * @param string $field
     *   Field used for order.
     * @param string $order
     *   Sorting type.
     *
     * @return array
     *   Exact ordered results.
     */
    public function order(array $data, string $field = '', string $order = ''): array
    {
        $orderedItems = [];

        foreach ($this->isMatchSorting($order) as $match) {
            foreach ($data as $k => $item) {
                $itemArray = $item->toArray();
                $itemValue = $this->getNestedValue($itemArray, $field);

                if ($itemValue == $match) {
                    $orderedItems[] = $item;
                    unset($data[$k]);
                }
            }
        }

        return array_merge($orderedItems, $data);
    }

    /**
     * Fetches a nested array value.
     *
     * @param array $input
     *   An array of data.
     * @param string $path
     *   Path to the value, delimited by dots.
     *
     * @return string
     *   Nested value.
     */
    private function getNestedValue(array $input, string $path):string
    {
        $path = array_filter(explode('.', $path));

        while ($key = current($path)) {
            $input = $input[$key] ?? null;
            next($path);
        }

        return (string) $input;
    }
}

