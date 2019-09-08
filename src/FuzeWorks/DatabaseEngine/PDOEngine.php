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

namespace FuzeWorks\DatabaseEngine;
use FuzeWorks\Exception\DatabaseException;
use FuzeWorks\Exception\TransactionException;
use FuzeWorks\Logger;
use FuzeWorks\Model\iDatabaseTableModel;
use PDO;
use PDOException;
use PDOStatement;

/**
 * Class PDOEngine Class
 *
 * The following additional methods can be accessed through the __call method
 * @method bool exec(string $statement)
 * @method mixed getAttribute(int $attribute)
 * @method string lastInsertId(string $name = null)
 * @method string quote(string $string, int $parameter_type = PDO::PARAM_STR)
 * @method bool setAttribute(int $attribute, mixed $value)
 */
class PDOEngine extends DatabaseDriver
{

    /**
     * Whether the Engine has been set up
     *
     * @var bool
     */
    protected $setUp = false;

    /**
     * The PDO object connected with the database
     *
     * @var PDO
     */
    protected $pdoConnection;

    /**
     * Connection string with the database
     *
     * @var string
     */
    protected $dsn;

    /**
     * Whether a transaction has failed and should be reverted in the future
     *
     * @var bool
     */
    protected $transactionFailed = false;

    /**
     * Whether a transaction should be automatically committed if not manually aborted by the user.
     *
     * If enabled, will automatically commit or revert upon shutdown
     * If disabled, will not do anything
     *
     * @var bool
     */
    protected $transactionAutocommit = false;

    /**
     * Returns the name of this engine
     *
     * @return string
     */
    public function getName(): string
    {
        return 'pdo';
    }

    public function getConnectionDescription(): string
    {
        return is_null($this->dsn) ? 'none' : $this->dsn;
    }

    /**
     * Whether the database connection has been set up yet
     *
     * @return bool
     */
    public function isSetup(): bool
    {
        return $this->setUp;
    }

    /**
     * Method called by \FuzeWorks\Database to setUp the database connection
     *
     * @param array $parameters
     * @return bool
     * @throws DatabaseException
     */
    public function setUp(array $parameters): bool
    {
        // Prepare variables for connection
        $this->dsn = isset($parameters['dsn']) ? $parameters['dsn'] : null;
        $username = isset($parameters['username']) ? $parameters['username'] : '';
        $password = isset($parameters['password']) ? $parameters['password'] : '';

        // Don't attempt connection without DSN
        if (is_null($this->dsn))
            throw new DatabaseException("Could not setUp PDOEngine. No DSN provided");

        // Set some base parameters which are required for FuzeWorks
        $parameters[PDO::ATTR_ERRMODE] = PDO::ERRMODE_SILENT;

        // Attempt to connect. Throw exception on failure
        try {
            $this->pdoConnection = new PDO($this->dsn, $username, $password, $parameters);
        } catch (PDOException $e) {
            throw new DatabaseException("Could not setUp PDOEngine. PDO threw PDOException: '" . $e->getMessage() . "'");
        }

        // Set this engine as set up
        $this->setUp = true;

        // And return true upon success
        return true;
    }

    /**
     * Method called by \FuzeWorks\Database to tear down the database connection upon shutdown
     *
     * @return bool
     * @throws TransactionException
     */
    public function tearDown(): bool
    {
        // Commit or rollback all changes to the database
        $this->transactionEnd();

        // And close the connection
        $this->pdoConnection = null;
        return true;
    }

    /**
     * Call methods on the PDO Connection
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->pdoConnection->{$name}(...$arguments);
    }

    /**
     * Get properties from the PDO Connection
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->pdoConnection->$name;
    }

    /**
     * Set properties on the PDO Connection
     *
     * @param $name
     * @param $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        return $this->pdoConnection->$name = $value;
    }

    /**
     * Internal method used to report queries by the \FuzeWorks\DatabaseEngine\PDOStatement
     *
     * @internal
     * @param string $queryString
     * @param int $queryData
     * @param float $queryTimings
     * @param array $errorInfo
     */
    public function logPDOQuery(string $queryString, int $queryData, float $queryTimings, array $errorInfo = [])
    {
        $errorInfo = empty($errorInfo) ? $this->error() : $errorInfo;
        $this->logQuery($queryString, $queryData, $queryTimings, $errorInfo);
    }

