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
 * @package E20R\Tests\Integration\Members_ListTest
 */

namespace E20R\Tests\Integration;

if ( ! defined( 'ABSPATH' ) && defined( 'PLUGIN_PHPUNIT' ) ) {
	die( 'WordPress not loaded. Naughty, naughty!' );
}

use Codeception\TestCase\WPTestCase;
use E20R\Exceptions\InvalidSettingsKey;
use E20R\Licensing\Exceptions\BadOperation;
use E20R\Members_List\Admin\Exceptions\DBQueryError;
use E20R\Members_List\Admin\Exceptions\InvalidProperty;
use E20R\Members_List\Admin\Exceptions\InvalidSQL;
use E20R\Members_List\Admin\Pages\Members_List_Page;
use E20R\Members_List\Members_List;
use E20R\Utilities\Message;
use E20R\Utilities\Utilities;

use Brain\Monkey;

use function E20R\Tests\Fixtures\fixture_insert_level_data;
use function E20R\Tests\Fixtures\fixture_insert_member_data;
use function E20R\Tests\Fixtures\fixture_insert_order_data;
use function E20R\Tests\Fixtures\fixture_insert_user_records;
use function E20R\Tests\Fixtures\fixture_insert_usermeta;
use function E20R\Tests\Fixtures\fixture_clear_test_data;
use function E20R\Tests\Fixtures\fixture_test_tables_exist;

/**
 * Integration tests for the MembersList class
 *
 * @covers \E20R\Members_List\Members_List
 */
class Members_List_IntegrationTest extends WPTestCase {

	/**
	 * The utilities class we'll use for these tests
	 *
	 * @var null|Utilities $utils
	 */
	private $utils = null;

	/**
	 * Validates that tables exist or lets us know which one(s) are missing
	 *
	 * @var bool|string
	 */
	private $tables_exist = true;

	/**
	 * The list of values representing an empty end-date timestamp
	 *
	 * @var array $empty_values
	 */
	private $empty_values = array();

	/**
	 * Set up for the test class
	 */
	public function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['hook_suffix'] = 'pmpro_membership';
		$message                = new Message();
		$this->utils            = new Utilities( $message );

		$this->utils->log( 'Loading records as necessary' );

		if ( false === fixture_test_tables_exist() ) {
			$this->utils->log( 'Error: Required tables are not configured!' );
			$this->tables_exist = false;
		}
		if ( false === fixture_insert_user_records() ) {
			$this->utils->log( 'Error loading new user records' );
			$this->tables_exist = 'wp_users';
		}
		if ( false === fixture_insert_usermeta() ) {
			$this->utils->log( 'Error loading new usermeta records' );
			$this->tables_exist = 'wp_usermeta';
		}
		if ( false === fixture_insert_level_data() ) {
			$this->utils->log( 'Error loading new level records' );
			$this->tables_exist = 'pmpro_membership_levels';
		}
		if ( false === fixture_insert_order_data() ) {
			$this->utils->log( 'Error loading new order records' );
			$this->tables_exist = 'pmpro_membership_orders';
		}
		if ( false === fixture_insert_member_data() ) {
			$this->utils->log( 'Error loading new member data records' );
			$this->tables_exist = 'pmpro_memberships_users';
		}

