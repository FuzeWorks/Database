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
use FuzeWorks\DatabaseEngine\PDOEngine;
use FuzeWorks\DatabaseEngine\PDOStatementWrapper;
use FuzeWorks\Exception\DatabaseException;
use FuzeWorks\Exception\EventException;
use FuzeWorks\Exception\TransactionException;
use FuzeWorks\Factory;
use PDO;
use PDOStatement;

/**
 * PDOTableModel Class
 *
 * A PDO Wrapper allowing the user to modify a table without using CRUD. No writing of SQL required!
 *
 * The following additional methods can be accessed through the __call method
 * @method PDOStatement query(string $sql)
 * @method PDOStatementWrapper prepare(string $statement, array $driver_options = [])
 * @method bool exec(string $statement)
 * @method mixed getAttribute(int $attribute)
 * @method string lastInsertId(string $name = null)
 * @method string quote(string $string, int $parameter_type = PDO::PARAM_STR)
 * @method bool setAttribute(int $attribute, mixed $value)
 */
class PDOTableModel implements iDatabaseTableModel
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
     * @var PDOEngine
     */
    protected $dbEngine;

    /**
     * The table this model manages on the database
     *
     * @var string
     */
    protected $tableName;

    /**
     * The last statement used by PDO
     *
     * @var PDOStatementWrapper
     */
    protected $lastStatement;

    /**
     * Initializes the model to connect with the database.
     *
     * @param string $connectionName
     * @param array $parameters
     * @param string|null $tableName
     * @throws DatabaseException
     * @see PDOEngine::setUp()
     */
    public function __construct(string $connectionName = 'default', array $parameters = [], string $tableName = 'default')
    {
        if (is_null($this->databases))
            $this->databases = Factory::getInstance()->databases;

        $this->dbEngine = $this->databases->get($connectionName, 'pdo', $parameters);
        $this->tableName = $tableName;
    }

    public function getName(): string
    {
        return 'pdo';
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
        // If no data is provided, stop now
        if (empty($data))
            throw new DatabaseException("Could not create data. No data provided.");

        // Select table
        if ($table == 'default')
            $table = $this->tableName;

        // Determine which fields will be inserted
        $fieldsArr = $this->createFields($data);
        $fields = $fieldsArr['fields'];
        $values = $fieldsArr['values'];

        // Generate the sql and create a PDOStatement
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$values})";

        /** @var PDOStatement $statement */
        $this->lastStatement = $this->dbEngine->prepare($sql);

        // And execute the query
        if ($this->arrIsAssoc($data))
            $this->lastStatement->execute($data);
        else
            foreach ($data as $record)
                $this->lastStatement->execute($record);

        // And return true for success
        return $this->lastStatement->rowCount();
    }

    /**
     * @todo: WRITE ABOUT FETCHMODE
     *
     * @param array $filter
     * @param array $options
     * @param string $table
     * @return array
     * @throws DatabaseException
     */
    public function read(array $filter = [], array $options = [], string $table = 'default'): array
    {
        // Select table
        if ($table == 'default')
            $table = $this->tableName;

        // Determine which fields to select. If none provided, select all
        $fields = (isset($options['fields']) && is_array($options['fields']) ? implode(',', $options['fields']) : '*');

        // Apply the filter. If none provided, don't condition it
        $where = $this->filter($filter);

        // If a JOIN is provided, create the statement
        if (isset($options['join']))
        {
            $joinType = (isset($options['join']['joinType']) ? strtoupper($options['join']['joinType']) : 'LEFT');
            $targetTable = (isset($options['join']['targetTable']) ? $options['join']['targetTable'] : null);
            $targetField = (isset($options['join']['targetField']) ? $options['join']['targetField'] : null);
            $sourceField = (isset($options['join']['sourceField']) ? $options['join']['sourceField'] : null);
            if (is_null($targetTable) || is_null($targetField) || is_null($sourceField))
                throw new DatabaseException("Could not read from '" . $table . "'. Missing fields in join options.");

            $join = "{$joinType} JOIN {$targetTable} ON {$table}.{$sourceField} = {$targetTable}.{$targetField}";
        }
        else
            $join = '';

        // Generate the sql and create a PDOStatement
        $sql = "SELECT " . $fields . " FROM {$table} {$join} " . $where;

        /** @var PDOStatement $statement */
        $this->lastStatement = $this->dbEngine->prepare($sql);

        // Return prepared statement, if requested to do so
        if (isset($options['returnPreparedStatement']) && $options['returnPreparedStatement'] == true)
            return [];

        // And execute the query
        $this->lastStatement->execute($filter);

        // And return the result
        $fetchMode = (isset($options['fetchMode']) ? $options['fetchMode'] : PDO::FETCH_ASSOC);
        if (is_array($fetchMode))
            return $this->lastStatement->fetchAll(...$fetchMode);

        return $this->lastStatement->fetchAll($fetchMode);
    }

    public function update(array $data, array $filter, array $options = [], string $table = 'default'): int
    {
        // If no data is provided, stop now
        if (empty($data))
            throw new DatabaseException("Could not update data. No data provided.");

        // Select table
        if ($table == 'default')
            $table = $this->tableName;

        // Apply the filter
        $where = $this->filter($filter);

        // Determine fields and values
        foreach ($data as $key => $val)
            $fields[] = $key."=:".$key;

        $fields = implode(', ', $fields);

        // Generate the sql and create a PDOStatement
        $sql = "UPDATE {$table} SET {$fields} {$where}";

        /** @var PDOStatement $statement */
        $this->lastStatement = $this->dbEngine->prepare($sql);

        // Merge data and filter, since both are needed by the statement
        $parameters = array_merge($data, $filter);

        // And execute the query
        $this->lastStatement->execute($parameters);

        // And return true for success
        return $this->lastStatement->rowCount();
    }

    public function delete(array $filter, array $options = [], string $table = 'default'): int
    {
        // Select table
        if ($table == 'default')
            $table = $this->tableName;

        // Apply the filter
        $where = $this->filter($filter);

        // Generate the sql and create a PDOStatement
        $sql = "DELETE FROM {$table} " . $where;

        /** @var PDOStatement $statement */
        $this->lastStatement = $this->dbEngine->prepare($sql);

        // And execute the query
        $this->lastStatement->execute($filter);

        // And return true for success
        return $this->lastStatement->rowCount();
    }

    public function getLastStatement(): PDOStatementWrapper
    {
        return $this->lastStatement;
    }

    /**
     * @return bool
     * @throws TransactionException
     */
    public function transactionStart(): bool
    {
        return $this->dbEngine->transactionStart();
    }

    /**
     * @return bool
     * @throws TransactionException
     */
    public function transactionEnd(): bool
    {
        return $this->dbEngine->transactionEnd();
    }

    /**
     * @return bool
     * @throws TransactionException
     */
    public function transactionCommit(): bool
    {
        return $this->dbEngine->transactionCommit();
    }

    /**
     * @return bool
     * @throws TransactionException
     */
    public function transactionRollback(): bool
    {
        return $this->dbEngine->transactionRollback();
    }

    /**
     * Call methods on the PDO Engine, which calls methods on the PDO Connection
     *
     * @param $name
     * @param $arguments
     * @return PDOEngine
     */
    public function __call($name, $arguments)
    {
        return $this->dbEngine->{$name}(...$arguments);
    }

    /**
     * Get properties from the PDO Engine, which gets properties from the PDO connection
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->dbEngine->$name;
    }

    /**
     * Set properties on the PDO Connection, which sets properties on the PDO Connection
     *
     * @param $name
     * @param $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        return $this->dbEngine->$name = $value;
    }

    private function filter(array $filter = []): string
    {
        if (empty($filter))
            return '';

        $whereKeys = [];
        foreach ($filter as $filterKey => $filterVal)
            $whereKeys[] = $filterKey . '=:' . $filterKey;

        return 'WHERE ' . implode(' AND ', $whereKeys);
    }

    private function createFields(array $record): array
    {
        // If multiple data is inserted at once, search the fields in the first entry
        if (!$this->arrIsAssoc($record))
            $record = $record[0];

        // Determine the fields and values
        foreach ($record as $key => $val)
        {
            $fields[] = $key;
            $values[] = ':'.$key;
        }

        // And merge them again
        $fields = implode(', ', $fields);
        $values = implode(', ', $values);

        return ['fields' => $fields, 'values' => $values];
    }

    private function arrIsAssoc(array $arr): bool
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}