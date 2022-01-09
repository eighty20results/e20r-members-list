<?php
/**
 * Copyright (c) 2016 - 2022 - Eighty / 20 Results by Wicked Strong Chicks.
 * ALL RIGHTS RESERVED
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
 * @package \
 */

use Codeception\Util\Autoload;

Autoload::addNamespace( 'E20R\\Utilities', __DIR__ . '/../src' );

/**
 * The following snippets uses `PLUGIN` to prefix
 * the constants and class names. You should replace
 * it with something that matches your plugin name.
 */
// define test environment.
const PLUGIN_PHPUNIT = true;

if ( ! defined( 'PLUGIN_PATH' ) ) {
	define( 'PLUGIN_PATH', __DIR__ . '/../src/' );
}

require_once __DIR__ . '/../inc/autoload.php';

// Load the class autoloader.
require_once __DIR__ . '/../class-e20r-members-list.php';
