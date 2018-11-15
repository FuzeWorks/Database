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

use FuzeWorks\Logger;
use Fuzeworks\Factory;
use FuzeWorks\Core;

/**
 * Database Cache Class
 *
 * Converted from CodeIgniter.
 *
 * @package		FuzeWorks
 * @category	Database
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/user_guide/database/
 * @license		http://opensource.org/licenses/MIT	MIT License
 */
class FW_DB_Cache {

	/**
	 * Database object
	 *
	 * Allows passing of DB object so that multiple database connections
	 * and returned DB objects can be supported.
	 *
	 * @var	object
	 */
	public $db;

	/**
	 * The FuzeWorks factory class
	 * 
	 * @var Fuzeworks\Factory;
	 */
	private $factory;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @param	object	&$db
	 * @return	void
	 */
	public function __construct(&$db)
	{
		$this->db =& $db;
		$this->factory = Factory::getInstance();
		$this->factory->helpers->load('file');

		$this->check_path();
	}

	// --------------------------------------------------------------------

	/**
	 * Set Cache Directory Path
	 *
	 * @param	string	$path	Path to the cache directory
	 * @return	bool
	 */
	public function check_path($path = '')
	{
		$path = ($path === '' ? Core::$tempDir . DS . 'Database' : $path);

		// Add a trailing slash to the path if needed
		$path = realpath($path)
			? rtrim(realpath($path), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR
			: rtrim($path, '/').'/';

		if ( ! is_dir($path))
		{
			if (!mkdir($path, 0777, false))
			{
				Logger::logDebug('DB cache path error: '.$path);

				// If the path is wrong we'll turn off caching
				return $this->db->cache_off();
			}
		}

		if ( ! Core::isReallyWritable($path))
		{
			Logger::logDebug('DB cache dir not writable: '.$path);

			// If the path is not really writable we'll turn off caching
			return $this->db->cache_off();
		}

		$this->db->cachedir = $path;
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Retrieve a cached query
	 *
	 * The URI being requested will become the name of the cache sub-folder.
	 * An MD5 hash of the SQL statement will become the cache file name.
	 *
	 * @param	string	$sql
	 * @return	string
	 */
	public function read($sql)
	{
		$segment_one = ($this->factory->uri->segment(1) == FALSE) ? 'default' : $this->factory->uri->segment(1);
		$segment_two = ($this->factory->uri->segment(2) == FALSE) ? 'index' : $this->factory->uri->segment(2);
		$filepath = $this->db->cachedir.$segment_one.'+'.$segment_two.'/'.md5($sql);

		if (FALSE === ($cachedata = @file_get_contents($filepath)))
		{
			return FALSE;
		}

		return unserialize($cachedata);
	}

	// --------------------------------------------------------------------

	/**
	 * Write a query to a cache file
	 *
	 * @param	string	$sql
	 * @param	object	$object
	 * @return	bool
	 */
	public function write($sql, $object)
	{
		$segment_one = ($this->factory->uri->segment(1) == FALSE) ? 'default' : $this->factory->uri->segment(1);
		$segment_two = ($this->factory->uri->segment(2) == FALSE) ? 'index' : $this->factory->uri->segment(2);
		$dir_path = $this->db->cachedir.$segment_one.'+'.$segment_two.'/';
		$filename = md5($sql);

		if ( ! is_dir($dir_path) && ! @mkdir($dir_path, 0750))
		{
			return FALSE;
		}

		if (write_file($dir_path.$filename, serialize($object)) === FALSE)
		{
			return FALSE;
		}

		chmod($dir_path.$filename, 0640);
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Delete cache files within a particular directory
	 *
	 * @param	string	$segment_one
	 * @param	string	$segment_two
	 * @return	void
	 */
	public function delete($segment_one = '', $segment_two = '')
	{
		if ($segment_one === '')
		{
			$segment_one  = ($this->factory->uri->segment(1) == FALSE) ? 'default' : $this->factory->uri->segment(1);
		}

		if ($segment_two === '')
		{
			$segment_two = ($this->factory->uri->segment(2) == FALSE) ? 'index' : $this->factory->uri->segment(2);
		}

		$dir_path = $this->db->cachedir.$segment_one.'+'.$segment_two.'/';
		delete_files($dir_path, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete all existing cache files
	 *
	 * @return	void
	 */
	public function delete_all()
	{
		delete_files($this->db->cachedir, TRUE, TRUE);
	}

}