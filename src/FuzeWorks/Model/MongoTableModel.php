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
use FuzeWorks\Database;
use FuzeWorks\DatabaseEngine\MongoEngine;
use FuzeWorks\Exception\DatabaseException;
use FuzeWorks\Factory;
use MongoDB\Collection;

class MongoTableModel implements iDatabaseTableModel
{

    /**
     * Holds the FuzeWorks Database loader
     *
     * @var Database
     */
    private $databases;

    /**
     * Holds the PDOEngine for this model
     *
     * @var MongoEngine
     */
    protected $dbEngine;

    /**
     * Holds the collection that is being modified
     *
     * @var Collection
     */
    protected $collection;

    /**
     * Initializes the model to connect with the database.
     *
     * @param string $connectionName
     * @param array $parameters
     * @param string $tableName
     * @throws DatabaseException
     * @see MongoEngine::setUp()
     */
    public function __construct(string $connectionName = 'default', array $parameters = [], string $tableName = 'default.default')
    {
        if (is_null($this->databases))
            $this->databases = Factory::getInstance()->databases;

        // Load databaseEngine
        $this->dbEngine = $this->databases->get($connectionName, 'mongo', $parameters);

        // Determine the collection
        $this->collection = $this->getCollection($tableName);
    }

    /**
     * @param string $collectionString
     * @return Collection
     * @throws DatabaseException
     */
    protected function getCollection(string $collectionString)
    {
        // Determine collection
        $coll = explode('.', $collectionString);
        if (count($coll) != 2)
            throw new DatabaseException("Could not load MongoTableModel. Provided tableName is not a valid collection string.");

        return $this->dbEngine->{$coll[0]}->{$coll[1]};
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'mongo';
    }

    /**
     * @param array $data
     * @param array $options
     * @param string $table
     * @return int
     * @throws DatabaseException
     */
    public function create(array $data, array $options = [], string $table = 'default'): int
    {
        // If not data is provided, stop now
        if (empty($data))
            throw new DatabaseException("Could not create data. No data provided.");

        // Select collection
        if ($table == 'default')
            $collection = $this->collection;
        else
            $collection = $this->getCollection($table);

        // And execute the request
        if ($this->arrIsAssoc($data))
            $res = $collection->insertOne($data, $options);
        else
            $res = $collection->insertMany($data, $options);

        // And return the count of inserted documents
        return $res->getInsertedCount();
    }

    /**
     * @param array $filter
     * @param array $options
     * @param string $table
     * @return array
     * @throws DatabaseException
     */
    public function read(array $filter = [], array $options = [], string $table = 'default'): array
    {
        // Select collection
        if ($table == 'default')
            $collection = $this->collection;
        else
            $collection = $this->getCollection($table);

        // Execute the request
        $results = $collection->find($filter, $options);

        // Return the result
        $return = [];
        foreach ($results->toArray() as $result)
            $return[] = iterator_to_array($result);

        return $return;
    }

    /**
     * @param array $data
     * @param array $filter
     * @param array $options
     * @param string $table
     * @return int
     * @throws DatabaseException
     */
    public function update(array $data, array $filter, array $options = [], string $table = 'default'): int
    {
        // If not data is provided, stop now
        if (empty($data))
            throw new DatabaseException("Could not create data. No data provided.");

        // Select collection
        if ($table == 'default')
            $collection = $this->collection;
        else
            $collection = $this->getCollection($table);

        // And execute the request
        $data = ['$set' => $data];
        $res = $collection->updateMany($filter, $data, $options);

        // Return the result
        return $res->getModifiedCount();
    }

    /**
     * @param array $filter
     * @param array $options
     * @param string $table
     * @return int
     * @throws DatabaseException
     */
    public function delete(array $filter, array $options = [], string $table = 'default'): int
    {
        // Select collection
        if ($table == 'default')
            $collection = $this->collection;
        else
            $collection = $this->getCollection($table);

        // Execute the request
        $res = $collection->deleteMany($filter, $options);

        // Return the result
        return $res->getDeletedCount();
    }

    /**
     * @return bool
     */
    public function transactionStart(): bool
    {
        // TODO: Implement transactionStart() method.
    }

    /**
     * @return bool
     */
    public function transactionEnd(): bool
    {
        // TODO: Implement transactionEnd() method.
    }

    /**
     * @return bool
     */
    public function transactionCommit(): bool
    {
        // TODO: Implement transactionCommit() method.
    }

    /**
     * @return bool
     */
    public function transactionRollback(): bool
    {
        // TODO: Implement transactionRollback() method.
    }

    /**
     * Determines whether an array is associative or numeric
     *
     * @param array $arr
     * @return bool
     */
    private function arrIsAssoc(array $arr): bool
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}