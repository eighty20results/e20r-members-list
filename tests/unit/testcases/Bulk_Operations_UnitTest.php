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
use E20R\Members_List\Admin\Exceptions\InvalidProperty;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Codeception\Test\Unit;
use E20R\Members_List\Admin\Exceptions\PMProNotActive;
use E20R\Tests\Unit\Fixtures;

use E20R\Members_List\Admin\Bulk\Bulk_Operations;
use E20R\Utilities\Utilities;
use Exception;

/**
 * Unit testing of the Bulk_Operations() class
 */
class Bulk_Operations_UnitTest extends Unit {

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
	 * Unit test for the Bulk_Operations::set() method
	 *
	 * @param string      $parameter The parameter to set
	 * @param mixed       $value The value to set the parameter to
	 * @param null|string $expected The returned value from the set() operation, or the exception class name
	 *
	 * @dataProvider fixture_set_values
	 * @return void
	 * @test
	 * @throws InvalidProperty Thrown if the get/set operation is invalid
	 */
	public function it_sets_the_parameter( $parameter, $value, $expected ) {

		$m_bo = $this->constructEmptyExcept(
			Bulk_Operations::class,
			'set',
			array( $this->m_utils )
		);

		if ( null !== $expected ) {
			$this->expectException( $expected );
		}

		$result = $m_bo->set( $parameter, $value );
		self::assertSame( $expected, $result );
	}

	/**
	 * Fixture for the is_sets_the_parameter() test method
	 *
	 * @return array[]
	 */
	public function fixture_set_values() {
		return array(
			array( 'operation', 'testing', null ), // #0
			array( 'failed', array(), null ), // #1
			array( 'members_to_update', array(), null ), // #2
			array( 'utils', $this->m_utils, null ), // #3
			array( 'dummy', null, InvalidProperty::class ), // #4
			array( ' operation', null, InvalidProperty::class ), // #5
			array( ' fail', null, InvalidProperty::class ), // #6
			array( 'members-to-update', null, InvalidProperty::class ), // #7
			array( 'operation ', null, InvalidProperty::class ), // #8
			array( 'oper ation', null, InvalidProperty::class ), // #8
		);
	}

	/**
	 * Happy Path unit test for the Bulk_Cancel::cancel() method
	 *
	 * @param string      $parameter The parameter to get
	 * @param mixed|null  $value The value to set for the parameter (if the parameter exists)
	 * @param mixed       $expected The return value we expect from the get() method
	 * @param null|string $exception The expected exception class name
	 *
	 * @dataProvider fixture_get_values
	 * @return void
	 * @test
	 * @throws InvalidProperty Thrown if the get/set operation is invalid
	 */
	public function it_gets_the_parameter( string $parameter, $value, $expected, ?string $exception ) {

		if ( false !== $value ) {
			$m_bo = $this->make(
				Bulk_Operations::class,
				array(
					'utils'    => $this->m_utils,
					$parameter => $value,
				),
			);
		} else {
			$this->m_utils->log( "Not setting the '{$parameter}' value to anything" );
			$m_bo = $this->make(
				Bulk_Operations::class,
				array(
					'utils' => $this->m_utils,
				),
			);
		}

		if ( null !== $exception ) {
			$this->expectException( $exception );
		}

		$result = $m_bo->get( $parameter );
		self::assertSame( $expected, $result );
	}

	/**
	 * Fixture for the Bulk_Operations::get() test(s)
	 *
	 * @return array['parameter_name', 'expected_default_value', 'set_value', 'expected_value', 'exception_if_thrown' ]
	 */
	public function fixture_get_values() {
		return array(
			// parameter_name, set_value, expected_value, exception_if_thrown
			array(
				'members_to_update',
				array(
					array(
						'user_id'  => 1,
						'level_id' => 1,
					),
				),
				array(
					array(
						'user_id'  => 1,
						'level_id' => 1,
					),
				),
				null,
			), // # 0
			array( 'operation', 'unit-test', 'unit-test', null ), // # 1
			array( 'members_to_update', false, array(), null ), // #2 -> Tests the default value
			array( 'failed', false, null, null ), // #3 -> Tests the default value
			array( 'nothing_useful', false, null, InvalidProperty::class ), // #4
			array( 'should raise exception', false, null, InvalidProperty::class ), // #4

		);
	}
}