		$this->empty_values = array(
			'',
			null,
			0,
			'0',
			'0000-00-00 00:00:00',
			'0000-00-00',
			'00:00:00',
		);
	}

	/**
	 * Tear-down test settings
	 *
	 * @return void
	 */
	public function tearDown(): void {
		fixture_clear_test_data();

		Monkey\tearDown();
		parent::tearDown();

	}
	/**
	 * Test the set_tables_and_joins() function with a filter
	 *
	 * @param array      $expected_list The list of expected tables/joins.
	 * @param string|int $level_setting The Level ID we're testing for (required by the Members_List() class).
	 * @param int        $table_list_count The count of table joins we're expecting to see.
	 *
	 * @dataProvider fixture_table_join_list
	 * @test
	 */
	public function it_configures_the_db_tables_and_joins_to_use( array $expected_list, $level_setting, int $table_list_count ) {

		if ( ! is_null( $level_setting ) ) {
			$_REQUEST['level'] = $level_setting;
		}
		$mc_class   = new Members_List();
		$table_list = $mc_class->set_tables_and_joins();

		self::assertEquals( $expected_list, $table_list );
		self::assertArrayHasKey( 'joins', $table_list );
		self::assertArrayHasKey( 'from', $table_list );
		self::assertEquals( $table_list_count, count( $table_list['joins'] ) );

	}

	/**
	 * Fixture for tables & joins
	 *
	 * @return \array[][]
	 */
	public function fixture_table_join_list(): array {
		global $wpdb;
		return array(
			array(
				array(
					'from'  => array(
						'name'  => "{$wpdb->prefix}users",
						'alias' => 'u',
					),
					'joins' => array(
						0 => array(
							'name'      => "{$wpdb->prefix}pmpro_memberships_users",
							'join_type' => 'LEFT JOIN',
							'alias'     => 'mu',
							'condition' => "ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM {$wpdb->prefix}pmpro_memberships_users AS mu3 WHERE mu3.user_id = u.ID ORDER BY mu3.id DESC LIMIT 1)",
						),
						1 => array(
							'name'      => "{$wpdb->prefix}pmpro_membership_levels",
							'join_type' => 'LEFT JOIN',
							'alias'     => 'ml',
							'condition' => 'ON mu.membership_id = ml.id',
						),
						2 => array(
							'name'      => "{$wpdb->prefix}usermeta",
							'join_type' => 'LEFT JOIN',
							'alias'     => 'um',
							'condition' => 'ON u.ID = um.user_id',
						),
					),
				),
				null,
				3,
			),
			array(
				array(
					'from'  => array(
						'name'  => "{$wpdb->prefix}users",
						'alias' => 'u',
					),
					'joins' => array(
						0 => array(
							'name'      => "{$wpdb->prefix}pmpro_memberships_users",
							'join_type' => 'LEFT JOIN',
							'alias'     => 'mu',
							'condition' => "ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM {$wpdb->prefix}pmpro_memberships_users AS mu3 WHERE mu3.user_id = u.ID ORDER BY mu3.id DESC LIMIT 1)",
						),
						1 => array(
							'name'      => "{$wpdb->prefix}pmpro_membership_levels",
							'join_type' => 'LEFT JOIN',
							'alias'     => 'ml',
							'condition' => 'ON mu.membership_id = ml.id',
						),
						2 => array(
							'name'      => "{$wpdb->prefix}usermeta",
							'join_type' => 'LEFT JOIN',
							'alias'     => 'um',
							'condition' => 'ON u.ID = um.user_id',
						),
						3 => array(
							'name'      => "{$wpdb->prefix}pmpro_memberships_users",
							'join_type' => 'LEFT JOIN',
							'alias'     => 'mu2',
							'condition' => "ON u.ID = mu2.user_id AND mu2.status = 'active'",
						),
					),
				),
				'all',
				4,
			),
		);
	}

	/**
	 * Test for the set_sql_columns() member function with filter check(s)
	 *
	 * @param array       $expected The expected column array to test against.
	 * @param string|null $filter_func The filter hook function to use for the test.
	 *
	 * @dataProvider fixture_sql_columns
	 * @test
	 */
	public function it_configures_the_sql_columns_to_use_or_support( array $expected, ?string $filter_func ) {
		// Init the Members_List() class.
		$mc_class = new Members_List( $this->utils );

		if ( ! empty( $filter_func ) ) {
			add_filter( 'e20r_sql_column_alias_map', array( $this, $filter_func ), 10, 1 );
		}

		if ( null !== $filter_func ) {
			$this->assertEquals(
				10, // Filter priority from above.
				has_filter(
					'e20r_sql_column_alias_map',
					array( $this, $filter_func )
				)
			);
		} else {
			$this->assertFalse(
				has_filter(
					'e20r_sql_column_alias_map',
					array( $this, $filter_func )
				)
			);
		}
		$actual = $mc_class->set_sql_columns();

		$this->assertNotEmpty( $actual );
		$this->assertContainsEquals( 'record_id', $actual );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Adds a lastname column mapping
	 *
	 * @param string[] $received Received surname array from filter.
	 *
	 * @return string[]
	 */
	public function fixture_added_surname( array $received ) : array {
		$received['u.user_lastname'] = 'surname';
		return $received;
	}

	/**
	 * Adds a first name column mapping
	 *
	 * @param string[] $received Received firstname array (from filter).
	 *
	 * @return string[]
	 */
	public function fixture_added_firstname( array $received ) : array {
		$firstname = array( 'u.user_firstname' => 'first_name' );
		return $received + $firstname;
	}

	/**
	 * Adds a few different column mappings
	 *
	 * @param string[] $received Received metadata array from the filter.
	 *
	 * @return string[]
	 */
	public function fixture_adding_multiple( array $received ) : array {
		$to_add = array(
			'u.user_something' => 'something',
			'mu.level_meta'    => 'level_name',
		);

		return array_merge( $received, $to_add );
	}

	/**
	 * The sql column fixture
	 *
	 * @return array[]
	 */
	public function fixture_sql_columns(): array {

		$base = array(
			'mu.id'              => 'record_id',
			'u.ID'               => 'ID',
			'u.user_login'       => 'user_login',
			'u.user_email'       => 'user_email',
			'u.user_registered'  => 'user_registered',
			'mu.membership_id'   => 'membership_id',
			'mu.initial_payment' => 'initial_payment',
			'mu.billing_amount'  => 'billing_amount',
			'mu.cycle_period'    => 'cycle_period',
			'mu.cycle_number'    => 'cycle_number',
			'mu.billing_limit'   => 'billing_limit',
			'mu.code_id'         => 'code_id',
			'mu.status'          => 'status',
			'mu.trial_amount'    => 'trial_amount',
			'mu.trial_limit'     => 'trial_limit',
			'mu.startdate'       => 'startdate',
			'mu.enddate'         => 'enddate',
			'ml.name'            => 'name',
		);

		return array(
			array( $base, null ),
			array( $this->fixture_added_surname( $base ), 'fixture_added_surname' ),
			array( $this->fixture_added_firstname( $base ), 'fixture_added_firstname' ),
			array( $this->fixture_adding_multiple( $base ), 'fixture_adding_multiple' ),
		);
	}

	/**
	 * Test the generate_member_sql() function
	 *
	 * @param string|int $level        Membership level to use to find record(s)
	 * @param int        $per_page     Number of items to return per page.
	 * @param int        $page_number  The page number we're returning.
	 * @param string     $sort_order   The sort order (ASC/DESC).
	 * @param string     $order_by     The order_by column.
	 * @param string     $find         The string to find.
	 * @param bool       $is_email     Whether we're searching for an email address.
	 * @param string     $expected_sql The SQL we expect the generate_member_sql() method to return.
	 *
	 * @dataProvider fixture_member_sql_params_levels
	 * @test
	 * @throws InvalidProperty Raise if the property specified is invalid
	 */
	public function it_generates_sql_with_multiple_levels( $level, int $per_page, int $page_number, string $sort_order, string $order_by, string $find, bool $is_email, string $expected_sql ) {

		// Configure the request.
		$_REQUEST['order']   = $sort_order;
		$_REQUEST['orderby'] = $order_by;
		$_REQUEST['find']    = $find;
		$_REQUEST['level']   = $level;

		$ml_class = new Members_List( $this->utils );
		try {
			$ml_class->generate_member_sql( $per_page, $page_number, false );
		} catch ( InvalidSQL $e ) {
			$this->fail( 'Error generating SQL: ' . $e->getMessage() );
		}

		$resulting_sql = $ml_class->get( 'sql_query' );
		self::assertDiscardWhitespace( $expected_sql, $resulting_sql );
	}

	/**
	 * The SQL parameter fixture for the generate_member_sql() unit test
	 *
	 * @return array[]
	 */
	public function fixture_member_sql_params_levels(): array {
		return array(
			// phpcs:ignore
			// $level, $per_page, $page_number, $sort_order, $order_by, find, $is_email, $expected_sql
			array( 'all', -1, -1, 'DESC', 'ml.id, u.ID', '', true, $this->fixture_sql_statement_levels( 0 ) ), // OK.
			array( 'all', -1, -1, 'ASC', 'u.ID', '', true, $this->fixture_sql_statement_levels( 1 ) ), // OK.
			array( 'active', -1, -1, 'DESC', 'ml.id, u.ID', '', true, $this->fixture_sql_statement_levels( 2 ) ), // OK.
			array( 'oldmembers', -1, -1, 'DESC', 'ml.id, u.ID', '', true, $this->fixture_sql_statement_levels( 3 ) ), // OK.
			array( 1, -1, -1, 'DESC', 'ml.id, u.ID', '', true, $this->fixture_sql_statement_levels( 4 ) ), // OK.
			array( 2, 15, 10, 'ASC', 'ml.id, u.ID', '', true, $this->fixture_sql_statement_levels( 5 ) ), // OK.
			array( 2, 15, 11, 'ASC', 'ml.id, u.ID', 'Thomas', true, $this->fixture_sql_statement_levels( 6 ) ),
			array( 'expired', -1, -1, 'DESC', 'ml.id, u.ID', '', true, $this->fixture_sql_statement_levels( 7 ) ), // OK.
			array( 'cancelled', -1, -1, 'DESC', 'ml.id, u.ID', '', true, $this->fixture_sql_statement_levels( 8 ) ), // OK.
			array( 'all', -1, -1, '', 'mu.membership_id', '', true, $this->fixture_sql_statement_levels( 9 ) ), // OK.
		);
	}

	/**
	 * Fixture: Final version of SQL statements being tested
	 *
	 * @param int $counter - Array entry counter.
	 *
	 * @return string
	 */
	public function fixture_sql_statement_levels( $counter = 0 ): string {
		global $wpdb;

		$statements = array(
			0 => "SELECT
		mu.id AS record_id, u.ID AS ID, u.user_login AS user_login, u.user_email AS user_email, u.user_registered AS user_registered, mu.membership_id AS membership_id, mu.initial_payment AS initial_payment, mu.billing_amount AS billing_amount, mu.cycle_period AS cycle_period, mu.cycle_number AS cycle_number, mu.billing_limit AS billing_limit, mu.code_id AS code_id, mu.status AS status, mu.trial_amount AS trial_amount, mu.trial_limit AS trial_limit, mu.startdate AS startdate, mu.enddate AS enddate, ml.name AS name
			 FROM {$wpdb->prefix}users AS u
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM {$wpdb->prefix}pmpro_memberships_users AS mu3 WHERE mu3.user_id = u.ID ORDER BY mu3.id DESC LIMIT 1)
				LEFT JOIN {$wpdb->prefix}pmpro_membership_levels AS ml ON mu.membership_id = ml.id
				LEFT JOIN {$wpdb->prefix}usermeta AS um ON u.ID = um.user_id
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu2 ON u.ID = mu2.user_id AND mu2.status = 'active'
			 WHERE mu.status IN ('cancelled','admin_cancelled','admin_change','admin_changed','changed','inactive','active','expired')
			 GROUP BY ml.id,u.ID
			 ORDER BY ml.id,u.ID DESC
",
			1 => "SELECT
		mu.id AS record_id, u.ID AS ID, u.user_login AS user_login, u.user_email AS user_email, u.user_registered AS user_registered, mu.membership_id AS membership_id, mu.initial_payment AS initial_payment, mu.billing_amount AS billing_amount, mu.cycle_period AS cycle_period, mu.cycle_number AS cycle_number, mu.billing_limit AS billing_limit, mu.code_id AS code_id, mu.status AS status, mu.trial_amount AS trial_amount, mu.trial_limit AS trial_limit, mu.startdate AS startdate, mu.enddate AS enddate, ml.name AS name
			 FROM {$wpdb->prefix}users AS u
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM {$wpdb->prefix}pmpro_memberships_users AS mu3 WHERE mu3.user_id = u.ID ORDER BY mu3.id DESC LIMIT 1)
				LEFT JOIN {$wpdb->prefix}pmpro_membership_levels AS ml ON mu.membership_id = ml.id
				LEFT JOIN {$wpdb->prefix}usermeta AS um ON u.ID = um.user_id
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu2 ON u.ID = mu2.user_id AND mu2.status = 'active'
			 WHERE mu.status IN ('cancelled','admin_cancelled','admin_change','admin_changed','changed','inactive','active','expired')
			 GROUP BY ml.id, u.ID
			 ORDER BY u.ID ASC
",
			2 => "SELECT
		mu.id AS record_id, u.ID AS ID, u.user_login AS user_login, u.user_email AS user_email, u.user_registered AS user_registered, mu.membership_id AS membership_id, mu.initial_payment AS initial_payment, mu.billing_amount AS billing_amount, mu.cycle_period AS cycle_period, mu.cycle_number AS cycle_number, mu.billing_limit AS billing_limit, mu.code_id AS code_id, mu.status AS status, mu.trial_amount AS trial_amount, mu.trial_limit AS trial_limit, mu.startdate AS startdate, mu.enddate AS enddate, ml.name AS name
			 FROM {$wpdb->prefix}users AS u
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM {$wpdb->prefix}pmpro_memberships_users AS mu3 WHERE mu3.user_id = u.ID ORDER BY mu3.id DESC LIMIT 1)
				LEFT JOIN {$wpdb->prefix}pmpro_membership_levels AS ml ON mu.membership_id = ml.id
				LEFT JOIN {$wpdb->prefix}usermeta AS um ON u.ID = um.user_id
			 WHERE (mu.membership_id IS NOT NULL OR mu.membership_id > 0) AND mu.status IN ('active')
			 GROUP BY ml.id, u.ID
			 ORDER BY ml.id, u.ID DESC
