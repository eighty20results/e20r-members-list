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

namespace E20R\Members_List\Controller;

use E20R\Members_List\Admin\Members_List;
use Codeception\Test\Unit;
use Brain\Monkey;
use Spatie\Snapshots\MatchesSnapshots;

class E20R_Members_ListTest extends Unit {
	use MatchesSnapshots;

	public function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// A few common passthroughs
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

		$GLOBALS['hook_suffix'] = 'page';
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

	/**
	 * Tests that the get_instance() function returns the expected class
	 */
	public function test_get_instance() {
		self::assertInstanceOf( '\\E20R\Members_List\\Controller\\E20R_Members_List', E20R_Members_List::get_instance() );
	}

	/**
	 * Tests that the expected hooks to run have been loaded
	 * @test
	 */
	public function test_load_hooks() {

		Monkey\Actions\expectAdded( 'init' )
			->with( array( Members_List::get_instance(), 'load_hooks' ), -1 );
		Monkey\Actions\expectAdded( 'init' )
			->with( array( E20R_Members_List::get_instance(), 'load_text_domain' ), 1 );
		Monkey\Actions\expectAdded( 'e20r_memberslist_process_action' )
			->with( array( Members_List::get_instance(), 'export_members' ), 10, 3 );

		// Load the class and hooks (make sure the hooks we expect are loaded
		E20R_Members_List::get_instance()->load_hooks();
	}

	/**
	 * Test the happy path for the autoloader
	 *
	 * @param string $class_name
	 * @param bool $expected
	 *
	 * @dataProvider fixture_good_class_list
	 */
	public function test_auto_loader_success( $class_name, $expected, $message ) {

		$result = E20R_Members_List::auto_loader( $class_name );
		self::assertEquals( $expected, $result, $message );
	}

	/**
	 * Fixture for the auto_loader success tests
	 *
	 * @return array[]
	 */
	public function fixture_good_class_list() {
		return array(
			array( 'E20R\Members_List\Admin\Export_Members', true, 'The Export_Members class is NOT present in the plugin directory?!?' ),
			array( 'E20R\Members_List\Admin\Members_List_Page', true, 'The Members_List_Page class is NOT present in the plugin directory?!?' ),
			array( 'E20R\Members_List\Support\Sort_By_Meta', true, 'The Sort_By_Meta class is NOT present in the plugin directory?!?' ),
		);
	}

	/**
	 * Test a failure path for the autoloader
	 *
	 * @param string $class_name
	 * @param bool $expected
	 *
	 * @dataProvider fixture_missing_class_list
	 */
	public function test_auto_loader_error_returns( $class_name, $expected, $message ) {

		$result = E20R_Members_List::auto_loader( $class_name );
		self::assertEquals( $expected, $result, $message );
	}

	/**
	 * Fixture for the auto loader failure tests
	 *
	 * @return array[]
	 */
	public function fixture_missing_class_list() {
		return array(
			array( 'E20R\Members_List\Controller\E20R_Members_List', false, 'Valid class, but was unexpectedly allowed to load though trying to (re)load' ),
			array( 'TLS\Members_List\Controller\E20R_Members_List', false, 'Valid class, and unexpectedly in the right namespace (Should be: TLS\\...)' ),
			array( 'TLS_Does_Not_Exists', false, 'Had the expected namespace prefix ("E20R" - No prefix was specified!)' ),
			array( 'E20R\\Members_List\\Admin\\My_E20R_Members_List', false, 'My_E20R_Members_List is _NOT_ a valid class in this plugin!' ),
			array( 'E20\Members_List\Admin\Members_List_Page', false, 'For some reason the typo in the E20R prefix was ignored' ), // Typo
		);
	}
}
