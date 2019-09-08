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
use MongoDB\ChangeStream;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\Exception\InvalidArgumentException;
use MongoDB\Driver\Manager;
use function MongoDB\Driver\Monitoring\addSubscriber;
use MongoDB\Driver\ReadConcern;
use MongoDB\Driver\ReadPreference;
use MongoDB\Driver\Session;
use MongoDB\Driver\WriteConcern;
use MongoDB\Exception\RuntimeException;
use MongoDB\Model\DatabaseInfoIterator;

/**
 * Class MongoEngine
 *
 * The following additional methods can be accessed through the __call method
 * @method array|object dropDatabase(string $databaseName, array $options = [])
 * @method Manager getManager()
 * @method ReadConcern getReadConcern()
 * @method ReadPreference getReadPreference()
 * @method array getTypeMap()
 * @method WriteConcern getWriteConcern()
 * @method DatabaseInfoIterator listDatabases(array $options = [])
 * @method Collection selectCollection(string $databaseName, string $collectionName, array $options = [])
 * @method Database selectDatabase(string $databaseName, array $options = [])
 * @method Session startSession(array $options = [])
 * @method ChangeStream watch(array $pipeline = [], array $options = [])
 */
class MongoEngine extends DatabaseDriver
{

    /**
     * Whether the Engine has been set up
     *
     * @var bool
     */
    protected $setUp = false;

    /**
     * @var Client
     */
    protected $mongoConnection;

    /**
     * Connection string with the database
     *
     * @var string
     */
    protected $uri;

    /**
     * Returns the name of this engine
     *
     * @return string
     */
    public function getName(): string
    {
        return 'mongo';
    }

    public function getConnectionDescription(): string
    {
        if (is_null($this->mongoConnection))
            return 'none';

        return $this->uri;
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
        $this->uri = isset($parameters['uri']) ? $parameters['uri'] : null;
        $uriOptions = isset($parameters['uriOptions']) ? $parameters['uriOptions'] : [];
        $driverOptions = isset($parameters['driverOptions']) ? $parameters['driverOptions'] : [];

        // Don't attempt and connect without a URI
        if (is_null($this->uri))
            throw new DatabaseException("Could not setUp MongoEngine. No URI provided");

        // Import username and password
        if (isset($parameters['username']) && isset($parameters['password']))
        {
            $uriOptions['username'] = $parameters['username'];
            $uriOptions['password'] = $parameters['password'];
        }

        // And set FuzeWorks app name
        $uriOptions['appname'] = 'FuzeWorks';

        try {
            $this->mongoConnection = new Client($this->uri, $uriOptions, $driverOptions);
        } catch (InvalidArgumentException | RuntimeException $e) {
            throw new DatabaseException("Could not setUp MongoEngine. MongoDB threw exception: '" . $e->getMessage() . "'");
        }

        // Set this engine as set up
        $this->setUp = true;

        // Register subscriber
        $subscriber = new MongoCommandSubscriber($this);
        addSubscriber($subscriber);

        // And return true upon success
        return true;
    }

    public function logMongoQuery(string $queryString, int $queryData, float $queryTimings, array $errorInfo = [])
    {
        $this->logQuery($queryString, $queryData, $queryTimings, $errorInfo);
    }

    /**
     * Method called by \FuzeWorks\Database to tear down the database connection upon shutdown
     *
     * @return bool
     */
    public function tearDown(): bool
    {
        // MongoDB does not require any action. Always return true
        return true;
    }

    /**
     * Call methods on the Mongo Connection
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->mongoConnection->{$name}(...$arguments);
    }

    /**
     * Get properties from the Mongo Connection
     *
     * @param $name
     * @return Database
     */
    public function __get($name): Database
    {
        return $this->mongoConnection->$name;
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
}