",
			3 => "SELECT
		mu.id AS record_id, u.ID AS ID, u.user_login AS user_login, u.user_email AS user_email, u.user_registered AS user_registered, mu.membership_id AS membership_id, mu.initial_payment AS initial_payment, mu.billing_amount AS billing_amount, mu.cycle_period AS cycle_period, mu.cycle_number AS cycle_number, mu.billing_limit AS billing_limit, mu.code_id AS code_id, mu.status AS status, mu.trial_amount AS trial_amount, mu.trial_limit AS trial_limit, mu.startdate AS startdate, mu.enddate AS enddate, ml.name AS name
			 FROM {$wpdb->prefix}users AS u
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM {$wpdb->prefix}pmpro_memberships_users AS mu3 WHERE mu3.user_id = u.ID ORDER BY mu3.id DESC LIMIT 1)
				LEFT JOIN {$wpdb->prefix}pmpro_membership_levels AS ml ON mu.membership_id = ml.id
				LEFT JOIN {$wpdb->prefix}usermeta AS um ON u.ID = um.user_id
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu2 ON u.ID = mu2.user_id AND mu2.status = 'active'
			 WHERE mu.status IN ('expired', 'cancelled') AND mu2.status IS NULL
			 GROUP BY ml.id, u.ID
			 ORDER BY ml.id, u.ID DESC
