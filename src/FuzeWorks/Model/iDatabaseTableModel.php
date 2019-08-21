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


use FuzeWorks\DatabaseEngine\iDatabaseEngine;
use FuzeWorks\Exception\DatabaseException;

interface iDatabaseTableModel
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getEngineName(): string;

    public function setUp(iDatabaseEngine $engine, string $tableName);

    public function isSetup(): bool;

    /**
     * @param array $data
     * @param array $options
     * @param string $table
     * @return int
     * @throws DatabaseException
     */
    public function create(array $data, array $options = [], string $table = 'default'): int;

    /**
     * @param array $filter
     * @param array $options
     * @param string $table
     * @return array
     * @throws DatabaseException
     */
    public function read(array $filter = [], array $options = [], string $table = 'default'): array;

    /**
     * @param array $data
     * @param array $filter
     * @param array $options
     * @param string $table
     * @return int
     * @throws DatabaseException
     */
    public function update(array $data, array $filter, array $options = [], string $table = 'default'): int;

    /**
     * @param array $filter
     * @param array $options
     * @param string $table
     * @return int
     * @throws DatabaseException
     */
    public function delete(array $filter, array $options = [], string $table = 'default'): int;

    /**
     * @return bool
     */
    public function transactionStart(): bool;

    /**
     * @return bool
     */
    public function transactionEnd(): bool;

    /**
     * @return bool
     */
    public function transactionCommit(): bool;

    /**
     * @return bool
     */
    public function transactionRollback(): bool;
}