<?php
/**
 * FuzeWorks Framework Database Component.
 *
 * The FuzeWorks PHP FrameWork
 *
 * Copyright (C) 2013-2018 TechFuze
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
 * @copyright Copyright (c) 2013 - 2018, TechFuze. (http://techfuze.net)
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @link  http://techfuze.net/fuzeworks
 * @since Version 1.1.4
 *
 * @version Version 1.1.4
 */

namespace FuzeWorks;
use FuzeWorks\DatabaseEngine\iDatabaseEngine;
use FuzeWorks\DatabaseEngine\MongoEngine;
use FuzeWorks\DatabaseEngine\PDOEngine;
use FuzeWorks\Event\DatabaseLoadDriverEvent;
use FuzeWorks\Event\DatabaseLoadTableModelEvent;
use FuzeWorks\Exception\DatabaseException;
use FuzeWorks\Exception\EventException;
use FuzeWorks\Model\iDatabaseTableModel;
use FuzeWorks\Model\MongoTableModel;
use FuzeWorks\Model\PDOTableModel;

/**
 * Database loading class
 * 
 * Loads databases, forges and utilities in a standardized manner. 
 * 
 * @author  TechFuze <contact@techfuze.net>
 * @copyright (c) 2013 - 2014, TechFuze. (https://techfuze.net)
 * 
 */
class Database
{

    /**
     * Current database config as found the database config file with highest priority
     *
     * @var array
     */
    protected $dbConfig;

    /**
     * All engines that can be used for databases
     *
     * @var iDatabaseEngine[]
     */
    protected $engines = [];

    /**
     * All tableModels that can be used for connections
     *
     * @var iDatabaseTableModel[]
     */
    protected $tableModels = [];

    /**
     * Whether all DatabaseEngines have been loaded yet
     *
     * @var bool
     */
    protected $enginesLoaded = false;

    /**
     * Array of all the database engines
     *
     * @var iDatabaseEngine[]
     */    
    protected $connections = [];

    /**
     * Array of all the tableModels
     *
     * @var iDatabaseTableModel[]
     */
    protected $tables;
    
    /**
     * Register with the TracyBridge upon startup
     */
    public function init()
    {
        $this->dbConfig = Factory::getInstance()->config->get('database')->toArray();

        if (class_exists('Tracy\Debugger', true))
            DatabaseTracyBridge::register();
    }

    /**
     * Close connections when shutting down FuzeWorks
     */
    public function __destruct()
    {
        foreach ($this->connections as $connection)
            $connection->tearDown();
    }

    /**
     *
     * When providing a database using the databaseLoadDriverEvent, parameters and connectionName
     * will be ignored.
     *
     * @param string $connectionName
     * @param string $engineName
     * @param array $parameters
     * @return iDatabaseEngine
     * @throws DatabaseException
     */
    public function get(string $connectionName = 'default', string $engineName = '', array $parameters = []): iDatabaseEngine
    {
        // Fire the event to allow settings to be changed
        /** @var DatabaseLoadDriverEvent $event */
        try {
            $event = Events::fireEvent('databaseLoadDriverEvent', strtolower($engineName), $parameters, $connectionName);
        } catch (EventException $e) {
            throw new DatabaseException("Could not get database. databaseLoadDriverEvent threw exception: '".$e->getMessage()."'");
        }
        if ($event->isCancelled())
            throw new DatabaseException("Could not get database. Cancelled by databaseLoadDriverEvent.");

        /** @var iDatabaseEngine $engine */
        // If a databaseEngine is provided by the event, use that. Otherwise search in the list of engines
        if (is_object($event->databaseEngine) && $event->databaseEngine instanceof iDatabaseEngine)
        {
            // Do intervention first
            $engine = $this->connections[$event->connectionName] = $event->databaseEngine;
            if (!$engine->isSetup())
                $engine->setUp($event->parameters);
        }
        elseif (isset($this->connections[$event->connectionName]))
        {
            // Do already exists second
            $engine = $this->connections[$event->connectionName];
        }
        elseif (!empty($event->engineName) && !empty($event->parameters))
        {
            // Do provided config third
            $engineClass = get_class($this->fetchEngine($event->engineName));
            $engine = $this->connections[$event->connectionName] = new $engineClass();
            $engine->setUp($event->parameters);
        }
        else
        {
            // Do external config fourth
            if (!isset($this->dbConfig['connections'][$event->connectionName]))
                throw new DatabaseException("Could not get database. Database not found in config.");

            $engineName = $this->dbConfig['connections'][$event->connectionName]['engineName'];
            $engineClass = get_class($this->fetchEngine($engineName));
            $engine = $this->connections[$event->connectionName] = new $engineClass();
            $engine->setUp($this->dbConfig['connections'][$event->connectionName]);
        }

        // Tie it into the Tracy Bar if available
        if (class_exists('\Tracy\Debugger', true))
            DatabaseTracyBridge::registerDatabase($engine);

        return $engine;
    }

