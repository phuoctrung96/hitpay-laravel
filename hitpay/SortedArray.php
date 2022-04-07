<?php

namespace HitPay;

class SortedArray
{
    /**
     * Sort the array by the given priority map.
     *
     * Each call to this method makes one discrete value movement if necessary.
     *
     * @param array $priorityMap
     * @param array $array
     *
     * @return array
     */
    protected function sortArray(array $priorityMap, array $array) : array
    {
        $lastIndex = 0;

        foreach ($array as $index => $value) {
            if (!is_string($value)) {
                continue;
            }

            $stripped = head(explode(':', $value));

            if (in_array($stripped, $priorityMap)) {
                $priorityIndex = array_search($stripped, $priorityMap);

                // This value is in the priority map. If we have encountered another value that was also in the priority
                // map and was at a lower priority than the current value, we will move this value to be above the
                // previous encounter.

                if (isset($lastPriorityIndex) && $priorityIndex < $lastPriorityIndex) {
                    return $this->sortArray(
                        $priorityMap, array_values($this->moveArray($array, $index, $lastIndex))
                    );
                }

                // This value is in the priority map; but, this is the first value we have encountered from the map thus
                // far. We'll save its current index plus its index from the priority map so we can compare against them
                // on the next iterations.

                $lastIndex = $index;
                $lastPriorityIndex = $priorityIndex;
            }
        }

        return array_values(array_unique($array, SORT_REGULAR));
    }

    /**
     * Splice a value into a new position and remove the old entry.
     *
     * @param array $array
     * @param int $from
     * @param int $to
     *
     * @return array
     */
    protected function moveArray(array $array, int $from, int $to) : array
    {
        array_splice($array, $to, 0, $array[$from]);

        unset($array[$from + 1]);

        return $array;
    }

    /**
     * Create a new instance of this class.
     *
     * @param array $priorityMap
     * @param array $array
     *
     * @return array
     */
    public static function process(array $priorityMap, array $array) : array
    {
        return (new static)->sortArray($priorityMap, $array);
    }
}
