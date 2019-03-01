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
use Tracy\IBarPanel;
use Tracy\Debugger;
use Tracy\Dumper;

/**
 * DatabaseTracyBridge Class.
 *
 * This class provides a bridge between FuzeWorks\Database and Tracy Debugging tool.
 * 
 * This class registers in Tracy, and creates a Bar object which contains information about database sessions. 
 * It hooks into database usage and provides the information on the Tracy Bar panel. 
 *
 * @author    Abel Hoogeveen <abel@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
class DatabaseTracyBridge implements IBarPanel
{

    /**
     * @var iDatabaseEngine[]
     */
	public static $databases = array();

	protected $results = array();

	public static function register()
	{
		$class = new self();
		$bar = Debugger::getBar();
		$bar->addPanel($class);
	}

	public static function registerDatabase(iDatabaseEngine $database)
	{
		self::$databases[] = $database;
	}

	protected function getResults(): array
	{
		if (!empty($this->results))
		{
			return $this->results;
		}

		// First prepare global variables
		$results = array();
		$results['dbCount'] = 0;
		$results['queryCount'] = 0;
		$results['queryTimings'] = 0.0;
		$results['errorsFound'] = false;

		// Go through all databases
		foreach (self::$databases as $database) {
			// Increase total databases
			$results['dbCount']++;

            // First determine the ID
			$databaseId = $database->getConnectionDescription();

			// Go through all queries
            if (!method_exists($database, 'getQueries'))
            {
                continue;
            }

            foreach ($database->getQueries() as $query)
            {
                $results['queryTimings'] += $query['queryTimings'];
                $key = $query['queryString'];
                if (!isset($results['queries'][$databaseId][$key]))
                    $results['queryCount']++;

                $results['queries'][$databaseId][$key]['query'] = $query['queryString'];
                $results['queries'][$databaseId][$key]['timings'] = $query['queryTimings'];
                $results['queries'][$databaseId][$key]['errors'] = $query['queryError'];

                if (!isset($results['queries'][$databaseId][$key]['data']))
                    $results['queries'][$databaseId][$key]['data'] = $query['queryData'];
                else
                    $results['queries'][$databaseId][$key]['data'] += $query['queryData'];

                if (!empty($query['queryError']))
                    $results['errorsFound'] = true;
            }
		}

		// Limit the amount in order to keep things readable
		$results['queryCountProvided'] = 0;
		if (isset($results['queries']))
        {
            foreach ($results['queries'] as $id => $database) {
                $results['queries'][$id] = array_reverse(array_slice($database, -10));
                $results['queryCountProvided'] += count($results['queries'][$id]);
            }
            $results = array_slice($results, -10);
        }

		//dump($results['queries']['mysql:host=localhost;dbname=hello']);

		return $this->results = $results;
	}

	public function getTab(): string
	{
		$results = $this->getResults();
		ob_start(function () {});
		require dirname(__DIR__) . DS . 'Layout' . DS . 'layout.tracydatabasetab.php';
		return ob_get_clean();
	}

	public function getPanel(): string
	{
		// Parse the panel
		$results = $this->getResults();
		ob_start(function () {});
		require dirname(__DIR__) . DS . 'Layout' . DS . 'layout.tracydatabasepanel.php';
		return ob_get_clean();
	}
}