<?php
/**
 * FuzeWorks.
 *
 * The FuzeWorks PHP FrameWork
 *
 * Copyright (C) 2015   TechFuze
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    TechFuze
 * @copyright Copyright (c) 2013 - 2017, TechFuze. (http://techfuze.net)
 * @copyright Copyright (c) 1996 - 2015, Free Software Foundation, Inc. (http://www.fsf.org/)
 * @license   http://opensource.org/licenses/GPL-3.0 GPLv3 License
 *
 * @link  http://techfuze.net/fuzeworks
 * @since Version 1.0.4
 *
 * @version Version 1.0.4
 */

namespace FuzeWorks\Event;

use FuzeWorks\DatabaseEngine\iDatabaseEngine;
use FuzeWorks\Event;

/**
 * Event that gets loaded when a database driver is loaded
 *
 * Use this to cancel the loading of a database, or change the provided database
 *
 * @author    Abel Hoogeveen <abel@techfuze.net>
 * @copyright Copyright (c) 2013 - 2017, TechFuze. (http://techfuze.net)
 */
class DatabaseLoadDriverEvent extends Event
{
    /**
     * A possible database that can be loaded. 
     * 
     * Provide a database in this variable and it will be loaded. It shall be identified as default if 
     * the parameters variable is empty. If there is a string in parameters this database shall be identified as
     * such. 
     *
     * @var iDatabaseEngine|null
     */
    public $databaseEngine = null;

    /**
     * The name of the engine to be loaded
     *
     * @var string
     */
    public $engineName;

    /**
     * Parameters of the database to be loaded
     *
     * @var array
     */
    public $parameters;

    /**
     * Database group to load
     *
     * @var bool
     */
    public $connectionName;

    public function init(string $engineName, array $parameters, string $connectionName)
    {
        $this->engineName = $engineName;
        $this->parameters = $parameters;
        $this->connectionName = $connectionName;
    }
}
