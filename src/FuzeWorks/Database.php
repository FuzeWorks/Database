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
use FuzeWorks\Exception\DatabaseException;
use FuzeWorks\Exception\EventException;

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
     * Whether all DatabaseEngines have been loaded yet
     *
     * @var bool
     */
    protected $enginesLoaded = false;

    /**
     * Array of all the non-default databases
     *
     * @var iDatabaseEngine[]
     */    
    protected $connections = [];
    
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
            $engineClass = get_class($this->getEngine($event->engineName));
            $engine = $this->connections[$event->connectionName] = new $engineClass();
            $engine->setUp($event->parameters);
        }
        else
        {
            // Do external config fourth
            if (!isset($this->dbConfig['connections'][$event->connectionName]))
                throw new DatabaseException("Could not get database. Database not found in config.");

            $engineName = $this->dbConfig['connections'][$event->connectionName]['engineName'];
            $engineClass = get_class($this->getEngine($engineName));
            $engine = $this->connections[$event->connectionName] = new $engineClass();
            $engine->setUp($this->dbConfig['connections'][$event->connectionName]);
        }

        // Tie it into the Tracy Bar if available
        if (class_exists('\Tracy\Debugger', true))
            DatabaseTracyBridge::registerDatabase($engine);

        return $engine;
    }

    /**
     * Get a loaded database engine.
     *
     * @param string $engineName
     * @return iDatabaseEngine
     * @throws DatabaseException
     */
    public function getEngine(string $engineName): iDatabaseEngine
    {
        // First retrieve the name
        $engineName = strtolower($engineName);

        // Then load all engines
        $this->loadDatabaseEngines();

        // If the engine exists, return it
        if (isset($this->engines[$engineName]))
            return $this->engines[$engineName];

        // Otherwise throw exception
        throw new DatabaseException("Could not get engine. Engine does not exist.");
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
     * Load all databaseEngines by firing a databaseLoadEngineEvent and by loading all the default engines
     *
     * @return bool
     * @throws DatabaseException
     */
    protected function loadDatabaseEngines(): bool
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

        // And save results
        $this->enginesLoaded = true;
        return true;
    }



}