",
			// FIXME: There's something wrong with the generated SQL during testing - specifically the status value when searching for a membership level(?)
			4 => "SELECT
		mu.id AS record_id, u.ID AS ID, u.user_login AS user_login, u.user_email AS user_email, u.user_registered AS user_registered, mu.membership_id AS membership_id, mu.initial_payment AS initial_payment, mu.billing_amount AS billing_amount, mu.cycle_period AS cycle_period, mu.cycle_number AS cycle_number, mu.billing_limit AS billing_limit, mu.code_id AS code_id, mu.status AS status, mu.trial_amount AS trial_amount, mu.trial_limit AS trial_limit, mu.startdate AS startdate, mu.enddate AS enddate, ml.name AS name
			 FROM {$wpdb->prefix}users AS u
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM {$wpdb->prefix}pmpro_memberships_users AS mu3 WHERE mu3.user_id = u.ID ORDER BY mu3.id DESC LIMIT 1)
				LEFT JOIN {$wpdb->prefix}pmpro_membership_levels AS ml ON mu.membership_id = ml.id
				LEFT JOIN {$wpdb->prefix}usermeta AS um ON u.ID = um.user_id
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu2 ON u.ID = mu2.user_id AND mu2.status = 'active'
			 WHERE (mu.membership_id IS NOT NULL OR mu.membership_id > 0) AND mu.status IN ('active') AND mu.membership_id = 1
			 GROUP BY ml.id, u.ID
			 ORDER BY ml.id, u.ID DESC
",
			5 => "SELECT
		mu.id AS record_id, u.ID AS ID, u.user_login AS user_login, u.user_email AS user_email, u.user_registered AS user_registered, mu.membership_id AS membership_id, mu.initial_payment AS initial_payment, mu.billing_amount AS billing_amount, mu.cycle_period AS cycle_period, mu.cycle_number AS cycle_number, mu.billing_limit AS billing_limit, mu.code_id AS code_id, mu.status AS status, mu.trial_amount AS trial_amount, mu.trial_limit AS trial_limit, mu.startdate AS startdate, mu.enddate AS enddate, ml.name AS name
			 FROM {$wpdb->prefix}users AS u
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM {$wpdb->prefix}pmpro_memberships_users AS mu3 WHERE mu3.user_id = u.ID ORDER BY mu3.id DESC LIMIT 1)
				LEFT JOIN {$wpdb->prefix}pmpro_membership_levels AS ml ON mu.membership_id = ml.id
				LEFT JOIN {$wpdb->prefix}usermeta AS um ON u.ID = um.user_id
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu2 ON u.ID = mu2.user_id AND mu2.status = 'active'
			 WHERE (mu.membership_id IS NOT NULL OR mu.membership_id > 0) AND mu.status IN ('active') AND mu.membership_id = 2
			 GROUP BY ml.id, u.ID
			 ORDER BY ml.id, u.ID ASC
			LIMIT 15 OFFSET 135
