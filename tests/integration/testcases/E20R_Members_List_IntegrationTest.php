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
 * @package E20R\Tests\Integration\E20R_Members_List_IntegrationTest
 */

namespace E20R\Tests\Integration;

use Codeception\TestCase\WPTestCase;

use E20R\Members_List\Admin\Exceptions\MissingUtilitiesModule;
use E20R\Members_List\Admin\Pages\Members_List_Page;
use E20R\Members_List\E20R_Members_List;
use E20R\Members_List\Members_List;
use E20R\Utilities\Message;
use E20R\Utilities\Utilities;

/**
 * Test class for the E20R_Members_List
 */
class E20R_Members_List_IntegrationTest extends WPTestCase {

	/**
	 * Mock instance for Members_List() class
	 *
	 * @var Members_List|null $mock_mc_class
	 */
	private $members_list;

	/**
	 * Mock instance of the Members_List_Page class
	 *
	 * @var Members_List_Page|null $mock_mlp_class
	 */
	private $mlp_class = null;

	/**
	 * Mock instance of the Utilities class
	 *
	 * @var null|Utilities $utils
	 */
	private $utils = null;
	/**
	 * SetUp test environment
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['hook_suffix'] = 'pmpro_membership';
		$message                = new Message();
		$this->utils            = new Utilities( $message );
		$this->mlp_class        = new Members_List_Page( $this->utils );
	}

	/**
	 * Teardown which calls \WP_Mock tearDown
	 *
	 * @return void
	 */
	public function tearDown(): void { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		parent::tearDown();
	}

	/**
	 * Tests that the get_instance() function returns the expected class
	 *
	 * @test
	 */
	public function it_should_return_the_instance() {
		self::assertInstanceOf( '\\E20R\\Members_List\\E20R_Members_List', new E20R_Members_List( $this->mlp_class, $this->utils ) );
	}

	/**
	 * Tests that the expected hooks to run have been loaded
	 *
	 * @test
	 */
	public function it_should_load_hooks_and_actions() {

		// Load the class and hooks (make sure the hooks we expect are loaded).
		$class = new E20R_Members_List( $this->mlp_class, $this->utils );
		try {
			$class->load_hooks();
		} catch ( MissingUtilitiesModule $e ) {
			self::assertFalse( true, 'Error: The E20R Utilities Module is missing!' );
		}

		self::assertSame( 1, has_action( 'init', array( $class, 'load_text_domain' ) ) );
		self::assertSame( 10, has_action( 'wp_loaded', array( $class->get_page(), 'load_hooks' ) ) );

		self::assertSame( 99999, has_action( 'pmpro_after_change_membership_level', array( $class, 'attempt_clear_cache' ) ) );
		self::assertSame( 99999, has_action( 'deleted_user', array( $class, 'attempt_clear_cache' ) ) );
		self::assertSame( 99999, has_action( 'profile_update', array( $class, 'attempt_clear_cache' ) ) );
		self::assertSame( 99999, has_action( 'edit_user_profile_update', array( $class, 'attempt_clear_cache' ) ) );

		self::assertSame( 10, has_filter( 'plugin_row_meta', array( $class, 'plugin_row_meta' ) ) );
	}
}