    /**
     * @param string $tableName
     * @param string $connectionName
     * @param string $engineName
     * @param array $parameters
     * @return iDatabaseTableModel
     * @throws DatabaseException
     */
    public function getTableModel(string $tableName, string $connectionName = 'default', string $engineName = '', array $parameters = []): iDatabaseTableModel
    {
        try {
            /** @var DatabaseLoadTableModelEvent $event */
            $event = Events::fireEvent('databaseLoadTableModelEvent', strtolower($engineName), $parameters, $connectionName, $tableName);
        } catch (EventException $e) {
            throw new DatabaseException("Could not get TableModel. databaseLoadTableModelEvent threw exception: '" . $e->getMessage() . "'");
        }

        if ($event->isCancelled())
            throw new DatabaseException("Could not get TableModel. Cancelled by databaseLoadTableModelEvent.");

        /** @var iDatabaseTableModel $tableModel */
        // If a TableModel is provided by the event, use that. Otherwise search in the list of tableModels
        if (is_object($event->tableModel) && $event->tableModel instanceof iDatabaseTableModel)
        {
            $tableModel = $this->tables[$event->connectionName . "|" . $event->tableName] = $event->tableModel;
            if (!$tableModel->isSetup())
                $tableModel->setUp($this->get($event->connectionName, $tableModel->getEngineName(), $event->parameters), $event->tableName);
        }
        // If the connection already exists, use that
        elseif (isset($this->tables[$event->connectionName . "|" . $event->tableName]))
        {
            $tableModel = $this->tables[$event->connectionName . "|" . $event->tableName];
        }
        // Otherwise use the provided configuration
        else
        {
            // First the engine shall be fetched, so the name of the tableModel is known
            $engine = $this->get($event->connectionName, $event->engineName, $event->parameters);
            $tableModelClass = get_class($this->fetchTableModel($engine->getName()));

            // Load the tableModel and add the engine
            $tableModel = $this->tables[$event->connectionName . "|" . $event->tableName] = new $tableModelClass();
            $tableModel->setUp($engine, $event->tableName);
        }

        // And return the tableModel
        return $tableModel;
    }

    /**
     * Get a loaded database engine.
     *
     * @param string $engineName
     * @return iDatabaseEngine
     * @throws DatabaseException
     */
    public function fetchEngine(string $engineName): iDatabaseEngine
    {
        // First retrieve the name
        $engineName = strtolower($engineName);

        // Then load all engines
        $this->loadDatabaseComponents();

        // If the engine exists, return it
        if (isset($this->engines[$engineName]))
            return $this->engines[$engineName];

        // Otherwise throw exception
        throw new DatabaseException("Could not get engine. Engine does not exist.");
    }

    /**
     * Fetch a loaded TableModel
     *
     * @param string $tableModelName
     * @return iDatabaseTableModel
     * @throws DatabaseException
     */
    public function fetchTableModel(string $tableModelName): iDatabaseTableModel
    {
        // First retrieve the name
        $tableModelName = strtolower($tableModelName);

        // Then load all the tableModels
        $this->loadDatabaseComponents();

        // If the tableModel exists, return it
        if (isset($this->tableModels[$tableModelName]))
            return $this->tableModels[$tableModelName];

        // Otherwise throw an exception
        throw new DatabaseException("Could not get tableModel. TableModel does not exist.");
    }

    /**
     * Register a new database engine
     *
     * @param iDatabaseEngine $databaseEngine
     * @return bool
     * @throws DatabaseException
     */
    public function registerEngine(iDatabaseEngine $databaseEngine): bool
    {
        // First retrieve the name
        $engineName = strtolower($databaseEngine->getName());

        // Check if the engine is already set
        if (isset($this->engines[$engineName]))
            throw new DatabaseException("Could not register engine. Engine '".$engineName."' already registered.");


        // Install it
        $this->engines[$engineName] = $databaseEngine;
        Logger::log("Registered Database Engine: '" . $engineName . "'");

        return true;
    }

    /**
     * Register a new database tableModel
     *
     * @param iDatabaseTableModel $tableModel
     * @return bool
     * @throws DatabaseException
     */
    public function registerTableModel(iDatabaseTableModel $tableModel): bool
    {
        // First retrieve the name
        $tableModelName = strtolower($tableModel->getName());

        // Check if the tableModel is already set
        if (isset($this->tableModels[$tableModelName]))
            throw new DatabaseException("Could not register tableModel. TableModel '" . $tableModelName . "' already registered.");

        // Install it
        $this->tableModels[$tableModelName] = $tableModel;
        Logger::log("Registered TableModel type: '" . $tableModelName . "'");

        return true;
    }

    /**
     * Load all databaseEngines by firing a databaseLoadEngineEvent and by loading all the default engines
     *
     * @return bool
     * @throws DatabaseException
     */
    protected function loadDatabaseComponents(): bool
    {
        // If already loaded, skip
        if ($this->enginesLoaded)
            return false;

        // Fire engine event
        try {
            Events::fireEvent('databaseLoadEngineEvent');
        } catch (EventException $e) {
            throw new DatabaseException("Could not load database engines. databaseLoadEngineEvent threw exception: '" . $e->getMessage() . "'");
        }

        // Load the engines provided by the DatabaseComponent
        $this->registerEngine(new PDOEngine());
        $this->registerEngine(new MongoEngine());

        // Load the tableModels provided by the DatabaseComponent
        $this->registerTableModel(new PDOTableModel());
        $this->registerTableModel(new MongoTableModel());

        // And save results
        $this->enginesLoaded = true;
        return true;
    }



}