",
			6 => "SELECT
		mu.id AS record_id, u.ID AS ID, u.user_login AS user_login, u.user_email AS user_email, u.user_registered AS user_registered, mu.membership_id AS membership_id, mu.initial_payment AS initial_payment, mu.billing_amount AS billing_amount, mu.cycle_period AS cycle_period, mu.cycle_number AS cycle_number, mu.billing_limit AS billing_limit, mu.code_id AS code_id, mu.status AS status, mu.trial_amount AS trial_amount, mu.trial_limit AS trial_limit, mu.startdate AS startdate, mu.enddate AS enddate, ml.name AS name
			 FROM {$wpdb->prefix}users AS u
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM {$wpdb->prefix}pmpro_memberships_users AS mu3 WHERE mu3.user_id = u.ID ORDER BY mu3.id DESC LIMIT 1)
				LEFT JOIN {$wpdb->prefix}pmpro_membership_levels AS ml ON mu.membership_id = ml.id
				LEFT JOIN {$wpdb->prefix}usermeta AS um ON u.ID = um.user_id
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu2 ON u.ID = mu2.user_id AND mu2.status = 'active'
			 WHERE ( u.user_login LIKE '%Thomas%' OR u.user_nicename LIKE '%Thomas%' OR u.display_name LIKE '%Thomas%' OR u.user_email LIKE '%Thomas%' OR um.meta_value LIKE '%Thomas%' ) AND mu.status IN ('active') AND mu.membership_id = 2
			 GROUP BY ml.id, u.ID
			 ORDER BY ml.id, u.ID ASC
			LIMIT 15 OFFSET 150
",
			7 => "SELECT
		mu.id AS record_id, u.ID AS ID, u.user_login AS user_login, u.user_email AS user_email, u.user_registered AS user_registered, mu.membership_id AS membership_id, mu.initial_payment AS initial_payment, mu.billing_amount AS billing_amount, mu.cycle_period AS cycle_period, mu.cycle_number AS cycle_number, mu.billing_limit AS billing_limit, mu.code_id AS code_id, mu.status AS status, mu.trial_amount AS trial_amount, mu.trial_limit AS trial_limit, mu.startdate AS startdate, mu.enddate AS enddate, ml.name AS name
			 FROM {$wpdb->prefix}users AS u
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM {$wpdb->prefix}pmpro_memberships_users AS mu3 WHERE mu3.user_id = u.ID ORDER BY mu3.id DESC LIMIT 1)
				LEFT JOIN {$wpdb->prefix}pmpro_membership_levels AS ml ON mu.membership_id = ml.id
				LEFT JOIN {$wpdb->prefix}usermeta AS um ON u.ID = um.user_id
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu2 ON u.ID = mu2.user_id AND mu2.status = 'active'
			 WHERE mu.status IN ('expired') AND mu2.status IS NULL
			 GROUP BY ml.id, u.ID
			 ORDER BY ml.id, u.ID DESC
",
			8 => "SELECT
		mu.id AS record_id, u.ID AS ID, u.user_login AS user_login, u.user_email AS user_email, u.user_registered AS user_registered, mu.membership_id AS membership_id, mu.initial_payment AS initial_payment, mu.billing_amount AS billing_amount, mu.cycle_period AS cycle_period, mu.cycle_number AS cycle_number, mu.billing_limit AS billing_limit, mu.code_id AS code_id, mu.status AS status, mu.trial_amount AS trial_amount, mu.trial_limit AS trial_limit, mu.startdate AS startdate, mu.enddate AS enddate, ml.name AS name
			 FROM {$wpdb->prefix}users AS u
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM {$wpdb->prefix}pmpro_memberships_users AS mu3 WHERE mu3.user_id = u.ID ORDER BY mu3.id DESC LIMIT 1)
				LEFT JOIN {$wpdb->prefix}pmpro_membership_levels AS ml ON mu.membership_id = ml.id
				LEFT JOIN {$wpdb->prefix}usermeta AS um ON u.ID = um.user_id
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu2 ON u.ID = mu2.user_id AND mu2.status = 'active'
			 WHERE mu.status IN ('cancelled','admin_cancelled','admin_change','admin_changed','changed','inactive') AND mu2.status IS NULL
			 GROUP BY ml.id, u.ID
			 ORDER BY ml.id, u.ID DESC
",
			9 => "SELECT
		mu.id AS record_id, u.ID AS ID, u.user_login AS user_login, u.user_email AS user_email, u.user_registered AS user_registered, mu.membership_id AS membership_id, mu.initial_payment AS initial_payment, mu.billing_amount AS billing_amount, mu.cycle_period AS cycle_period, mu.cycle_number AS cycle_number, mu.billing_limit AS billing_limit, mu.code_id AS code_id, mu.status AS status, mu.trial_amount AS trial_amount, mu.trial_limit AS trial_limit, mu.startdate AS startdate, mu.enddate AS enddate, ml.name AS name
			 FROM {$wpdb->prefix}users AS u
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM {$wpdb->prefix}pmpro_memberships_users AS mu3 WHERE mu3.user_id = u.ID ORDER BY mu3.id DESC LIMIT 1)
				LEFT JOIN {$wpdb->prefix}pmpro_membership_levels AS ml ON mu.membership_id = ml.id
				LEFT JOIN {$wpdb->prefix}usermeta AS um ON u.ID = um.user_id
				LEFT JOIN {$wpdb->prefix}pmpro_memberships_users AS mu2 ON u.ID = mu2.user_id AND mu2.status = 'active'
			 WHERE mu.status IN ('cancelled','admin_cancelled','admin_change','admin_changed','changed','inactive','active','expired')
			 GROUP BY ml.id, u.ID
			 ORDER BY mu.membership_id DESC
