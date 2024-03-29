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

use E20R\Members_List\Admin\Exceptions\InvalidProperty;
use E20R\Members_List\Admin\Exceptions\PMProNotActive;
use E20R\Members_List\Admin\Bulk\Bulk_Cancel;
use E20R\Utilities\Utilities;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Codeception\Test\Unit;

use Exception;

/**
 * Unit testing of the Bulk_Cancel() class
 */
class Bulk_Cancel_UnitTest extends Unit {

	use MockeryPHPUnitIntegration;

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
		Monkey\setUp();

		$this->loadStubs();
		$this->loadMocks();
	}

	/**
	 * Tear-down method for the Bulk_Cancel_UnitTest() class
	 *
	 * @return void
	 */
	public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Loads stubbed WP functions we'll need
	 *
	 * @return void
	 */
	private function loadStubs() {
		Functions\when( 'esc_attr__' )
			->returnArg( 1 );
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
	 * Unit test to validate that PMProNotActive exception is thrown
	 *
	 * @param bool $pmpro_present Whether we will behave as if PMPro is installed and active
	 * @param bool $cancel_result The result we'll return from the stub'ed pmpro_cancelM
	 * @param bool $expected_result The result from running the execute() method against the mock data
	 *
	 * @return void
	 * @throws InvalidProperty Thrown if attempting to access an invalid class property
	 *
	 * @dataProvider fixture_pmpro_not_active
	 * @test
	 */
	public function it_should_throw_pmpro_not_active_exception( $pmpro_present, $cancel_result, $expected_result ) {
		Functions\stubs(
			array(
				'function_exists'             => function( $name ) use ( $pmpro_present ) {
					switch ( $name ) {
						case 'pmpro_setMessage':
						case 'pmpro_cancelMembershipLevel':
							$this->m_utils->log( "Is {$name}() available? Returning " . ( $pmpro_present ? 'Yes' : 'No' ) );
							return $pmpro_present;
						default:
							$this->m_utils->log( 'Returning default of TRUE for ' . $name );
							return true;
					}
				},
				'pmpro_cancelMembershipLevel' => function( $level_id, $user_id, $level_status ) use ( $cancel_result ) {
					$this->m_utils->log( "Canceling membership level {$level_id} for user {$user_id} and setting status => {$level_status}" );
					return $cancel_result;
				},
			)
		);

		$bc     = new Bulk_Cancel( null, $this->m_utils );
		$result = $bc->execute();
		self::assertSame( $expected_result, $result );
	}

	/**
	 * Fixture for the it_should_throw_pmpro_not_active_exception test
	 *
	 * @return array
	 */
	public function fixture_pmpro_not_active() {
		return array(
			array( false, null, false ),
		);
	}
	/**
	 * Happy Path unit test for the Bulk_Cancel::cancel() method
	 *
	 * @param array $members_to_cancel The list of members to "cancel"
	 * @param bool  $pmpro_present What the 'function_exists()' function is supposed to return
	 * @param bool  $cancel_returns What the pmpro_cnacelMembershipLevel() function is supposed to return
	 * @param int   $failed_count The expected count of failed cancellations from the operation
	 * @param mixed $expected The expected outcome from running the cancel operation
	 *
	 * @dataProvider fixture_generates_users_to_bulk_cancel
	 * @return void
	 * @test
	 * @throws InvalidProperty Thrown if the get/set operation is invalid
	 */
	public function it_performs_the_bulk_cancel_operation( $members_to_cancel, $pmpro_present, $cancel_returns, $failed_count, $expected ) {
		if ( true === $pmpro_present ) {
			Functions\stubs(
				array(
					'pmpro_setMessage' => function( $msg, $prio ) {
						$this->m_utils->add_message( $msg, $prio, 'backend' );
					},
				)
			);
		}

		Functions\stubs(
			array(
				'function_exists' => function( $name ) use ( $pmpro_present ) {
					switch ( $name ) {
						case 'pmpro_setMessage':
						case 'pmpro_cancelMembershipLevel':
							return $pmpro_present;
						default:
							return true;
					}
				},
			)
		);

		$m_bc = $this->construct(
			Bulk_Cancel::class,
			array( $members_to_cancel, $this->m_utils ),
			array( 'cancel_member' => $cancel_returns )
		);

		$m_bc->set( 'members_to_update', $members_to_cancel );

		$result = $m_bc->execute();
		$failed = $m_bc->get( 'failed' );
		$count  = count( $failed );

		self::assertSame( $failed_count, $count, "Error comparing '{$failed_count}' to '{$count}'" );
		self::assertSame( $expected, $result );
	}

	/**
	 * Generate user IDs and level IDs to process during tests
	 *
	 * @return int[][]
	 */
	private function fixture_generate_user_level_array() {
		$members_to_cancel = array();

		// Build user IDs with level(s)
		foreach ( range( 1, 5, -1 ) as $level_id ) {
			foreach ( range( ( 65535 - ( 10 * $level_id ) ), ( 65530 - ( 10 * $level_id ) ), -1 ) as $user_id ) {
				$user_info = array(
					'user_id'  => $user_id,
					'level_id' => $level_id,
				);

				$members_to_cancel[] = $user_info;
			}
		}

		return $members_to_cancel;
	}
	/**
	 * Fixture for the 'it_performs_the_bulk_cancel_operation' unit test method
	 *
	 * @return array
	 */
	public function fixture_generates_users_to_bulk_cancel() {
		$members = $this->fixture_generate_user_level_array();
		return array(
			// members to process, is pmpro present, pmpro_cancelMembershipLevel returns, failed count, expected result from execute()
			array( $members, true, true, 0, true ),
			array( $members, true, false, 30, false ),
			array( $members, false, false, 0, false ),
		);
	}

	/**
	 * Unit test for the Bulk_Cancel::cancel_member() method
	 *
	 * @param int  $user_id The WP_User ID for the member we're affecting
	 * @param int  $level_id The id for the membership level as represented in the PMPro pmpro_memberships_levels table
	 * @param bool $function_exists What the mocked 'function_exists()' function should return
	 * @param bool $pmp_returns The result we should return from the mocked 'pmpro_cancelMembershipLevel' function
	 * @param bool $expected The expected return value for the (mocked)
	 *
	 * @return void
	 *
	 * @dataProvider fixture_user_to_cancel
	 * @test
	 * @throws InvalidProperty Thrown if the get/set operation is invalid
	 */
	public function it_tries_to_cancel_the_membership_for_the_specified_wpuser_id( $user_id, $level_id, $function_exists, $pmp_returns, $expected ) {

		if ( true === $function_exists ) {
			Functions\stubs(
				array(
					'pmpro_cancelMembershipLevel' =>
						function( $level_id, $user_id, $status ) use ( $pmp_returns ) {
							$this->m_utils->log( "pmpro_cancelMembershipLevel({$level_id}, {$user_id}, {$status})" );
							return $pmp_returns;
						},
				)
			);
		}

		Functions\stubs(
			array(
				'do_action'       => null,
				'function_exists' => function( $name ) use ( $function_exists ) {
					if ( 'pmpro_cancelMembershipLevel' === $name ) {
						$this->m_utils->log( 'function_exists( "pmpro_cancelMembershipLevel" ) -> ' . ( $function_exists ? 'Yes' : 'No' ) );
						if ( false === $function_exists ) {
							$this->expectException( PMProNotActive::class );
						}
						return $function_exists;
					}

					return true;
				},
			)
		);

		$bc     = new Bulk_Cancel( null, $this->m_utils );
		$result = $bc->cancel_member( $user_id, $level_id );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		self::assertSame( $expected, $result, 'Error: Unexpected value returned: ' . print_r( $result, true ) );

	}

	/**
	 * Fixture for the it_cancels_the_membership_for_the_specific_wpuser_id unit test
	 *
	 * @return array[]
	 */
	public function fixture_user_to_cancel() {
		return array(
			// user Id, Level Id, function_exists, cancel_returns, expected result
			array( 1234567, 123456, false, null, null ),
			array( 1234567, 123456, true, null, null ),
			array( 1, 1, true, true, true ),
			array( 1, 1, false, false, false ),
		);
	}

	/**
	 * Bulk_Cancel() class instantiation unit test
	 *
	 * @param array          $members_to_update The list of member info to use for cancellation operation
	 * @param Utilities|null $utils_class The E20R Utilities module class (or mocked class)
	 * @param mixed          $expected_class The expected class returned
	 * @param int[]          $expected_to_update The expected response
	 *
	 * @return void
	 *
	 * @test
	 * @dataProvider fixture_instantiated_values
	 */
	public function it_should_instantiate_the_class( $members_to_update, $utils_class, $expected_class, $expected_to_update ) {
		$bc = new Bulk_Cancel( $members_to_update, $utils_class );

		if ( null !== $utils_class ) {
			self::assertInstanceOf( $expected_class, $bc->get( 'utils' ) );
		}

		self::assertIsArray( $bc->get( 'members_to_update' ) );
		self::assertSameSize( $members_to_update, $expected_to_update );
		self::assertSameSize( $members_to_update, $bc->get( 'members_to_update' ) );
		self::assertSame( 'cancel', $bc->get( 'operation' ) );
	}

	/**
	 * Fixture for the it_should_instanitate_the_class
	 *
	 * @return array[]
	 */
	public function fixture_instantiated_values() {
		$utils   = new Utilities();
		$members = $this->fixture_generate_user_level_array();
		return array(
			array( $members, null, Utilities::class, $members ),
			array( $members, $utils, Utilities::class, $members ),
			array( $members, null, Utilities::class, $members ),
		);
	}
}
