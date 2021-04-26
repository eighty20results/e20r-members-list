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

namespace E20R\Members_List\WPUnitTest;

use Codeception\TestCase\WPTestCase;
use E20R\Members_List\Admin\Members_List;
use E20R\Utilities\Utilities;
use Spatie\Snapshots\MatchesSnapshots;

class Members_ListTest extends WPTestCase {
	use MatchesSnapshots;

	/**
	 * Set up for the test class
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
			define( 'WP_PLUGIN_DIR', '../../' );
		}

		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', '../../' );
		}

		$GLOBALS['hook_suffix'] = 'pmpro_membership';
	}

	/**
	 * Teardown which calls \WP_Mock tearDown
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Test the set_tables_and_joins() function with a filter
	 *
	 * @param array      $expected_list
	 * @param string|int $level_setting
	 * @param int        $table_list_count
	 *
	 * @throws \Exception
	 * @dataProvider fixture_table_join_list
	 */
	public function test_set_tables_and_joins( array $expected_list, $level_setting, int $table_list_count ) {

		if ( ! is_null( $level_setting ) ) {
			$_REQUEST['level'] = $level_setting;
		}
		$mc_class = new Members_List();
		$table_list = $mc_class->set_tables_and_joins();

		$this->assertEquals( $expected_list, $table_list );
		$this->assertArrayHasKey( 'joins', $table_list );
		$this->assertArrayHasKey( 'from', $table_list );
		$this->assertEquals( $table_list_count, count( $table_list['joins'] ) );

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
				3
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
						)
					),
				),
				'all',
				4
			)
		);
	}

	/**
	 * Test for the set_sql_columns() member function with filter check(s)
	 *
	 * @param array       $expected
	 * @param string|null $filter_func
	 *
	 * @dataProvider fixture_sql_columns
	 * @throws \Exception
	 */
	public function test_set_sql_columns( array $expected, ?string $filter_func ) {
		// Init the Members_List() class
		$mc_class = new Members_List();

		if ( !empty( $filter_func ) ) {
			// $mc_class->get( 'utils' )->log("Adding column map handler: {$filter_func}");
			add_filter( 'e20r_sql_column_alias_map', array( $this, $filter_func ), 10, 1 );
		}

		if ( null !== $filter_func ) {
			$this->assertEquals(
				10, // Filter priority from above
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
	 * @param string[] $received
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
	 * @param string[] $received
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
	 * @param string[] $received
	 *
	 * @return string[]
	 */
	public function fixture_adding_multiple( array $received ) : array {
		$to_add = array(
			'u.user_something' => 'something',
			'mu.level_meta' => 'level_name'
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
	 * @param string|int  $status
	 * @param int         $per_page
	 * @param int         $page_number
	 * @param string      $sort_order
	 * @param string      $order_by
	 * @param string      $find
	 * @param bool        $is_email
	 * @param string      $expected_sql
	 *
	 * @throws \Exception
	 * @dataProvider fixture_member_sql_params_levels
	 */
	public function test_generate_member_sql_levels( $status, int $per_page, int $page_number, string $sort_order, string $order_by, string $find, bool $is_email, string $expected_sql ) {

		// Configure the request
		$_REQUEST['order'] = $sort_order;
		$_REQUEST['orderby'] = $order_by;
		$_REQUEST['find'] = $find;
		$_REQUEST['level'] = $status;

		$mc_class = new Members_List();
		$mc_class->generate_member_sql( $per_page, $page_number );
		$resulting_sql = $mc_class->get( 'sql_query' );

		$this->assertDiscardWhitespace( $expected_sql, $resulting_sql);
		// $this->assertEquals( $expected_sql, $resulting_sql );
	}

	/**
	 * The SQL parameter fixture for the generate_member_sql() unit test
	 *
	 * @return array[]
	 */
	public function fixture_member_sql_params_levels(): array {
		return array(
			// phpcs:ignore
			// $status, $per_page, $page_number, $sort_order, $order_by, find, $is_email, $expected_sql
			array( 'all', -1, -1, 'DESC', 'ml.id, u.ID', '', true, $this->fixture_sql_statement_levels( 0 ) ), // OK
			array( 'all', -1, -1, 'ASC', 'u.ID', '', true, $this->fixture_sql_statement_levels( 1 ) ), // OK
			array( 'active', -1, -1, 'DESC', 'ml.id, u.ID', '', true, $this->fixture_sql_statement_levels( 2 ) ), // OK
			array( 'oldmembers', -1, -1, 'DESC', 'ml.id, u.ID', '', true, $this->fixture_sql_statement_levels( 3 ) ), // OK
			array( 1, -1, -1, 'DESC', 'ml.id, u.ID', '', true, $this->fixture_sql_statement_levels( 4 ) ), // OK
			array( 2, 15, 10, 'ASC', 'ml.id, u.ID', '', true, $this->fixture_sql_statement_levels( 5 ) ), // OK
			array( 2, 15, 11, 'ASC', 'ml.id, u.ID', 'Thomas', true, $this->fixture_sql_statement_levels( 6 ) ),
			array( 'expired', -1, -1, 'DESC', 'ml.id, u.ID', '', true, $this->fixture_sql_statement_levels( 7 ) ), // OK
			array( 'cancelled', -1, -1, 'DESC', 'ml.id, u.ID', '', true, $this->fixture_sql_statement_levels( 8 ) ), // OK
			array( 'all', -1, -1, '', 'mu.membership_id', '', true, $this->fixture_sql_statement_levels( 9 ) ), // OK
		);
	}

	/**
	 * Fixture: Final version of SQL statements being tested
	 *
	 * @param int $counter - Array entry counter
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
			 GROUP BY ml.id, u.ID
			 ORDER BY ml.id, u.ID DESC
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
"
			);

		return ( $statements[ $counter ] ?? "" );
	}

	/**
	 * Happy-path for the Members_List::get_members() method
	 *
	 * @param int        $per_page
	 * @param int        $page_number
	 * @param string     $status
	 * @param string     $sort_order
	 * @param string     $order_by
	 * @param string     $find
	 * @param bool       $is_email
	 * @param array|null $record_list
	 *
	 * @dataProvider fixture_get_members_happy
	 * @throws \Exception
	 */
	public function test_get_members_happy_path( int $per_page, int $page_number, string $status, string $sort_order, string $order_by, string $find, bool $is_email, $record_list ) {

		if ( ! is_null( $sort_order ) ) {
			$_REQUEST['order'] = $sort_order;
		}

		if ( ! is_null( $find ) ) {
			$_REQUEST['find']  = $find;
		}

		if ( ! is_null( $status ) ) {
			$_REQUEST['level'] = $status;
		}

		$mc_class = new Members_List();

		try {
			$mc_class->get_members( $per_page, $page_number );
		} catch( \Exception $exp ) {
			$mc_class->get('utils')->log("Error: {$exp->getMessage()}" );
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
	 * @param string|int $level_id
	 *
	 * @dataProvider fixture_record_count_params
	 */
	public function test_get_member_record_count( $level_id ) {

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
	 * @param string   $filter_name
	 * @param string   $filter_method
	 * @param string[] $expected
	 *
	 * @dataProvider fixture_table_columns
	 * @throws \Exception
	 */
	public function test_all_columns( string $filter_name, string $filter_method,  array $expected ) {

		// Configure & set the last column name we should have
		$mlist = new Members_List();

		// Add a filter(s) to update the columns
		if ( $filter_name ) {
			$mlist->get('utils')->log("Adding '{$filter_method}' to '${filter_name}'");
			add_filter( $filter_name, array( $this, $filter_method) );
		}

		$actual = $mlist->all_columns();
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Fixture: Filters for the all_columns() tests
	 *
	 * @return array[]
	 */
	public function fixture_table_columns() {
		return array(
			// $filter_name, $filter_method, $expected
			array(
				'e20r_memberslist_columnlist',
				'pfixture_last_column',
				$this->fixture_default_columns() + array( 'last' => 'Ends on' )
			),
		);
	}

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

		$default = array(
			'cb'              => '<input type="checkbox" />',
			'user_login'      => _x( 'Login', 'e20r-members-list' ),
			'first_name'      => _x( 'First Name', 'e20r-members-list' ),
			'last_name'       => _x( 'Last Name', 'e20r-members-list' ),
			'user_email'      => _x( 'Email', 'e20r-members-list' ),
			'baddress'        => _x( 'Billing Info', 'e20r-members-list' ),
			'name'            => _x( 'Level', 'e20r-members-list' ),
			'fee'             => _x( 'Fee', 'e20r-members-list' ),
			'code'            => _x( 'Discount Code', 'e20r-members-list' ),
			'status'          => _x( 'Status', 'e20r-members-list' ),
			'user_registered' => _x( 'Joined', 'e20r-members-list' ),
			'startdate'       => _x( 'Start', 'e20r-members-list' ),
			// 'last'            => _x( 'Expires', 'e20r-members-list' ),
		);

		return $default;
	}

	public function test_process_bulk_action() {

	}


	public function test_export_members() {

	}

	public function test_get_sortable_columns() {

	}

	public function test_Prepare_items() {

	}

	public function test_get_hidden_columns() {

	}
}
