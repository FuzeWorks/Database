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

use MongoDB\Driver\Monitoring\CommandFailedEvent;
use MongoDB\Driver\Monitoring\CommandStartedEvent;
use MongoDB\Driver\Monitoring\CommandSubscriber;
use MongoDB\Driver\Monitoring\CommandSucceededEvent;
use Tracy\Debugger;

class MongoCommandSubscriber implements CommandSubscriber
{

    /**
     * @var MongoEngine
     */
    private $mongoEngine;

    /**
     * @var float
     */
    private $commandTimings = 0.0;

    /**
     * @var string
     */
    private $queryString;

    public function __construct(MongoEngine $engine)
    {
        $this->mongoEngine = $engine;
    }

    /**
     * Notification method for a failed command.
     * If the subscriber has been registered with MongoDB\Driver\Monitoring\addSubscriber(), the driver will call this method when a command has failed.
     * @link https://secure.php.net/manual/en/mongodb-driver-monitoring-commandsubscriber.commandfailed.php
     * @param CommandFailedEvent $event An event object encapsulating information about the failed command.
     * @return void
     * @throws \InvalidArgumentException on argument parsing errors.
     * @since 1.3.0
     */
    public function commandFailed(CommandFailedEvent $event)
    {
        // TODO: Implement commandFailed() method.
    }

    /**
     * Notification method for a started command.
     * If the subscriber has been registered with MongoDB\Driver\Monitoring\addSubscriber(), the driver will call this method when a command has started.
     * @link https://secure.php.net/manual/en/mongodb-driver-monitoring-commandsubscriber.commandstarted.php
     * @param CommandStartedEvent $event An event object encapsulating information about the started command.
     * @return void
     * @throws \InvalidArgumentException on argument parsing errors.
     * @since 1.3.0
     */
    public function commandStarted(CommandStartedEvent $event)
    {
        $this->commandTimings = microtime(true);
        $this->queryString = strtoupper($event->getCommandName());

        // Determine query string
        $command = $event->getCommand();
        $this->queryString .= ' \'' . $event->getDatabaseName() . '.' . $command->{$event->getCommandName()} . '\'';

        // If a projection is provided, print it
        if (isset($command->projection))
        {
            $projection = $command->projection;
            $projectionStrings = [];
            foreach ($projection as $projectionKey => $projectionVal)
                $projectionStrings[] = $projectionKey;

            $this->queryString .= " PROJECT[" . implode(',', $projectionStrings) . ']';
        }

        // If a filter is provided, print it
        if (isset($command->filter) && !empty((array) $command->filter))
        {
            $filter = $command->filter;
            $filterStrings = [];
            foreach ($filter as $filterKey => $filterVal)
                $filterStrings[] = $filterKey;

            $this->queryString .= " FILTER[" . implode(',', $filterStrings) . ']';
        }

        // If a sort is provided, print it
        if (isset($command->sort))
        {
            $sort = $command->sort;
            $sortStrings = [];
            foreach ($sort as $sortKey => $sortVal)
                $sortStrings[] = $sortKey . ($sortVal == 1 ? ' ASC' : ' DESC');

            $this->queryString .= " SORT[" . implode(',', $sortStrings) . ']';
        }

        // If documents are provided, print it
        if (isset($command->documents))
        {
            $documents = $command->documents;
            $documentKeys = [];
            foreach ($documents as $document)
                $documentKeys = array_merge($documentKeys, array_keys((array) $document));

            $this->queryString .= " VALUES[" . implode(',', $documentKeys) . ']';
        }

        // If a deletes is provided, print it
        if (isset($command->deletes))
        {
            $deletes = $command->deletes;
            $deleteKeys = [];
            foreach ($deletes as $delete)
            {
                if (!isset($delete->q))
                    continue;

                $deleteKeys = array_merge($deleteKeys, array_keys((array) $delete->q));
            }

            $this->queryString .= " FILTER[" . implode(',', $deleteKeys) . ']';
        }

        // If a limit is provided, print it
        if (isset($command->limit))
            $this->queryString .= " LIMIT(".$command->limit.")";
    }

    /**
     * Notification method for a successful command.
     * If the subscriber has been registered with MongoDB\Driver\Monitoring\addSubscriber(), the driver will call this method when a command has succeeded.
     * @link https://secure.php.net/manual/en/mongodb-driver-monitoring-commandsubscriber.commandsucceeded.php
     * @param CommandSucceededEvent $event An event object encapsulating information about the successful command.
     * @return void
     * @throws \InvalidArgumentException on argument parsing errors.
     * @since 1.3.0
     */
    public function commandSucceeded(CommandSucceededEvent $event)
    {
        // Get variables
        $queryTimings = microtime(true) - $this->commandTimings;
        $queryString = $this->queryString;
        $queryData = 0;

        switch ($event->getCommandName())
        {
            case 'find':
                $queryData = count($event->getReply()->cursor->firstBatch);
                break;

            case 'update':
            case 'delete':
            case 'insert':
                $queryData = $event->getReply()->n;
                break;

        }

        // And log query
        $this->mongoEngine->logMongoQuery($queryString, $queryData, $queryTimings, []);

        // And reset timings
        $this->commandTimings = 0.0;
    }
}