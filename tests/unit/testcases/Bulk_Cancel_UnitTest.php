<?php
/**
 *
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
 * @package E20R\Tests\Unit\Bulk_Cancel_UnitTest
 */

namespace E20R\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Codeception\AssertThrows;
use Codeception\Test\Unit;
use E20R\Tests\Unit\Fixtures;

use E20R\Members_List\Admin\Bulk\Bulk_Cancel;
use E20R\Utilities\Utilities;
use Exception;

/**
 * Unit testing of the Bulk_Cancel() class
 */
class Bulk_Cancel_UnitTest extends Unit {

	/**
	 * The Mocked Utilities class
	 *
	 * @var null|Utilities $m_utils
	 */
	private $m_utils = null;

	/**
	 * Configure/set up for the unit test(s)
	 *
	 * @return void
	 * @throws Exception Raised by the parent::setUp() method
	 */
	public function setUp(): void {
		parent::setUp();

		$this->loadStubs();
		$this->loadMocks();
	}

	/**
	 * Loads stubbed WP functions we'll need
	 *
	 * @return void
	 */
	private function loadStubs() {
		Fixtures\e20r_unittest_stubs();
	}

	/**
	 * Mock needed 3rd party classes
	 *
	 * @return void
	 * @throws Exception Raised by the makeEmpty() mocker
	 */
	private function loadMocks() {
		$this->m_utils = self::makeEmpty(
			Utilities::class,
			array(
				'log'         => function( $msg ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( 'Mocked log: ' . $msg );
				},
				'add_message' => function( $msg, $severity, $location ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( "Added a {$severity} UI message to {$location}: {$msg}" );
				},
			)
		);
	}
	/**
	 * Happy Path unit test for the Bulk_Cancel::cancel() method
	 *
	 * @dataProvider fixture_generates_users_to_bulk_cancel
	 * @return void
	 * @test
	 */
	public function it_performs_the_bulk_cancel_operation() {

	}

	/**
	 * Fixture for the 'it_performs_the_bulk_cancel_operation' unit test method
	 *
	 * @return array
	 */
	public function fixture_generates_users_to_bulk_cancel() {
		return array();
	}

	/**
	 * Happy Path unit test for the Bulk_Cancel::cancel_member() method
	 *
	 * @param int  $user_id The WP_User ID for the member we're affecting
	 * @param int  $level_id The id for the membership level as represented in the PMPro pmpro_memberships_levels table
	 * @param bool $pmp_returns The result we should return from the mocked 'pmpro_cancelMembershipLevel' function
	 * @param bool $function_exists What the mocked 'function_exists()' function should return
	 * @param bool $expected The expected return value for the (mocked)
	 * @return void
	 *
	 * @dataProvider fixture_user_to_cancel
	 * @test
	 */
	public function it_cancels_the_membership_for_the_specified_wpuser_id( $user_id, $level_id, $pmp_returns, $function_exists, $expected ) {

		Functions\when( 'function_exists' )
			->justReturn( $function_exists );

		if ( true === $function_exists ) {
			Functions\expect( 'pmpro_cancelMembershipLevel' )
				->atLeast()
				->once()
				->andReturn( $pmp_returns );
		}

		$bc     = new Bulk_Cancel( null, $this->m_utils );
		$result = $bc->cancel_member( $user_id, $level_id );
		self::assertSame( $expected, $result );
	}

	/**
	 * Fixture for the it_cancels_the_membership_for_the_specific_wpuser_id unit test
	 *
	 * @return array[]
	 */
	public function fixture_user_to_cancel() {
		return array(
			// user Id, Level Id, cancel_returns, function_exists, expected result
			array( 1, 1, true, true, true ),
			array( 1234567, 123456, null, false, false ),
		);
	}
}
