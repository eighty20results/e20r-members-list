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

use Codeception\Test\Unit;
use Brain\Monkey;
use E20R\Members_List\Admin\Members_List;
use Spatie\Snapshots\MatchesSnapshots;

class Members_ListTest extends Unit {
	use MatchesSnapshots;

	private $mc_class;

	public function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
			define( 'WP_PLUGIN_DIR', '../../' );
		}

		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', '../../' );
		}

		// A few common passthroughs
		// 1. WordPress i18n functions
		Monkey\Functions\when( '__' )
			->returnArg( 1 );
		Monkey\Functions\when( '_e' )
			->returnArg( 1 );
		Monkey\Functions\when( '_n' )
			->returnArg( 1 );
		Monkey\Functions\when( '_x' )
			->returnArg( 1 );
		Monkey\Functions\when( 'esc_attr__' )
			->returnArg( 1 );
		Monkey\Functions\when( 'esc_sql' )
			->returnArg( 1 );

		Monkey\Functions\when( 'sanitize_text_field' )
			->returnArg( 1 );

		Monkey\Functions\when( 'plugins_url' )
			->justReturn( '/var/www/html/wp-content/plugins/e20r-members-list/' );
		( sprintf( 'https://development.local/wp-content/plugins/' ) );
		Monkey\Functions\when( 'plugin_dir_path' )
			->justReturn( sprintf( '%1$s/', getcwd() ) );
		Monkey\Functions\when( 'get_current_blog_id' )
			->justReturn( 1 );

		$GLOBALS['hook_suffix'] = 'pmpro_membership';

		// Not assuming that the PMPro plugin is active for unit tests
		global $wpdb;
		$wpdb->pmpro_memberships_users = "{$wpdb->prefix}pmpro_memberships_users";
		$wpdb->pmpro_membership_levels = "{$wpdb->prefix}pmpro_membership_levels";
		$this->mc_class = new Members_List();
	}

	/**
	 * Teardown which calls \WP_Mock tearDown
	 *
	 * @return void
	 */
	public function tearDown(): void {
		global $wpdb;
		@$wpdb->check_connection();

		Monkey\tearDown();
		parent::tearDown();
		// Mockery::close();
	}

	/**
	 * Test the set_tables_and_joins() function with a filter
	 *
	 * @param array $expected_list
	 * @param string|int $level_setting
	 * @param int $table_list_count
	 *
	 * @throws \Exception
	 * @dataProvider fixture_table_join_list
	 */
	public function test_set_tables_and_joins( $expected_list, $level_setting, $table_list_count  ) {

		if ( ! is_null( $level_setting ) ) {
			$_REQUEST['level'] = $level_setting;
		}

		$table_list = $this->mc_class->set_tables_and_joins();

		$this->assertIsArray( $table_list );
		$this->assertEquals( $table_list_count, count( $table_list['joins'] ) );
		$this->assertArrayHasKey( 'joins', $table_list );
		$this->assertArrayHasKey( 'from', $table_list );
		$this->assertEquals( $expected_list, $table_list );

		Monkey\Filters\applied( 'e20r_memberslist_tables_and_joins' );
	}

	/**
	 * Fixture for tables & joins
	 *
	 * @return \array[][]
	 */
	public function fixture_table_join_list() {
		return array(
			array(
				array(
					'from'  => array(
						'name'  => 'wp_users',
						'alias' => 'u',
					),
					'joins' => array(
						0 => array(
							'name'      => 'wp_pmpro_memberships_users',
							'join_type' => 'LEFT JOIN',
							'alias'     => 'mu',
							'condition' => "ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM wp_pmpro_memberships_users AS mu3 WHERE mu3.user_id = u.id ORDER BY mu3.id DESC LIMIT 1)",
						),
						1 => array(
							'name'      => 'wp_pmpro_membership_levels',
							'join_type' => 'LEFT JOIN',
							'alias'     => 'ml',
							'condition' => 'ON mu.membership_id = ml.id',
						),
						2 => array(
							'name'      => 'wp_usermeta',
							'join_type' => 'LEFT JOIN ',
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
						'name'  => 'wp_users',
						'alias' => 'u',
					),
					'joins' => array(
						0 => array(
							'name'      => 'wp_pmpro_memberships_users',
							'join_type' => 'LEFT JOIN',
							'alias'     => 'mu',
							'condition' => "ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM wp_pmpro_memberships_users AS mu3 WHERE mu3.user_id = u.id ORDER BY mu3.id DESC LIMIT 1)",
						),
						1 => array(
							'name'      => 'wp_pmpro_membership_levels',
							'join_type' => 'LEFT JOIN',
							'alias'     => 'ml',
							'condition' => 'ON mu.membership_id = ml.id',
						),
						2 => array(
							'name'      => 'wp_usermeta',
							'join_type' => 'LEFT JOIN ',
							'alias'     => 'um',
							'condition' => 'ON u.ID = um.user_id',
						),
					),
				),
				'all',
				3
			)
		);
	}

	/**
	 * Test for the set_sql_columns() member function with filter check(s)
	 *
	 * @param array $expected
	 * @param string $filter_func
	 *
	 * @dataProvider fixture_sql_columns
	 */
	public function test_set_sql_columns( $expected, $filter_func ) {

		if ( null !== $filter_func ) {
			error_log("Adding column alias map filter handler: {$filter_func}");
			add_filter( 'e20r_members_list_default_sql_column_alias_map', array( $this->mc_class, $filter_func ) );
		}

		$result = $this->mc_class->set_sql_columns();
		// error_log("SQL Columns: " . print_r( $result, true ));

		$this->assertIsArray( $result );
		$this->assertEquals( $expected, $result );
		Monkey\Filters\applied( 'e20r_members_list_default_sql_column_alias_map' );
	}

	/**
	 * Adds a lastname column mapping
	 *
	 * @param string[] $received
	 *
	 * @return string[]
	 */
	public function fixture_added_surname( $received ) {
		error_log("Adding surname column");
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
	public function fixture_added_firstname( $received ) {
		error_log("Adding first_name column with array_merge()");
		$firstname = array( 'u.user_firstname' => 'first_name' );
		return array_merge( $received, $firstname );
	}

	/**
	 * Adds a few different column mappings
	 *
	 * @param string[] $received
	 *
	 * @return string[]
	 */
	public function fixture_adding_multiple( $received ) {
		error_log("Adding 2 columns with array_merge()");
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
	public function fixture_sql_columns() {

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
	 * @param string $status
	 * @param int    $per_page
	 * @param int    $page_number
	 * @param string $sort_order
	 * @param string $order_by
	 * @param string $find
	 * @param bool   $is_email
	 * @param string $expected_sql
	 *
	 * @dataProvider fixture_member_sql_params
	 * @throws \Exception
	 */
	public function test_generate_member_sql( $status, $per_page, $page_number, $sort_order, $order_by, $find, $is_email, $expected_sql ) {

		if ( ! is_null( $sort_order ) ) {
			$_REQUEST['order'] = $sort_order;
		}

		if ( ! is_null( $find ) ) {
			$_REQUEST['find'] = $find;
		}

		Monkey\Functions\when( 'is_email' )
			->justReturn( $is_email );

		Monkey\Functions\when( 'sanitize_email' )
			->returnArg( 1 );

		$this->mc_class->get_members( $per_page, $page_number, $status );
		$resulting_sql = $this->mc_class->get( 'sql_query' );

		$this->assertEquals( $expected_sql, $resulting_sql );
		Monkey\Filters\applied( 'e20r_memberslist_sort_order' );
		Monkey\Filters\applied( 'e20r_memberslist_order_by' );
		Monkey\Filters\applied( 'e20r_members_list_default_sql_column_alias_map' );
		Monkey\Filters\applied( 'e20r_memberslist_tables_and_joins' );
	}

	/**
	 * The SQL parameter fixture for the generate_member_sql() unit test
	 *
	 * @return array[]
	 */
	public function fixture_member_sql_params() {
		return array(
			// phpcs:ignore
			// $status, $per_page, $page_number, $sort_order, $order_by, find, $is_email, $expected_sql
			array( 'active', -1, -1, 'DESC', 'ml.id', '', true, $this->fixture_sql_statement( 0 )
			),
		);
	}

	public function fixture_sql_statement( $counter ) {
		return array(
			0 => "
 SELECT
		mu.id AS record_id, u.ID AS ID, u.user_login AS user_login, u.user_email AS user_email, u.user_registered AS user_registered, mu.membership_id AS membership_id, mu.initial_payment AS initial_payment, mu.billing_amount AS billing_amount, mu.cycle_period AS cycle_period, mu.cycle_number AS cycle_number, mu.billing_limit AS billing_limit, mu.code_id AS code_id, mu.status AS status, mu.trial_amount AS trial_amount, mu.trial_limit AS trial_limit, mu.startdate AS startdate, mu.enddate AS enddate, ml.name AS name
			 FROM wp_users AS u
				LEFT JOIN wp_pmpro_memberships_users AS mu ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM wp_pmpro_memberships_users AS mu3 WHERE mu3.user_id = u.id ORDER BY mu3.id DESC LIMIT 1)
	LEFT JOIN wp_pmpro_membership_levels AS ml ON mu.membership_id = ml.id
	LEFT JOIN  wp_usermeta AS um ON u.ID = um.user_id

			 WHERE (mu.membership_id IS NOT NULL OR mu.membership_id > 0) AND  mu.status IN ('active')
			 GROUP BY u.ID, ml.id
			 ORDER BY ml.id DESC

",
			1 => "
 SELECT
		mu.id AS record_id, u.ID AS ID, u.user_login AS user_login, u.user_email AS user_email, u.user_registered AS user_registered, mu.membership_id AS membership_id, mu.initial_payment AS initial_payment, mu.billing_amount AS billing_amount, mu.cycle_period AS cycle_period, mu.cycle_number AS cycle_number, mu.billing_limit AS billing_limit, mu.code_id AS code_id, mu.status AS status, mu.trial_amount AS trial_amount, mu.trial_limit AS trial_limit, mu.startdate AS startdate, mu.enddate AS enddate, ml.name AS name
			 FROM wp_users AS u
				LEFT JOIN wp_pmpro_memberships_users AS mu ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM wp_pmpro_memberships_users AS mu3 WHERE mu3.user_id = u.id ORDER BY mu3.id DESC LIMIT 1)
	LEFT JOIN wp_pmpro_membership_levels AS ml ON mu.membership_id = ml.id
	LEFT JOIN  wp_usermeta AS um ON u.ID = um.user_id

			 WHERE (mu.membership_id IS NOT NULL OR mu.membership_id > 0) AND  mu.status IN ('active')
			 GROUP BY u.ID, ml.id
			 ORDER BY ml.id DESC

"
		);
	}
	/**
	 * @param $per_page
	 * @param $page_number
	 * @param $status
	 * @param $sort_order
	 * @param $order_by
	 * @param $find
	 * @param $is_email
	 *
	 * @dataProvider fixture_get_members
	 */
	public function test_get_members( $per_page, $page_number, $status, $sort_order, $order_by, $find, $is_email, $record_list ) {

		if ( ! is_null( $sort_order ) ) {
			$_REQUEST['order'] = $sort_order;
		}

		if ( ! is_null( $find ) ) {
			$_REQUEST['find']  = $find;
		}

		Monkey\Functions\when( 'is_email' )
			->justReturn( $is_email );

		Monkey\Functions\when( 'sanitize_email' )
			->returnArg( 1 );

		$this->mc_class->get_members( $per_page, $page_number, $status );

		Monkey\Filters\applied( 'e20r_memberslist_sort_order' );
		Monkey\Filters\applied( 'e20r_memberslist_order_by' );
		Monkey\Filters\applied( 'e20r_members_list_default_sql_column_alias_map' );
		Monkey\Filters\applied( 'e20r_memberslist_tables_and_joins' );

	}

	/**
	 * The SQL parameter fixture for the generate_member_sql() unit test
	 *
	 * @return array[]
	 */
	public function fixture_get_members() {
		return array(
			// phpcs:ignore
			// $per_page, $page_number, $status, $sort_order, $order_by, find, $is_email, $record_list
			array( -1, -1, 'active', 'DESC', 'ml.id', '', true, array() ),
		);
	}

	/**
	 * @param $level_id
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
	public function fixture_record_count_params() {
		return array(
			array( 1 ),
		);
	}

	public function test_process_bulk_action() {

	}

	public function test_get_columns() {

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