",
		);

		return ( $statements[ $counter ] ?? '' );
	}

	/**
	 * Happy-path for the Members_List::get_members() method
	 *
	 * @param int         $per_page Number of results to return per page.
	 * @param int         $page_number The page number.
	 * @param string|null $status The status of the membership we're looking for.
	 * @param string|null $sort_order The sort order to use (ASC/DESC).
	 * @param string|null $order_by The column to order the result by.
	 * @param string|null $find The value to search for.
	 * @param bool        $is_email Whether that value is an email or not.
	 * @param array|null  $record_list The list of records we expect to see returned.
	 *
	 * @dataProvider fixture_get_members_happy
	 * @test
	 * @throws DBQueryError Raised if there's a problem with the DB query being generated
	 */
	public function it_generates_sql_for_members_list_using_happy_path( int $per_page, int $page_number, ?string $status, ?string $sort_order, ?string $order_by, ?string $find, bool $is_email, $record_list ) {

		if ( ! is_null( $sort_order ) ) {
			$_REQUEST['order'] = $sort_order;
		}

		if ( ! is_null( $find ) ) {
			$_REQUEST['find'] = $find;
		}

		if ( ! is_null( $status ) ) {
			$_REQUEST['level'] = $status;
		}

		$mc_class = new Members_List( $this->utils );

		try {
			$mc_class->get_members( $per_page, $page_number );
		} catch ( InvalidSQL | InvalidSettingsKey | BadOperation | DBQueryError $exp ) {
			$this->utils->log( "Error: {$exp->getMessage()}" );
		}

		$this->assertEquals( $record_list, $mc_class->items );
	}

	/**
	 * The SQL parameter fixture for the generate_member_sql() unit test
	 *
	 * @return array[][]
	 */
	public function fixture_get_members_happy(): array {
		return array(
			// phpcs:ignore
			// $per_page, $page_number, $status, $sort_order, $order_by, find, $is_email, $record_list
			array( -1, -1, 'active', 'DESC', 'ml.id', '', true, null ),
			array( 15, 10, 'active', 'DESC', 'ml.id', '', true, null ),
		);
	}

	/**
	 * Testing the Members_List::get_member_record_count() method
	 *
	 * @param string|int $level_id The membership level (id) we should process for.
	 *
	 * @dataProvider fixture_record_count_params
	 * @test
	 */
	public function it_gets_the_member_record_count_for_the_level_id( $level_id ) {

		// Setup
		if ( ! is_null( $level_id ) ) {
			$GLOBALS['level'] = $level_id;
		}

	}

	/**
	 * The fixture method for test_get_member_record_count
	 *
	 * @return int[][]
	 */
	public function fixture_record_count_params(): array {
		return array(
			array( 1 ),
		);
	}

	/**
	 * Test the returned column value when processing Members_List::column_last()
	 *
	 * @param array       $test_item The simulated CSV column data for the import
	 * @param null|string $next_payment The expected next payment date for recurring billing configurations
	 * @param string|null $date_format The date format to use for the column
	 * @param string      $expected_html The expected HTML/enddate information for the 'last' column
	 *
	 * @return void
	 *
	 * @dataProvider fixture_last_column_item
	 * @test
	 */
	public function it_should_return_the_correct_html_for_the_last_column(
		$test_item,
		$next_payment,
		$date_format,
		$expected_html
	) {
		$existing_date_format = get_option( 'date_format' );

		if ( $existing_date_format !== $date_format ) {
			if ( false === update_option( 'date_format', $date_format, 'no' ) ) {
				$this->fail( 'Cannot configure date format to: ' . $date_format );
			}
		}

		$page        = new Members_List_Page( $this->utils );
		$ml          = new Members_List( $this->utils, $page );
		$result_html = $ml->column_last( $test_item );
		$timestamp   = ! $ml->has_empty_date( $test_item['enddate'] ) ?
			strtotime( $test_item['enddate'], time() ) :
			null;

		self::assertDiscardWhitespace( $expected_html, $result_html );
		self::assertStringContainsString(
			"<input type=\"hidden\" value=\"{$test_item['membership_id']}\" class=\"e20r-members-list-membership-id\" name=\"e20r-members-list-enddate_mid_{$test_item['ID']}\" />",
			$result_html,
			"Error: membership_id info problem -> MID: {$test_item['membership_id']} for user {$test_item['ID']}"
		);
		self::assertStringContainsString(
			"<input type=\"hidden\" value=\"{$test_item['ID']}\" class=\"e20r-members-list-user-id\" name=\"e20r-members-list-user_id_{$test_item['ID']}\" />",
			$result_html,
			"Error: user_id info problem -> user_id: {$test_item['ID']}"
		);
		self::assertStringContainsString(
			"<input type=\"hidden\" value=\"{$timestamp}\" class=\"e20r-members-list-db-enddate\" name=\"e20r-members-list-db_enddate_{$test_item['ID']}\" />",
			$result_html,
			"Error: enddate timestamp problem -> Timestamp: {$timestamp} for user {$test_item['ID']}"
		);

		if ( ! empty( $existing_date_format ) ) {
			update_option( 'date_format', $existing_date_format );
		}
	}

	/**
	 * Fixture for the it_should_return_the_correct_html_for_the_last_column() test
	 *
	 * @return array[]
	 */
	public function fixture_last_column_item() {
		$item_list          = $this->fixture_generate_item_list();
		$return_array       = array();
		$this->empty_values = array(
			'',
			null,
			0,
			'0',
			'0000-00-00 00:00:00',
			'0000-00-00',
			'00:00:00',
		);

		// Build the fixture dynamically(ish)
		foreach ( $item_list as $item ) {
			// Have custom settings we need to pull
			$date_format  = $item['date_format'];
			$next_payment = $item['next_payment'];

			// Clean up the item before treating it as a user record
			unset( $item['date_format'] );
			unset( $item['next_payment'] );

			// Add a test record
			$return_array[] = array( $item, $next_payment, $date_format, $this->fixture_generate_enddate_html( $item, $date_format, $next_payment ) );
		}

		return $return_array;
	}

	/**
	 * Generates a list of records to test against
	 *
	 * @return array[]
	 */
	private function fixture_generate_item_list() {
		return array(
			// Test with membership that has fixed end-date and no recurring billing  (F j, Y as date format)
			array(
				'record_id'       => 2,
				'ID'              => 1,
				'membership_id'   => 1,
				'code_id'         => 0,
				'startdate'       => '2022-02-12 09:36:33',
				'enddate'         => '2023-02-12 23:59:59',
				'initial_payment' => 25.00,
				'billing_amount'  => 0.00,
				'next_payment'    => null,
				'cycle_number'    => 0,
				'cycle_period'    => '',
				'date_format'     => 'F j, Y',
			),
			// Test with a recurring membership setup (F j, Y as date format)
			array(
				'record_id'       => 2,
				'ID'              => 3,
				'membership_id'   => 2,
				'code_id'         => 0,
				'startdate'       => '2022-02-12 09:38:22',
				'enddate'         => '0000-00-00 00:00:00',
				'initial_payment' => 15.00,
				'billing_amount'  => 10.00,
				'next_payment'    => 'March 12, 2022',
				'cycle_number'    => 1,
				'cycle_period'    => 'Month',
				'date_format'     => 'F j, Y',
			),
			// Test with membership that has fixed end-date and no recurring billing  (Y-m-d as date format)
			array(
				'record_id'       => 2,
				'ID'              => 1,
				'membership_id'   => 1,
				'code_id'         => 0,
				'startdate'       => '2022-02-12 09:36:33',
				'enddate'         => '2023-02-12 23:59:59',
				'initial_payment' => 25.00,
				'billing_amount'  => 0.00,
				'next_payment'    => null,
				'cycle_number'    => 0,
				'cycle_period'    => '',
				'date_format'     => 'Y-m-d',
			),
			// Test with a recurring membership setup (Y-m-d as date format)
			array(
				'record_id'       => 2,
				'ID'              => 3,
				'membership_id'   => 2,
				'code_id'         => 0,
				'startdate'       => '2022-02-12 09:38:22',
				'enddate'         => '0000-00-00 00:00:00',
				'next_payment'    => '2022-03-12',
				'initial_payment' => 15.00,
				'billing_amount'  => 10.00,
				'cycle_number'    => 1,
				'cycle_period'    => 'Month',
				'date_format'     => 'Y-m-d',
			),
			// Test with what is an empty (no) enddate value
			array(
				'ID'             => 3,
				'record_id'      => 682,
				'membership_id'  => 1,
				'enddate'        => null,
				'billing_amount' => null,
				'next_payment'   => null,
				'cycle_number'   => null,
				'date_format'    => 'F j, Y',
			),
			// Test with what should also be treated as an empty (no) enddate value
			array( // #5
				'ID'             => 3,
				'record_id'      => 682,
				'membership_id'  => 1,
				'enddate'        => '',
				'billing_amount' => null,
				'next_payment'   => null,
				'cycle_number'   => null,
				'date_format'    => 'F j, Y',
			),
			// Test with a valid end-date
			array(
				'ID'             => 3,
				'record_id'      => 682,
				'membership_id'  => 1,
				'enddate'        => '2020-01-01 23:59:59',
				'billing_amount' => null,
				'next_payment'   => null,
				'cycle_number'   => null,
				'date_format'    => 'F j, Y',
			),
		);
	}

	/**
	 * Generate the expected HTML for the fixture_last_column_item() method
	 * NOTE: We won't be including the 'id=' field for the datepicker field (date field)
	 * as it's done by the datepicker JS library
	 *
	 * @param array       $item The record we're testing with
	 * @param string      $date_format The date format
	 * @param string|null $next_payment The expected next payment date
	 *
	 * @return string
	 */
	private function fixture_generate_enddate_html( $item, $date_format, $next_payment ) {
		if ( true !== $this->tables_exist ) {
			$this->fail( "Error: Something wrong with the table(s)... '{$this->tables_exist}'" );
		}

		$empty_date        = in_array( $item['enddate'], $this->empty_values, true );
		$enddate_timestamp = $empty_date ? null : strtotime( $item['enddate'], time() );
		$enddate_label     = $empty_date ? 'N/A' : gmdate( $date_format, $enddate_timestamp );

		if ( $empty_date && ! empty( $item['billing_amount'] ) && ! empty( $item['cycle_number'] ) ) {
			return $this->fixture_get_recurring(
				$next_payment,
				$item,
				$enddate_timestamp,
				$enddate_label
			);
		} else {
			return $this->fixture_get_non_recurring(
				$enddate_label,
				$item,
				$enddate_timestamp
			);
		}
	}

	/**
	 * HTML we expect when the user has a non-recurring membership
	 *
	 * @param string $next_payment_date The date used to show when the next payment is scheduled
	 * @param array  $item The record being processed
	 * @param int    $enddate_timestamp The epoch value for the enddate
	 * @param string $enddate_label The label used for the 'enddate'
	 *
	 * @return string
	 */
	private function fixture_get_recurring( $next_payment_date, $item, $enddate_timestamp, $enddate_label ) {
		return sprintf(
			'<a href="#" class="e20r-members-list_enddate e20r-members-list-editable" title="Update membership end/expiration date">N/A (<span class="e20r-members-list-small" style="font-size: 10px; font-style: italic;">Next Payment: %1$s</span>)<span class="dashicons dashicons-edit"></span></a><div class="ml-row-settings clearfix">

				<input type="hidden" value="%2$d" class="e20r-members-list-membership-id" name="e20r-members-list-enddate_mid_%3$d" />
				<input type="hidden" value="%3$d" class="e20r-members-list-user-id" name="e20r-members-list-user_id_%3$d" />
				<input type="hidden" value="%6$s" class="e20r-members-list-enddate-label" name="e20r-members-list-enddatelabel_%3$d" />
				<input type="hidden" value="%4$s" class="e20r-members-list-db-enddate" name="e20r-members-list-db_enddate_%3$d" />
				<input type="hidden" value="%5$d" class="e20r-members-list-db_record_id" name="e20r-members-list-db_record_id_%3$d" />
				<input type="hidden" value="enddate" class="e20r-members-list-field-name" name="e20r-members-list-field_name_%3$d" />
						<input type="date" placeholder="YYYY-MM-DD" pattern="(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))" title="Enter a date in this format YYYY-MM-DD" name="e20r-members-list-new_enddate_%3$d" class="e20r-members-list-input-enddate" value="%4$s" />
						<br />
						<a href="#" class="e20r-members-list-cancel e20r-members-list-list-link">Cancel</a>
					</div>',
			$next_payment_date,
			$item['membership_id'],
			$item['ID'],
			$enddate_timestamp,
			$item['record_id'],
			$enddate_label
		);
	}

	/**
	 * HTML we expect when the user has a non-recurring membership
	 *
	 * @param string $enddate_label The end-date label (text)
	 * @param array  $item The record being processed
	 * @param int    $enddate_timestamp The epoch value for the enddate
	 * @return string
	 */
	private function fixture_get_non_recurring( $enddate_label, $item, $enddate_timestamp ) {
		return sprintf(
			'<a href="#" class="e20r-members-list_enddate e20r-members-list-editable" title="Update membership end/expiration date">%1$s<span class="dashicons dashicons-edit"></span></a><div class="ml-row-settings clearfix">

				<input type="hidden" value="%2$d" class="e20r-members-list-membership-id" name="e20r-members-list-enddate_mid_%3$d" />
				<input type="hidden" value="%3$d" class="e20r-members-list-user-id" name="e20r-members-list-user_id_%3$d" />
				<input type="hidden" value="%1$s" class="e20r-members-list-enddate-label" name="e20r-members-list-enddatelabel_%3$d" />
				<input type="hidden" value="%4$s" class="e20r-members-list-db-enddate" name="e20r-members-list-db_enddate_%3$d" />
				<input type="hidden" value="%5$d" class="e20r-members-list-db_record_id" name="e20r-members-list-db_record_id_%3$d" />
				<input type="hidden" value="enddate" class="e20r-members-list-field-name" name="e20r-members-list-field_name_%3$d" />
						<input type="date" placeholder="YYYY-MM-DD" pattern="(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))" title="Enter a date in this format YYYY-MM-DD" name="e20r-members-list-new_enddate_%3$d" class="e20r-members-list-input-enddate" value="%6$s" />
						<br />
						<a href="#" class="e20r-members-list-cancel e20r-members-list-list-link">Cancel</a>
					</div>',
			$enddate_label,
			$item['membership_id'],
			$item['ID'],
			$enddate_timestamp,
			$item['record_id'],
			$enddate_timestamp ? gmdate( 'Y-m-d', $enddate_timestamp ) : $enddate_timestamp
		);
	}

	/**
	 * Test the columns to display (and their order)
	 *
	 * @param string   $filter_name The name of the column filter to apply
	 * @param string   $filter_method The method name for the filter hook
	 * @param string[] $expected The expected column list
	 *
	 * @dataProvider fixture_table_columns
	 * @test
	 */
	public function it_applies_the_filter_and_returns_columns_to_display( string $filter_name, string $filter_method, array $expected ) {

		// Configure & set the last column name we should have
		$members_list = new Members_List( $this->utils );

		// Add a filter(s) to update the columns
		if ( $filter_name ) {
			$this->utils->log( "Adding '{$filter_method}' to '${filter_name}'" );
			add_filter( $filter_name, array( $this, $filter_method ) );
		}

		$actual = $members_list->all_columns();
		self::assertSame( $expected, $actual );
	}

	/**
	 * Fixture: Filters for the all_columns() tests
	 *
	 * @return array[]
	 */
	public function fixture_table_columns() {
		return array(
			// filter_name, filter_method, expected
			array(
				'e20r_memberslist_columnlist',
				'pfixture_last_column',
				$this->fixture_default_columns(),
			),
		);
	}

	/**
	 * Filter hook handler for the fixtures we'll be using to test default column values
	 *
	 * @param array $columns The supplied column array
	 *
	 * @return string[]
	 */
	public function pfixture_last_column( $columns ) {
		$columns['last'] = 'Ends on';
		return $columns;
	}
	/**
	 * Default set of columns we should expect
	 *
	 * @return string[]
	 */
	public function fixture_default_columns() {
		$GLOBALS['hook_suffix'] = 'pmpro_membership'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$members_list           = new Members_List( $this->utils );
		return $members_list->all_columns();
	}
}
