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

use FuzeWorks\DatabaseComponent;
use FuzeWorks\TracyComponent;

require_once(dirname(__DIR__) . '/vendor/autoload.php');

$configurator = new FuzeWorks\Configurator();

// Implement all directories
$configurator->setTempDirectory(dirname(__FILE__) . '/temp');
$configurator->setLogDirectory(dirname(__FILE__) . '/temp');

// Other values
$configurator->setTimeZone('Europe/Amsterdam');
$configurator->enableDebugMode(true)->setDebugAddress('ALL');

// Implement the Layout Component
$configurator->addComponent(new DatabaseComponent());
$configurator->addComponent(new TracyComponent());

// Create container
$container = $configurator->createContainer();

// And return the result
return $container;

