<?php
/**
 * FuzeWorks Component.
 *
 * The FuzeWorks PHP FrameWork
 *
 * Copyright (C) 2013-2019 TechFuze
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author    TechFuze
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @link  http://techfuze.net/fuzeworks
 * @since Version 1.2.0
 *
 * @version Version 1.2.0
 */

namespace FuzeWorks\Model;
use ArrayObject;
use IteratorAggregate;
use Traversable;

class TableModelResult implements IteratorAggregate
{

    /**
     * Raw result from the TableModel
     *
     * @var Traversable
     */
    private $raw;

    /**
     * Result from the TableModel, possibly altered by TableModelResult methods.
     *
     * @var ArrayObject
     */
    private $result;

    /**
     * @var Traversable
     */
    private $traversable;

    /**
     * Whether the raw input has already been fully fetched
     *
     * @var bool
     */
    private $fullyFetched = false;

    public function __construct(iterable $results)
    {
        $this->raw = $results;
        $this->traversable = $results;
    }

    /**
     * Group the results by a certain field.
     *
     * @param string $field
     * @return TableModelResult
     */
    public function group(string $field): self
    {
        // First make sure all data is fetched
        $this->allToArray();

        // Afterwards build a grouped array
        $grouped = [];
        foreach ($this->result->getIterator() as $key => $val)
        {
            // Check if this group exists within the results
            if (isset($val[$field]))
            {
                // Name of the group
                $fieldSelector = $val[$field];

                // If the group has never been found before, add the array
                if (!isset($grouped[$fieldSelector]))
                    $grouped[$fieldSelector] = [];

                unset($val[$field]);
                $grouped[$fieldSelector][] = $val;
            }
        }

        $this->result->exchangeArray($grouped);
        return $this;
    }

    /**
     * Convert the result into an array
     *
     * @return array
     */
    public function toArray(): array
    {
        // First make sure all data is fetched
        $this->allToArray();

        // And return a copy
        return $this->result->getArrayCopy();
    }

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return $this->traversable;
    }

    private function allToArray(): bool
    {
        // If the input has already been fetched, ignore it
        if ($this->fullyFetched)
            return true;

        $result = [];
        foreach ($this->raw as $key => $val)
        {
            // Clear out all numeric keys
            foreach ($val as $recKey => $recVal)
                if (is_numeric($recKey))
                    unset($val[$recKey]);

            $result[$key] = $val;
        }

        // Set the variable
        $this->result = new ArrayObject($result);

        // Afterwards modify the traversable
        $this->traversable = $this->result->getIterator();

        // Set fullyFetched to true so it doesn't get fetched again
        $this->fullyFetched = true;

        return true;
    }

}