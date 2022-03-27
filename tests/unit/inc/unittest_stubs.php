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
 * @package E20R\Tests\Unit\Fixtures
 */

namespace E20R\Tests\Unit\Fixtures;

use Brain\Monkey\Functions;
use Exception;
use Mockery;

/**
 * Create stubs for the required WP defined functions
 *
 * @return void
 */
function e20r_unittest_stubs() {
	Functions\when( 'wp_die' )
		->justReturn(
			function( $string ) {
				// phpcs:ignore
				error_log( "Should have died: {$string}" );
			}
		);

	Functions\when( 'esc_attr__' )
		->returnArg( 1 );

	Functions\when( 'esc_html__' )
		->returnArg( 1 );

	Functions\when( '__return_true' )
		->justReturn( true );

	Functions\when( '__return_false' )
		->justReturn( false );

	Functions\when( 'plugin_dir_path' )
		->justReturn( __DIR__ . '/../../../' );

	Functions\when( 'get_current_blog_id' )
		->justReturn( 1 );

	Functions\when( 'date_i18n' )
		->justReturn(
			function( $date_string, $time ) {
				return date( $date_string, $time ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			}
		);

	Functions\expect( 'wp_date' )
		->andReturnUsing(
			function( $date_string, $time ) {
				return date( $date_string, $time ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			}
		);

	Functions\expect( 'plugins_url' )
		->andReturn( 'https://localhost:7254/wp-content/plugins/' );

	try {
		Functions\expect( 'admin_url' )
			->with( Mockery::contains( 'options-general.php' ) )
			->andReturnUsing(
				function() {
					return 'https://localhost:7254/wp-admin/options-general.php';
				}
			);
	} catch ( Exception $e ) {
		echo 'Error: ' . $e->getMessage(); // phpcs:ignore
	}

	try {
		Functions\expect( 'esc_url_raw' )
			->zeroOrMoreTimes()
			->andReturnFirstArg();
	} catch ( Exception $e ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'esc_url_raw() mock error: ' . esc_attr( $e->getMessage() ) );
	}

	Functions\when( '_deprecated_function' )
		->alias(
			function( $depr_function, $wp_version, $new_function ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( "{$depr_function} is deprecated as of WordPress v{$wp_version}. Please use {$new_function} instead" );
			}
		);
}

/**
 * Returns the WP_UPLOAD_DIR structure
 *
 * @return array
 */
function fixture_upload_dir() {
	return array(
		'path'    => __DIR__ . '/../../_output/2021/08/',
		'url'     => 'https://localhost:7254/wp-content/uploads/2021/08/',
		'subdir'  => '2021/08/',
		'basedir' => __DIR__ . '/../../_output/',
		'error'   => false,
	);
}
