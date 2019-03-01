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
use PDOStatement;

class PDOStatementWrapper
{

    /**
     * @var PDOStatement
     */
    private $statement;

    /**
     * Callable for logging queries and errors
     *
     * @var callable
     */
    private $logQueryCallable;

    public function __construct(PDOStatement $statement, callable $logQueryCallable)
    {
        $this->statement = $statement;
        $this->logQueryCallable = $logQueryCallable;
    }

    public function execute(array $input_parameters = [])
    {
        // Run the query and benchmark the time
        $benchmarkStart = microtime(true);
        $result = $this->statement->execute($input_parameters);
        $benchmarkEnd = microtime(true) - $benchmarkStart;
        $errInfo = $this->error();
        call_user_func_array($this->logQueryCallable, [$this->statement->queryString, $this->statement->rowCount(), $benchmarkEnd, $errInfo]);

        // If the query failed, throw an error
        if ($result === false)
        {
            // And throw an exception
            throw new DatabaseException("Could not run query. Database returned an error. Error code: " . $errInfo['code']);
        }

        return $result;
    }

    /**
     * Generates an error message for the last failure in PDO
     *
     * @return array
     */
    private function error(): array
    {
        $error = [];
        $pdoError = $this->statement->errorInfo();
        if (empty($pdoError[0]) || $pdoError[0] == '00000')
            return $error;

        $error['code'] = isset($pdoError[1]) ? $pdoError[0] . '/' . $pdoError[1] : $pdoError[0];
        if (isset($pdoError[2]))
            $error['message'] = $pdoError[2];

        return $error;
    }

    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->statement, $method], $parameters);
    }

    public function __get($name)
    {
        return $this->statement->$name;
    }

}