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
use E20R\Members_List\Admin\Exceptions\DBQueryError;
use E20R\Members_List\Admin\Exceptions\InvalidSQL;
use E20R\Members_List\Members_List;
use E20R\Utilities\Message;
use E20R\Utilities\Utilities;

/**
 * Integration tests for the MembersList class
 */
class Members_List_IntegrationTest extends WPTestCase {

	/**
	 * The utilities class we'll use for these tests
	 *
	 * @var null|Utilities $utils
	 */
	private $utils = null;

	/**
	 * Set up for the test class
	 */
	public function setUp(): void {
		parent::setUp();

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['hook_suffix'] = 'pmpro_membership';
		$message                = new Message();
		$this->utils            = new Utilities( $message );
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
			self::assertFalse( true, 'Error generating SQL: ' . $e->getMessage() );
		}

		$resulting_sql = $ml_class->get( 'sql_query' );
		$this->assertDiscardWhitespace( $expected_sql, $resulting_sql );
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
			// FIXME: There's something wrong with the generated SQL during testing - specifically the statusv value when searching for a membership level(?)
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
		} catch ( InvalidSQL $exp ) {
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
	 * @return \int[][]
	 */
	public function fixture_record_count_params(): array {
		return array(
			array( 1 ),
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
		$this->assertEquals( $expected, $actual );
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