    /**
     * Perform a query with the database. Only supports queries.
     *
     * Should only be used for reading data without dynamic statements.
     *
     * @param string $sql
     * @return PDOStatement
     * @throws DatabaseException
     */
    public function query(string $sql): PDOStatement
    {
        if (empty($sql))
            throw new DatabaseException("Could not run query. Provided query is empty.");

        // Run the query and benchmark the time
        $benchmarkStart = microtime(true);
        $result = $this->pdoConnection->query($sql);
        $benchmarkEnd = microtime(true) - $benchmarkStart;

        // Log the query
        $this->logPDOQuery($sql, 0, $benchmarkEnd);

        // If the query failed, handle the error
        if ($result === false)
        {
            // Mark the transaction as failed
            $this->transactionFailed = true;

            // And throw an exception
            throw new DatabaseException("Could not run query. Database returned an error. Error code: " . $this->error()['code']);
        }

        return $result;
    }

    /**
     * Create a PDOStatement to alter data on the database.
     *
     * @param string $statement
     * @param array $driver_options
     * @return PDOStatementWrapper
     */
    public function prepare(string $statement, array $driver_options = []): PDOStatementWrapper
    {
        return new PDOStatementWrapper(
            $this->pdoConnection->prepare($statement, $driver_options),
            array($this, 'logPDOQuery'),
            $this
        );
    }

    /**
     * Generates an error message for the last failure in PDO
     *
     * @return array
     */
    protected function error(): array
    {
        $error = [];
        $pdoError = $this->pdoConnection->errorInfo();
        if (empty($pdoError[0]) || $pdoError[0] == '00000')
            return $error;

        $error['code'] = isset($pdoError[1]) ? $pdoError[0] . '/' . $pdoError[1] : $pdoError[0];
        if (isset($pdoError[2]))
            $error['message'] = $pdoError[2];

        return $error;
    }

    /**
     * Start a transaction
     *
     * @return bool
     * @throws TransactionException
     */
    public function transactionStart(): bool
    {
        try {
            return $this->pdoConnection->beginTransaction();
        } catch (PDOException $e) {
            throw new TransactionException("Could not start transaction. PDO threw PDOException: '" . $e->getMessage() . "'");
        }
    }

    /**
     * End a transaction.
     *
     * Only runs of autocommit is enabled; and a transaction is running.
     * Automatically rolls back changes if an error occurs with a query
     *
     * @return bool
     * @throws TransactionException
     */
    public function transactionEnd(): bool
    {
        // If autocommit is disabled, don't do anything
        if (!$this->transactionAutocommit)
            return false;

        // If there is no transaction, there is nothing to rollback
        if (!$this->pdoConnection->inTransaction())
            return false;

        // If a transaction has failed, it should be rolled back
        if ($this->transactionFailed === true)
        {
            $this->transactionRollback();
            Logger::logError("PDOEngine transaction failed. Transaction has been rolled back.");
        }

        return $this->transactionCommit();
    }

    /**
     * Commit a transaction
     *
     * @return bool
     * @throws TransactionException
     */
    public function transactionCommit(): bool
    {
        try {
            return $this->pdoConnection->commit();
        } catch (PDOException $e) {
            throw new TransactionException("Could not commit transaction. PDO threw PDOException: '" . $e->getMessage() . "'");
        }
    }

    /**
     * Roll back a transaction
     *
     * @return bool
     * @throws TransactionException
     */
    public function transactionRollback(): bool
    {
        try {
            return $this->pdoConnection->rollBack();
        } catch (PDOException $e) {
            throw new TransactionException("Could not rollback transaction. PDO threw PDOException: '" . $e->getMessage() . "'");
        }
    }

    /**
     * @internal
     */
    public function transactionFail()
    {
        $this->transactionFailed = true;
    }
}