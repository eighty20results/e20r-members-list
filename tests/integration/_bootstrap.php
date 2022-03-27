<?php
/**
 *  Copyright (c) 2021 - 2022. - Eighty / 20 Results by Wicked Strong Chicks.
 *  ALL RIGHTS RESERVED
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  You can contact us at mailto:info@eighty20results.com
 *
 * @package \
 */

// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
error_log( 'Loading fixture definitions for integration tests' );

if ( file_exists( __DIR__ . '/inc/fixture_insert_test_data.php' ) ) {
	require_once __DIR__ . '/inc/fixture_insert_test_data.php';
}

if ( file_exists( __DIR__ . '/inc/fixture_clear_test_data.php' ) ) {
	require_once __DIR__ . '/inc/fixture_clear_test_data.php';
}

// PMPro isn't very defensively coded and
if ( ! defined( 'AUTH_KEY' ) ) {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( 'Defining AUTH_KEY' );
	define( 'AUTH_KEY', rand_str( 32 ) );
}

// pmpro_next_payment() assumes AUTH_KEY and SECURE_AUTH_KEY will always be defined
if ( ! defined( 'SECURE_AUTH_KEY' ) ) {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( 'Defining SECURE_AUTH_KEY' );
	define( 'SECURE_AUTH_KEY', rand_str( 32 ) );
}
