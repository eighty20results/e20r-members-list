<?php

namespace E20R\Tests\Unit;

use Codeception\Test\Unit;
use E20R\Members_List\Admin\Exceptions\InvalidMemberList;
use E20R\Members_List\Admin\Exceptions\InvalidProperty;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;
use Brain\Monkey\Functions;

use E20R\Members_List\Admin\Bulk\Bulk_Update;
use E20R\Utilities\Utilities;
use Exception;


/**
 * Unit tests for the Bulk_Update() class
 */
class Bulk_Update_UnitTest extends Unit {
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
	 * Test instantiation of the Bulk_Update() class
	 *
	 * @param array          $member_list The list of members to add to the instantiated class instance
	 * @param Utilities|null $utils E20R Utilities module instance (or mocked class instance)
	 * @param array          $expected_member_list List of member IDs we've added (and returned) from the instantiated class)
	 * @param string         $expected_operation The operation descriptor (should be 'update')
	 * @return void
	 *
	 * @dataProvider fixture_instantiate_bulk_update
	 * @test
	 */
	public function it_should_instantiate_the_bulk_update_class( $member_list, $utils, $expected_member_list, $expected_operation ) {

		$bu = new Bulk_Update( $member_list, $utils );
		if ( null !== $utils ) {
			$expected_utils_class = get_class( $utils );
			self::assertInstanceOf( $expected_utils_class, $bu->get( 'utils' ) );
		} else {
			$this->m_utils->log( "Don't know why, but we don't have an object for the utilities instance???" );
		}
		self::assertIsArray( $bu->get( 'members_to_update' ) );
		self::assertSame( $expected_operation, $bu->get( 'operation' ) );

	}

	/**
	 * Fixture for the it_should_instantiate_the_bulk_update_class test method
	 *
	 * @return array[]
	 */
	public function fixture_instantiate_bulk_update() {
		$member_ids = $this->fixture_user_level_list( 5, 100 );
		return array(
			array( $member_ids, $this->m_utils, $member_ids, 'update' ),
			array( $member_ids, new Utilities(), $member_ids, 'update' ),
		);
	}

	/**
	 * Unit test to validate that we correctly handle the get() method against valid and invalid properties
	 *
	 * @param string $property_name The name of the property to attempt to fetch/return the value of
	 * @param mixed  $expected      The returned value from the property we've requested (assuming 'false' here means we
	 *                              expect an exception)
	 *
	 * @return void
	 *
	 * @dataProvider fixture_property_tests
	 * @test
	 */
	public function it_should_get_property_values_and_return_expected_values_or_errors( $property_name, $expected ) {
		$members = null;

		if ( false === $expected ) {
			if ( 'members_to_update' === $property_name ) {
				$this->expectException( InvalidMemberList::class );
			} else {
				$this->expectException( InvalidProperty::class );
			}
		} else {
			if ( 'members_to_update' === $property_name ) {
				$members = $expected;
			}
		}

		$b_update = new Bulk_Update( $members, $this->m_utils );
		$result   = $b_update->get( $property_name );

		if ( false !== $expected ) {
			self::assertSame( $expected, $result );
		}
	}

	/**
	 * Fixture for property get/set unit tests
	 *
	 * @return array
	 */
	public function fixture_property_tests() {
		return array(
			array( 'operation', 'update' ),
			array( 'failed', array() ),
			array( 'members_to_update', array() ),
			array( 'member_to_update', false ), // expecting exception
			array( 'utilities', false ), // Expecting exception

		);
	}

	/**
	 * Instanitate Bulk_Update() with valid and invalid array of member info to process
	 *
	 * @param array  $members_list The array of member info to process
	 * @param string $expected_exception The expected exception
	 *
	 * @return void
	 * @test
	 * @dataProvider fixture_instantiates_with_exception
	 */
	public function it_should_set_members_list_or_trigger_exception( $members_list, $expected_exception ) {

		$exception = null;

		try {
			$b_update = new Bulk_Update( $members_list, $this->m_utils );
		} catch ( InvalidMemberList $e ) {
			$exception = get_class( $e );
		}

		self::assertSame( $expected_exception, $exception );
	}

	/**
	 * Fixture for the it_should_set_members_list_or_trigger_exception test
	 *
	 * @return array[]
	 */
	public function fixture_instantiates_with_exception() {
		$members = $this->fixture_user_level_list( 3, 1 );
		return array(
			array( $members, null ),
			array( null, null ),
			array( false, InvalidMemberList::class ),
			array( 1, InvalidMemberList::class ),
			array( array( 1, 2, 3, 4 ), InvalidMemberList::class ),
		);
	}

	/**
	 * Generate a mocked array of user/level values to process
	 *
	 * @param int  $last_user_id Counting from 1000 to $last_user_id
	 * @param int  $level_id The level_id to use
	 * @param bool $repeat Whether to repeat any user ID number(s)
	 *
	 * @return \int[][]
	 */
	private function fixture_user_level_list( $last_user_id, $level_id, $repeat = false ) {

		$return_array = array();

		if ( 1000 >= $last_user_id ) {
			$last_user_id = 1001;
		}

		if ( ! $repeat ) {
			foreach ( range( 1000, $last_user_id ) as $user_id ) {
				$data           = array(
					'user_id'  => $user_id,
					'level_id' => $level_id,
				);
				$return_array[] = $data;
			}
		}

		return $return_array;
	}
}
