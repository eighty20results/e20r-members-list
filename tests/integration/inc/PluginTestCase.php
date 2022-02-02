<?php
/*
 *  Copyright (c) 2021. - Eighty / 20 Results by Wicked Strong Chicks.
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
 */

use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;

class PluginTestCase extends TestCase {
	use MatchesSnapshots;
	use MockeryPHPUnitIntegration;

	/**
	 * Setup which calls \WP_Mock setup
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		// A few common passthrough
		// 1. WordPress i18n functions
		Monkey\Functions\when( '__' )
			->returnArg( 1 );
		Monkey\Functions\when( '_e' )
			->returnArg( 1 );
		Monkey\Functions\when( '_n' )
			->returnArg( 1 );
		Monkey\Functions\when( 'plugins_url' )
			->justReturn( sprintf( 'https://development.local/wp-content/plugins/' ) );
		Monkey\Functions\when( 'plugin_dir_path' )
			->justReturn( sprintf( '/var/www/html/wp-content/plugins/e20r-members-list/' ) );
		Monkey\Functions\when( 'get_current_blog_id' )
			->justReturn( 1 );
	}

	/**
	 * Teardown which calls \WP_Mock tearDown
	 *
	 * @return void
	 */
	public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
