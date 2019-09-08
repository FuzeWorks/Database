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
     * Returns the name of the TableModel.
     *
     * Usually 'pdo' or 'mongo'.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Return the name of the engine used by this TableModel.
     *
     * Usually 'pdo' or 'mongo'
     *
     * @return string
     */
    public function getEngineName(): string;

    /**
     * Return the engine used by this TableModel
     *
     * @return iDatabaseEngine
     */
    public function getEngine(): iDatabaseEngine;

    /**
     * Method invoked by FuzeWorks\Database to setup this tableModel.
     *
     * Provides the TableModel with the appropriate iDatabaseEngine and the name of the table.
     *
     * @param iDatabaseEngine $engine
     * @param string $tableName
     * @return mixed
     */
    public function setUp(iDatabaseEngine $engine, string $tableName);

    /**
     * Returns whether the TableModel has been setup yet
     *
     * @return bool
     */
    public function isSetup(): bool;

    /**
     * Creates data in the model.
     *
     * @param array $data
     * @param array $options
     * @return int
     * @throws DatabaseException
     */
    public function create(array $data, array $options = []): int;

    /**
     * Returns data from the model in the form of a TableModelResult
     *
     * @param array $filter
     * @param array $options
     * @return TableModelResult
     * @throws DatabaseException
     * @see TableModelResult
     */
    public function read(array $filter = [], array $options = []): TableModelResult;

    /**
     * Updates data in the model
     *
     * @param array $data
     * @param array $filter
     * @param array $options
     * @return int
     * @throws DatabaseException
     */
    public function update(array $data, array $filter, array $options = []): int;

    /**
     * Deletes data from the model
     *
     * @param array $filter
     * @param array $options
     * @return int
     * @throws DatabaseException
     */
    public function delete(array $filter, array $options = []): int;

    /**
     * Starts a transaction in the model when supported
     *
     * @return bool
     */
    public function transactionStart(): bool;

    /**
     * Ends a transaction in the model when supported
     *
     * @return bool
     */
    public function transactionEnd(): bool;

    /**
     * Commits changes in the model when supported
     *
     * @return bool
     */
    public function transactionCommit(): bool;

    /**
     * Rolls back changes in the modle when supported
     *
     * @return bool
     */
    public function transactionRollback(): bool;
}