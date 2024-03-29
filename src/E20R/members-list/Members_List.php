<?php
/**
 * License:

	Copyright 2016 - 2022 - Eighty / 20 Results by Wicked Strong Chicks, LLC (thomas@eighty20results.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package E20R\Members_List\Members_List
 */

namespace E20R\Members_List;

use E20R\Exceptions\InvalidSettingsKey;
use E20R\Licensing\Exceptions\BadOperation;
use E20R\Members_List\Admin\Bulk\Bulk_Cancel;
use E20R\Members_List\Admin\Bulk\Bulk_Update;
use E20R\Members_List\Admin\Exceptions\DBQueryError;
use E20R\Members_List\Admin\Exceptions\InvalidProperty;
use E20R\Members_List\Admin\Exceptions\InvalidSQL;
use E20R\Members_List\Admin\Export\Export_Members;
use E20R\Members_List\Admin\Modules\Multiple_Memberships;
use E20R\Members_List\Admin\Pages\Members_List_Page;
use E20R\Utilities\Cache;
use E20R\Utilities\Message;
use E20R\Utilities\Utilities;
use stdClass;
use WP_List_Table;
use WP_User;

if ( ! defined( 'ABSPATH' ) && ! defined( 'PLUGIN_PHPUNIT' ) ) {
	die( 'WordPress not loaded. Naughty, naughty!' );
}

if ( ! defined( 'E20R_ML_BASE_DIR' ) ) {
	define( 'E20R_ML_BASE_DIR', __FILE__ );
}

if ( ! class_exists( 'E20R\Members_List\Members_List' ) ) {

	/**
	 * WP_List_Table derived Members List class
	 */
	class Members_List extends WP_List_Table {

		// Local constant - number of seconds in a minute
		const MINUTE_IN_SECONDS = 60;

		/**
		 * Class instance variable (singleton support).
		 *
		 * @var Members_List|null $instance
		 */
		private static $instance;

		/**
		 * The table & condition section of the SQL.
		 *
		 * @var     string $sql_from
		 */
		private static $sql_from;

		/**
		 * The DB records (items) found
		 *
		 * @var null|mixed
		 */
		public $items = null;

		/**
		 * List of columns to fetch by default from DB
		 *
		 * @var array $sql_col_list
		 */
		private $sql_col_list = array();

		/**
		 * The default Members_List table columns
		 *
		 * @var array $default_columns
		 */
		private $default_columns = array();

		/**
		 * The columns that should be hidden in the table
		 *
		 * @var array $hidden_columns
		 */
		private $hidden_columns = array();

		/**
		 * The total number of records found after SQL
		 *
		 * @var null|int $total_members_found
		 */
		private $total_members_found = null;

		/**
		 * The total number of records in the PMPro Membership DB
		 *
		 * @var null|int $total_member_records
		 */
		private $total_member_records = null;

		/**
		 * The status of the membership when searching for totals
		 *
		 * @var null|string[] $membership_status
		 */
		private $membership_status = null;

		/**
		 * The current level status requested by the user (REQUEST)
		 *
		 * @var string $requested_status
		 */
		private $requested_status = 'active';
		/**
		 * The completed SQL query used to generate the membership list
		 *
		 * @var string $sql_query
		 */
		private $sql_query = '';
		/**
		 * Instance of the Utilities class
		 *
		 * @var Utilities $utils
		 */
		private $utils;

		/**
		 * Instance of the Members_List_Page() class
		 *
		 * @var Members_List_Page|null $page
		 */
		private $page;

		/**
		 * Various elements of the SQL query as it's being built.
		 *
		 * @var array $table_list
		 */
		private $table_list = array();

		/**
		 * The user requested search string
		 *
		 * @var string|null $user_search
		 */
		private $user_search = null;

		/**
		 * Search string
		 *
		 * @var null|string $search
		 */
		private $search = null;

		/**
		 * The ORDER BY portion of the SQL statement
		 *
		 * @var null|string $order_by
		 */
		private $order_by = null;

		/**
		 * The ORDER portion of the SQL statement
		 *
		 * @var string $order
		 */
		private $order = 'DESC';

		/**
		 * The LIMIT OFFSET part of the SQL statement
		 *
		 * @var null|int $offset
		 */
		private $offset = null;

		/**
		 * The LIMIT part of the SQL statement
		 *
		 * @var null|string $limit
		 */
		private $limit = null;

		/**
		 * The PMPro levels specific part of the SQL statement
		 *
		 * @var null|string $levels_sql
		 */
		private $levels_sql = null;

		/**
		 * The SQL search string
		 *
		 * @var null|string $find
		 */
		private $find = null;

		/**
		 * The WHERE clause for the SQL statement
		 *
		 * @var null|string $where
		 */
		private $where = null;

		/**
		 * The JOIN clauses for the SQL statement
		 *
		 * @var null|string $joins
		 */
		private $joins = null;

		/**
		 * The FROM clause for the SQL statement
		 *
		 * @var null|string $from
		 */
		private $from = null;

		/**
		 * The GROUP BY clause for the SQL statement
		 *
		 * @var null|string $group_by
		 */
		private $group_by = null;

		/**
		 * The Action parameter from the Bulk Action option
		 *
		 * @var string|null
		 */
		private $action = null;

		/**
		 * The date format to use for the members list
		 *
		 * @var string
		 */
		private $date_format = 'Y-M-D';

		/**
		 * Instance of the Bulk_Cancel class (action)
		 *
		 * @var null|Bulk_Cancel $cancel
		 */
		private $cancel = null;

		/**
		 * Instance of the Bulk_Update class (action)
		 *
		 * @var null|Bulk_Update $update
		 */
		private $update = null;

		/**
		 * Instance of the Export_Members class handling the export to CSV operation for this plugin.
		 *
		 * @var null|Export_Members $export
		 */
		private $export = null;

		/**
		 * List of date values we consider 'empty' (not confiured/set/defined)
		 *
		 * @var mixed|void|null $empty_date_values
		 */
		private $empty_date_values = array();

		/**
		 * Members_List constructor.
		 *
		 * @param Utilities|null         $utils An instance of the Utilities class.
		 * @param Members_List_Page|null $page An instance of the Members List Page rendering class
		 */
		public function __construct( $utils = null, $page = null ) {

			if ( empty( $utils ) ) {
				$message = new Message();
				$utils   = new Utilities( $message );
			}

			$this->utils       = $utils;
			self::$instance    = $this;
			$this->action      = $this->utils->get_variable( 'action', '' );
			$this->date_format = get_option( 'date_format' );

			// Default settings for what we consider 'empty' (not set) date values
			add_filter(
				'e20r_members_list_empty_date_values',
				array( $this, 'set_empty_date_values' ),
				-1,
				1
			);

			if ( empty( $page ) ) {
				$page = new Members_List_Page( $this->utils );
			}

			$this->page = $page;

			if ( 'e20rml_export_records' !== $this->action ) {
				parent::__construct(
					array(
						'singular' => esc_attr__( 'member', 'e20r-members-list' ),
						'plural'   => esc_attr__( 'members', 'e20r-members-list' ),
						'ajax'     => false,
					)
				);
			}

			$this->set_sql_columns();

			/**
			 * The default Members List columns to display (with labels)
			 */
			$this->default_columns = array(
				'cb'              => '<input type="checkbox" />',
				'user_login'      => esc_attr_x( 'Login', 'e20r-members-list' ),
				'first_name'      => esc_attr_x( 'First Name', 'e20r-members-list' ),
				'last_name'       => esc_attr_x( 'Last Name', 'e20r-members-list' ),
				'user_email'      => esc_attr_x( 'Email', 'e20r-members-list' ),
				'baddress'        => esc_attr_x( 'Billing Info', 'e20r-members-list' ),
				'name'            => esc_attr_x( 'Level', 'e20r-members-list' ),
				'fee'             => esc_attr_x( 'Fee', 'e20r-members-list' ),
				'code'            => esc_attr_x( 'Discount Code', 'e20r-members-list' ),
				'status'          => esc_attr_x( 'Status', 'e20r-members-list' ),
				'user_registered' => esc_attr_x( 'Joined', 'e20r-members-list' ),
				'startdate'       => esc_attr_x( 'Started', 'e20r-members-list' ),
				'last'            => esc_attr_x( 'Ends', 'e20r-members-list' ),
			);

			// Configure the defaults for the empty/not set date values
			$this->empty_date_values = apply_filters( 'e20r_members_list_empty_date_values', array() );

			if ( $this->is_module_enabled( 'pmpro-multiple-memberships-per-user/pmpro-multiple-memberships-per-user.php' ) ) {
				// TODO: Add actions to process data for the PMPro Multiple Memberships Per User add-on
				$this->utils->log( 'Enable the MMPU extensions' );
			}


			/**
			 * Prepare the Export bulk action
			 */
			if ( 'e20rml_export_records' === $this->action ) {
				$this->utils->log( 'Adding export handler. Headers ' . ( headers_sent() ? ' sent' : ' not sent' ) );
				$this->prepare_items();
				add_action( 'e20r_memberslist_process_action', array( $this, 'export_members' ), 10, 3 );
			}
		}

		/**
		 * Define the list of 'empty' values for the date check(s)
		 *
		 * @param array $values Received list of values
		 *
		 * @return array
		 */
		public function set_empty_date_values( $values = array() ) {
			return $values + array(
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
		 * Generate the SQL and set the sql_query member variable to use the query
		 *
		 * @param int  $per_page Number of records per page to return.
		 * @param int  $page_number The page number this query applies to (if paginated)
		 * @param bool $return_count Whether to return the full record count or not (i.e. ignore pagination)
		 *
		 * @throws InvalidSQL Raised when there's a problem generating valid SQL for the DB query
		 */
		public function generate_member_sql( $per_page = -1, $page_number = -1, $return_count = false ) {

			$this->utils->log( 'Called by: ' . $this->utils->who_called_me() );

			$default_level          = apply_filters( 'e20r_members_list_default_member_status', 'active' );
			$this->requested_status = trim( $this->utils->get_variable( 'level', $default_level ) );
			$this->user_search      = $this->utils->get_variable( 'find', null );

			// Default sort order and field (membership ID).
			$order = esc_sql( apply_filters( 'e20r_memberslist_sort_order', $this->utils->get_variable( 'order', 'DESC' ) ) );

			// Get the order_by value (probably the default, but...).
			$order_by = apply_filters( 'e20r_memberslist_order_by', array_map( 'trim', explode( ',', $this->utils->get_variable( 'orderby', 'ml.id, u.user_email' ) ) ) );

			// Get the group by value (probably the default, but...).
			$group_by = apply_filters( 'e20r_memberslist_group_by', array_map( 'trim', explode( ',', $this->utils->get_variable( 'groupby', 'ml.id, u.ID' ) ) ) );

			// Handle the 'last' column (which is really the enddate field when sorting/ordering).
			/* phpcs:ignore
					if ( in_array( 'last', $order_by ) ) {
						$order_by = array_map( function( $str ) {
									str_replace( 'last', 'enddate', $str );
								},
								$order_by
						);
					}

					// Handle the 'last' column if used as a group_by value
					if ( in_array( 'last', $group_by ) ) {
						$group_by = array_map( function( $str ) {
							str_replace( 'last', 'enddate', $str );
							},
								$group_by
						);
					}
			*/
			// Start the SELECT statement.
			$sql = 'SELECT
			';

			$this->set_last_column_name();

			// Add to the SQL statement.
			foreach ( $this->sql_col_list as $name => $alias ) {
				$sql .= "{$name} AS {$alias}, ";
			}

			// Clean up trailing comma from column list.
			$sql = rtrim( $sql, ', ' );

			// Add the tables to search (and configure JOIN operations) and.
			$this->set_tables_and_joins();

			// Error out if something is wrong here.
			if ( empty( $this->table_list ) ) {
				throw new InvalidSQL( esc_attr__( 'Error: Invalid list of tables & joins for member list!', 'e20r-members-list' ) );
			}

			if ( ! empty( $this->table_list['from'] ) ) {

				$this->from  = " FROM {$this->table_list['from']['name']}";
				$this->from .= ( empty( $this->table_list['from']['alias'] ) ? null : " AS {$this->table_list['from']['alias']}" );
			} else {
				throw new InvalidSQL( esc_attr__( 'Error: No FROM table specified for member list!', 'e20r-members-list' ) );
			}

			// Set the * JOIN statements (as necessary).
			foreach ( $this->table_list as $type => $config ) {
				if ( 'joins' === $type ) {

					// Avoid duplicate joins.
					if ( ! empty( $this->joins ) ) {
						$this->joins = '';
					}

					foreach ( $config as $k => $join ) {
						$this->joins .= "\t{$join['join_type']} {$join['name']} AS {$join['alias']} {$join['condition']} \n";
					}
				}
			}
			$this->set_membership_status();
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			$this->utils->log( 'Current membership level value: ' . print_r( $this->requested_status, true ) );
			$this->utils->log( "Value ({$this->requested_status}) is numeric? " . ( is_numeric( $this->requested_status ) ? 'Yes' : 'No' ) );

			$this->where = ' WHERE ';

			// We're searching for active members only (and not a specific user record).
			if ( empty( $this->user_search ) ) {
				$this->utils->log( 'No user/meta specific search requested' );

				// Looking for active members, and/or
				// members belonging to a specific membership level ID.
				if (
						'active' === $this->requested_status ||
						empty( $this->requested_status ) ||
						is_numeric( $this->requested_status )
				) {
					$this->utils->log( 'Adding the valid membership_id value check to SQL query' );
					// Make sure they have a valid membership ID stored.
					$this->where .= '(mu.membership_id IS NOT NULL OR mu.membership_id > 0) ';
				}
			}

			// Add the level specific portion of the WHERE statement.
			$this->levels_sql = $this->set_level_sql();

			// Is the user searching for something (meta value, user_login, email, start or end date.
			if ( ! empty( $this->user_search ) ) {
				$is_time = false;
				$this->utils->log( "Searching for: {$this->user_search}" );

				// Check if this is a date value.
				if ( ! is_numeric( $this->user_search ) && false !== strtotime( $this->user_search ) ) {

					$this->user_search = date_i18n( 'Y-m-d', strtotime( $this->user_search ) );
					$is_time           = true;
				}

				// Set up the search-for part of the query (i.e. user_login, usermeta, nicename,
				// dispay_name and user_email).
				$srch_str = esc_sql( sanitize_text_field( $this->user_search ) );

				$user_table_search = apply_filters(
					'e20r_memberslist_search_user_fields',
					array(
						'user_login',
						'user_nicename',
						'display_name',
						'user_email',
					)
				);

				$meta_table_fields = apply_filters( 'e20r_memberslist_search_usermeta_fields', array( 'meta_value' ) );

				// Start the search portion of the SQL statement.
				$this->find = sprintf(
					" ( u.%s LIKE '%%%s%%' ",
					array_shift( $user_table_search ),
					$srch_str
				);

				// Add all user table fields to search by.
				foreach ( $user_table_search as $idx => $field_name ) {
					$this->find .= "OR u.{$field_name} LIKE '%{$srch_str}%' ";
				}

				// Handle SQL if there's no user table fields include.
				if (
						! empty( $meta_table_fields ) &&
						0 === preg_match( '/ OR /', $this->find ) &&
						0 === preg_match( '/\( /', $this->find )
				) {
					$this->find = sprintf(
						" ( um.%s LIKE '%%%s%%'",
						array_shift( $meta_table_fields ),
						$srch_str
					);
				} elseif (
						! empty( $meta_table_fields ) &&
						0 === preg_match( '/ OR /', $this->find ) &&
						1 === preg_match( '/\( /', $this->find )
				) {
					$this->find = sprintf(
						" ( um.%s LIKE \'%%%s%%\'",
						array_shift( $meta_table_fields ),
						$srch_str
					);
				}

				// Add all/any metadata fields to search by
				// Frankly surprising if this is more than the meta_value field..
				foreach ( $meta_table_fields as $field_name ) {
					$this->find .= "OR um.meta_value LIKE '%{$srch_str}%' ";
				}

				// Search for records by start-date or end-date.
				if ( true === $is_time && 'desc' === strtolower( $order ) ) {
					$this->find .= "OR mu.startdate >= '{$srch_str} 00:00:00' OR mu.enddate >= '{$srch_str} 00:00:00' ";
				}

				if ( true === $is_time && 'asc' === strtolower( $order ) ) {
					$this->find .= "OR mu.startdate <= '{$srch_str} 00:00:00' OR mu.enddate <= '{$srch_str} 23:59:59' ";
				}

				$this->find .= ') ';
			}

			// Append any search & level info to the WHERE statement.
			if ( ! empty( $this->find ) || ! empty( $this->levels_sql ) ) {

				if ( ! empty( $this->find ) ) {
					$this->where .= $this->find;
				}

				if ( ! empty( $this->levels_sql ) ) {
					$this->where .= $this->levels_sql;
				}
			}

			$this->where = apply_filters(
				'e20r_memberslist_sql_where_statement',
				$this->where,
				$this->find,
				$this->levels_sql,
				$this->joins
			);

			$this->group_by = sprintf( ' GROUP BY %1$s', implode( ',', $group_by ) );
			$this->order_by = sprintf( ' ORDER BY %1$s %2$s', is_array( $order_by ) ? implode( ',', $order_by ) : $order_by, $order );
			$this->group_by = apply_filters( 'e20r_memberslist_group_by_statement', $this->group_by, $group_by );
			$this->order_by = apply_filters( 'e20r_memberslist_order_by_statement', $this->order_by, $order_by, $order );
			$per_page       = apply_filters( 'e20r_memberslist_per_page', $per_page );

			if ( ! is_numeric( $per_page ) ) {
				// translators: %1$s is the per-page value supplied by the e20r_memberslist_per_page filter.
				throw new InvalidSQL( sprintf( esc_attr__( 'Error: Invalid \'per_page\' value (need an integer): %1$s', 'e20r-members-list' ), $per_page ) );
			}

			if ( false === $return_count && -1 !== $per_page ) {
				$this->utils->log( "Adding pagination values ({$per_page} and page: {$page_number}" );
				$this->offset = ( $page_number - 1 ) * $per_page;
				$this->limit  = sprintf( ' LIMIT %1$d OFFSET %2$d ', $per_page, $this->offset );
			} else {
				$this->limit = null;
			}

			// Construct the tail end of the SQL statement.
			self::$sql_from = "
				{$this->from}
				{$this->joins}
				{$this->where}
				{$this->group_by}
				{$this->order_by}
				{$this->limit}
			";

			// Created the SQL statement.
			$this->sql_query = $sql . self::$sql_from;

			$this->utils->log( "SQL for fetching membership records:\n {$this->sql_query}" );
		}

		/**
		 * Set request status(es) to return records for
		 */
		private function set_membership_status() {

			$cancelled_statuses = apply_filters(
				'e20r_memberslist_cancelled_statuses',
				array(
					'cancelled',
					'admin_cancelled',
					'admin_change',
					'admin_changed',
					'changed',
					'inactive',
				)
			);
			$active_statuses    = apply_filters( 'e20r_memberslist_active_statuses', array( 'active' ) );
			$expired_statuses   = apply_filters( 'e20r_memberslist_expired_statuses', array( 'expired' ) );

			switch ( $this->requested_status ) {
				case 'all':
					$this->membership_status = array_merge( $cancelled_statuses, $active_statuses, $expired_statuses );
					break;
				case 'cancelled':
					$this->membership_status = $cancelled_statuses;
					break;
				case 'expired':
					$this->membership_status = $expired_statuses;
					break;
				case 'oldmembers':
					$this->membership_status = array_merge( $expired_statuses, array( 'cancelled' ) );
					break;
				default:
					$this->membership_status = $active_statuses;
			}

		}
		/**
		 * Set the Membership level (status) to display records for
		 *
		 * @return string
		 */
		private function set_level_sql() {
			$levels_sql = '';
			$this->utils->log( "Will return records for membership level: {$this->requested_status}" );

			if (
					( ! empty( $this->where ) && ' WHERE ' !== $this->where ) ||
					// phpcs: ignore Squiz.PHP.CommentedOutCode.Found
					// ( 'all' === $this->requested_status ) ||
					! empty( $this->user_search )
			) {
				$this->utils->log( "Start Level SQL portion of WHERE statement with 'AND' keyword" );
				$levels_sql .= 'AND ';
			}

			// Expand the status values (for the SQL query).
			$statuses    = implode( "','", array_map( 'sanitize_text_field', $this->membership_status ) );
			$levels_sql .= "mu.status IN ('{$statuses}') ";

			if ( in_array( $this->requested_status, array( 'expired', 'cancelled', 'oldmembers' ), true ) ) {
				$levels_sql .= ' AND mu2.status IS NULL ';
			}

			// Add the membership ID if.
			if ( is_numeric( $this->requested_status ) ) {
				$this->utils->log( 'Processing a numeric membership level (ID)' );
				$levels_sql .= sprintf( ' AND mu.membership_id = %1$d ', esc_sql( $this->requested_status ) );
			}

			return $levels_sql;
		}

		/**
		 * Configure the column name to use for the final column in the table list
		 */
		public function set_last_column_name() {

			if ( ! empty( $this->default_columns ) ) {

				/**
				 * Should the final column use 'Expired' or 'Expires' as the label
				 */
				if ( 'oldmembers' === $this->requested_status ) {
					$this->default_columns['last'] = apply_filters( 'e20r_members_list_enddate_col_name', esc_attr_x( 'Expired', 'e20r-members-list' ), $this->requested_status );
				} else {
					$this->default_columns['last'] = apply_filters( 'e20r_members_list_enddate_col_name', esc_attr_x( 'Expires', 'e20r-members-list' ), $this->requested_status );
				}

				// For backwards compatibility.
				/**
				 * Old: e20r-members-list-enddate-col-name, new: e20r_members_list_enddate_col_name
				 *
				 * @deprecated
				 */
				// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
				$this->default_columns['last'] = apply_filters( 'e20r-members-list-enddate-col-name', $this->default_columns['last'], $this->requested_status );
			}
		}

		/**
		 * Private function to capture the count of records in the membership database
		 *
		 * @return int|null
		 * @throws InvalidSettingsKey|BadOperation Raised if we can't find the Members_List_Page() class property
		 */
		public function get_member_record_count() {

			// Get SQL for all records in the paginated data.
			try {
				$this->generate_member_sql( -1, -1, true );
			} catch ( InvalidSQL $e ) {
				$this->utils->add_message(
					sprintf(
						// translators: %1$s - The error message returned by the exception thrown
						esc_attr__(
							'Cannot create a valid Database Query. Error message: %1$s',
							'e20r-members-list'
						),
						$e->getMessage()
					),
					'error',
					'backend'
				);
			}

			// Generate a cache key from the SQL query (md5 checksum) and try to fetch a cached value for that key
			$total_count_cache_key   = md5( $this->sql_query );
			$total_count_cache_group = $this->page->get( 'total_count_cache_group' );
			$cached_count            = Cache::get( $total_count_cache_key, $total_count_cache_group );
			$cache_timeout           = ( (int) $this->page->get( 'cache_timeout' ) * self::MINUTE_IN_SECONDS );

			if ( null === $cached_count ) {
				$this->utils->log( 'Cache miss for total record count number. Need to query DB directly' );
				try {
					$results = $this->get_members( -1, -1, true );
				} catch ( DBQueryError | InvalidSQL $e ) {
					$this->utils->add_message(
						sprintf(
							// translators: %1$s - The error message returned by the exception thrown
							esc_attr__(
								'Cannot execute database query. Error message: %1$s',
								'e20r-members-list'
							),
							$e->getMessage()
						),
						'error',
						'backend'
					);
					$results = null;
				}

				$cached_count = is_countable( $results ) ? count( $results ) : 0;

				$this->utils->log( 'Attempting to cache the results for total # of records found.' );
				if ( false === Cache::set( $total_count_cache_key, $cached_count, $cache_timeout, $total_count_cache_group ) ) {
					$this->utils->log( 'Error saving cache!' );
				}
			} else {
				$this->utils->log( "Cache hit for total record count number! Returning {$cached_count} records" );
			}

			$this->total_member_records = (int) $cached_count;
			$this->sql_query            = '';

			$this->utils->log(
				"We're searching? " . ( empty( $this->find ) ? 'No' : 'Yes' ) .
				" We're focusing on a level? " . ( empty( $this->requested_status ) ? 'No' : 'Yes' ) .
				" Records found: {$this->total_member_records}"
			);
			return $this->total_member_records;
		}

		/**
		 * Default list of columns to fetch.
		 *
		 * @return array - Array of SQL table columns/aliases to use when selecting data for membership list.
		 */
		public function set_sql_columns() {

			// Note: Format for the record array 'name' => 'alias'.
			$this->sql_col_list = array(
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

			/**
			 * The default mapping of DB columns (<table alias>.<column name>) to their respective alias(s)
			 *
			 * @filter e20r_members_list_default_sql_column_alias_map
			 * @param string[] $sql_col_list
			 */
			$this->sql_col_list = apply_filters( 'e20r_sql_column_alias_map', $this->sql_col_list );
			return $this->sql_col_list;
		}

		/**
		 * Creates or returns an instance of the PMPro_Approvals class.
		 *
		 * @return  Members_List A single instance of this class.
		 */
		public static function get_instance() {

			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Handle data query & filter, sorting, and pagination.
		 */
		public function prepare_items() {

			$this->utils->log( 'Loading column headers' );

			// Configure the column headers.
			$this->_column_headers = array(
				$this->all_columns(),
				$this->get_hidden_columns(),
				$this->get_sortable_columns(),
			);

			$should_export = $this->utils->get_variable( 'action', '' );

			if ( false !== $this->current_action() || 'e20rml_export_records' === $should_export ) {

				$this->utils->log( 'Trigger bulk action(s)/export' );

				// Handle bulk & save actions.
				$this->process_bulk_action();
			}

			// How many rows per page.
			$per_page = $this->get_items_per_page( 'per_page', 15 );

			// Allow programmers to define a different default membership status when user kicks off the search.
			$default_status = apply_filters( 'e20r_members_list_default_member_status', 'active' );

			// Do we need to limit?
			$this->requested_status = trim( $this->utils->get_variable( 'level', $default_status ) );

			// Get the current page number.
			$current_page = $this->get_pagenum();

			$this->utils->log( 'Fetch records from DB' );
			// Load  & count records.
			try {
				$this->generate_member_sql( $per_page, $current_page );
				$this->items                = $this->get_members( $per_page, $current_page );
				$this->total_member_records = $this->get_member_record_count();
			} catch ( DBQueryError | InvalidSQL $e ) {
				$this->utils->log( 'Error: ' . $e->getMessage() );
				$this->items                = null;
				$this->total_member_records = 0;
			}

			// BUG FIX: Handle situation(s) where there are no records found.
			if ( null !== $this->items ) {
				$this->utils->log(
					sprintf(
						'Configure pagination for %1$d total records and %2$d counted (returned) records',
						(int) $this->total_member_records,
						( is_countable( $this->items ) ? count( $this->items ) : 0 )
					)
				);
			}

			// Configure pagination.
			$this->set_pagination_args(
				array(
					'total_items' => (int) $this->total_member_records,
					'per_page'    => $per_page,
					'total_pages' => ceil( (int) $this->total_member_records / $per_page ),
				)
			);

		}

		/**
		 * Default columns to use for Member Listing (filterable)
		 *
		 * @return array
		 */
		public function all_columns() {

			$columns = $this->default_columns;

			/**
			 * Add/remove columns from the members list.
			 *
			 * @filter 'e20r_members_list_add_to_default_table_columns'
			 *
			 * @param array $new_columns - New columns to add to the WP_List_Table output
			 * @param array $columns     - List of existing/default columns
			 */
			$new_columns = apply_filters( 'e20r_memberslist_columnlist', array(), $columns );
			$new_columns = apply_filters( 'e20r_members_list_add_to_default_table_columns', $new_columns, $columns );
			$prepend     = apply_filters( 'e20r_members_list_page_prepend_cols', false );

			$columns = $this->default_columns + $new_columns;

			if ( true === $prepend ) {
				$columns = $new_columns + $this->default_columns;
			}

			return $columns;
		}

		/**
		 * Return list of columns that should be hidden by default
		 *
		 * @return array
		 */
		public function get_hidden_columns() {

			global $current_user;

			$columns_to_hide = get_user_meta( $current_user->ID, 'managememberships_page_pmpro-memberslistcolumnshidden', true );

			if ( empty( $columns_to_hide ) ) {
				$this->hidden_columns = array( 'baddress', 'status', 'code_id' );
			} else {

				if ( ! in_array( 'code_id', $columns_to_hide, true ) ) {
					$columns_to_hide[] = 'code_id';
				}

				$this->hidden_columns = $columns_to_hide;
			}

			// For backwards compatibility.
			$this->hidden_columns = apply_filters( 'e20r_memberslist_hidden_columns', $this->hidden_columns );

			return apply_filters( 'e20r_members_list_hidden_columns', $this->hidden_columns );
		}

		/**
		 * Columns to make sortable.
		 *
		 * @return array
		 */
		public function get_sortable_columns() {

			$columns          = $this->all_columns();
			$sortable_columns = array();

			foreach ( array_keys( $columns ) as $col ) {

				// Some of the default columns are sortable.
				switch ( $col ) {
					case 'display_name':
					case 'user_login':
					case 'user_email':
					case 'name':
					case 'startdate':
					case 'status':
					case 'last':
						$sortable_columns[ $col ] = array( $col, false );
						break;
				}
			}

			// Filter the return value so other plugins can change this behavior.
			return apply_filters( 'e20r_memberslist_sortable_columns', $sortable_columns );
		}

		/**
		 * Process actions from member list form
		 */
		public function process_bulk_action() {

			$a    = $this->utils->get_variable( 'action', null );
			$a2   = $this->utils->get_variable( 'action2', null );
			$page = $this->utils->get_variable( 'page', '' );

			if ( null === $a && null === $a2 ) {
				$this->utils->log( 'No bulk action to execute' );
				return;
			}

			if ( 'e20r-memberslist' !== $page ) {
				$this->utils->log( 'Not on the Members List page, so nothing to do' );
				return;
			}

			// Are we processing a bulk action?
			if (
				( null !== $a && 1 === preg_match( '/bulk-/', $a ) ) ||
				( null !== $a2 && 1 === preg_match( '/bulk-/', $a2 ) )
			) {

				$this->utils->log( 'Processing a bulk action' );

				// Process any plugin/add-on bulk actions first.
				// In our file that handles the request, verify the nonce.
				$nonce = esc_attr( $this->utils->get_variable( '_wpnonce', null ) );

				if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
					$this->utils->add_message(
						esc_attr__( 'Insecure action denied.', 'e20r-members-list' ),
						'error',
						'backend'
					);

					return;
				}

				$level_id         = $this->utils->get_variable( 'membership_id', array() );
				$action           = $this->current_action();
				$data             = array();
				$selected_members = $this->utils->get_variable( 'member_user_ids', array() );

				foreach ( $selected_members as $key => $user_id ) {
					$user_level = $this->utils->get_variable( "e20r-members-list-membership_id_{$user_id}", 0 );
					$data[]     = array(
						'user_id'  => $user_id,
						'level_id' => $user_level,
					);
				}

				$bulk_actions = array( $a, $a2 );

				// Prepare plugin specific data for member list bulk action processing.
				$data = apply_filters( 'e20r_memberslist_bulk_action_data_array', $data, $action, $level_id );

				if ( in_array( 'bulk-cancel', $bulk_actions, true ) ) {
					try {
						$this->cancel = new Bulk_Cancel( $data, $this->utils );
						$this->cancel->execute();
					} catch ( InvalidProperty $e ) {
						$this->utils->add_message(
							sprintf(
								// translators: %1$s - Error message from exception
								esc_attr__( 'Bulk Cancel: %1$s', 'e20r-members-list' ),
								$e->getMessage()
							),
							'error',
							'backend'
						);
					}

					return;

				} elseif ( in_array( 'bulk-export', $bulk_actions, true ) ) {

					$this->utils->log( 'Requested Export of members!' );
					$this->export_members();

					// To push the export file to the browser, we have to terminate execution of this process.
					$this->utils->log( 'Returned from export_members(). That\'s unexpected!' );

					// We should never get here.
					return;

				} elseif ( in_array( 'bulk-update', $bulk_actions, true ) ) {

					$this->utils->log( 'Requested member updates for ' . count( $data ) . ' records' );

					$this->update = new Bulk_Update( $data, $this->utils );
					try {
						$this->update->execute();
					} catch ( InvalidProperty $e ) {
						$this->utils->log( 'Error: ' . $e->getMessage() );
					}
				} else {
					$this->utils->log( 'About to trigger any custom defined bulk actions' );
					// Process member list bulk action in add-ons/plugins.
					do_action( 'e20r_memberslist_process_custom_bulk_actions', $nonce, $action, $bulk_actions, $data, $this->_args['plural'] );
				}
			} else {

				$this->utils->log( 'Single action for the Members List...' );

				$user_id                     = $this->utils->get_variable( 'member_user_ids', array() );
				$level_id                    = $this->utils->get_variable( 'membership_id', array() );
				$action                      = $this->current_action();
				$membership_levels_to_cancel = $this->utils->get_variable( 'membership_level_ids', array() );

				switch ( $action ) {

					case 'cancel':
						$user_ids = array(
							array(
								'user_id'              => $user_id,
								'level_id'             => $level_id,
								'membership_level_ids' => $membership_levels_to_cancel,
							),
						);

						if ( empty( $this->cancel ) ) {
							$this->cancel = new Bulk_Cancel( $user_ids, $this->utils );
						}

						if ( false === $this->cancel->execute() ) {
							$message = esc_attr__( 'Error cancelling membership(s)', 'e20r-members-list' );
							$this->utils->add_message( $message, 'error', 'backend' );

							if ( function_exists( 'pmpro_setMessage' ) ) {
								pmpro_setMessage( $message, 'error' );
							} else {
								global $msg;
								global $msgt;

								$msg  = esc_attr__( 'Error cancelling membership(s)', 'e20r-members-list' );
								$msgt = 'error';
							}
						}

						break;

					default:
						$this->utils->log( 'Trigger external process_action for memberslist' );
						// Process add-on Members list actions.
						do_action( 'e20r_memberslist_process_action', $action, $user_id, $level_id );
				}

				// Reload & whatnot
				// wp_redirect( esc_url( add_query_arg() ) );
				// exit;
			}
		}

		/**
		 * Export function bulk export action or for the "Export to CSV" button
		 *
		 * @param null|string $action The list page action being performed.
		 * @param null|int    $user_id The WP User ID to export data for.
		 * @param null|int    $level_id The membership level ID to export data for.
		 */
		public function export_members( $action = null, $user_id = null, $level_id = null ) {

			$this->utils->log( 'Called by: ' . $this->utils->who_called_me() );

			$this->set_membership_status();
			/* phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			$default_level   = apply_filters( 'e20r_members_list_default_member_status', 'active' );
			$this->requested_status = trim( $this->utils->get_variable( 'level', $default_level ) );
			*/

			$this->utils->log( 'Content sent...?' . ( headers_sent() ? 'Yes' : 'No' ) );
			/* phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			if ( empty( $this->requested_status ) ) {
				$this->requested_status = 'active';
			}
			*/

			add_filter( 'e20r_memberslist_sql_where_statement', array( $this, 'export_member_where' ), 20, 4 );
			add_filter( 'e20r_memberslist_sort_order', array( $this, 'export_sort_order' ), 10, 1 );
			add_filter( 'e20r_memberslist_order_by', array( $this, 'export_order_by' ), 10, 1 );

			try {
				$this->generate_member_sql( - 1, - 1, false );
			} catch ( InvalidSQL $e ) {
				$msg = sprintf(
					// translators: %1$s - Error message from thrown exception(s)
					esc_attr__(
						'Cannot export member data! Error message: %1$s',
						'e20r-members-list'
					),
					$e->getMessage()
				);
				$this->utils->add_message( $msg, 'error', 'backend' );
				return;
			}

			try {
				$members_to_export = $this->get_members( - 1, -1 );
			} catch ( DBQueryError | InvalidSQL $e ) {
				$msg = sprintf(
						// translators: %1$s - Error message from thrown exception(s)
					esc_attr__(
						'Cannot export member data! Error message: %1$s',
						'e20r-members-list'
					),
					$e->getMessage()
				);
				$this->utils->add_message( $msg, 'error', 'backend' );
				return;
			}

			if ( empty( $this->export ) ) {
				$this->export = new Export_Members( $members_to_export );
			}
			$this->export->get_data_list();
			$this->export->save_data_for_export();
			$this->export->return_content();
		}

		/**
		 * Load member data for listing
		 *
		 * @param int  $per_page     The number of records to display per page.
		 * @param int  $page_number  The page number we're on to calculate the offset from.
		 * @param bool $return_count Only return the count of records.
		 *
		 * @return array|null|object|int
		 *
		 * @throws InvalidSQL Raised when there's a problem with the SQL we generated.
		 * @throws DBQueryError Raised if the DB Query reports a problem
		 */
		public function get_members( $per_page = null, $page_number = null, $return_count = false ) {

			if ( null === $per_page ) {
				$per_page = -1;
			}

			$result_cache_group = $this->page->get( 'result_cache_group' );
			try {
				$cache_timeout = ( (int) $this->page->get( 'cache_timeout' ) * self::MINUTE_IN_SECONDS );
			} catch ( InvalidSettingsKey $e ) {
				$this->utils->log( 'Error: cache_timeout is not a valid settings key!' );
				$cache_timeout = 10 * self::MINUTE_IN_SECONDS;
			}

			global $wpdb;

			if ( empty( $this->sql_query ) || 1 !== preg_match_all( '/SELECT\s+.*\s+FROM(\s+(.*)){1,}/im', $this->sql_query ) ) {
				$this->utils->log( 'Error in/Missing SQL statement! "' . $this->sql_query . '"' );
				throw new InvalidSQL( 'Attempting to fetch data without a valid SQL query!' );
			}

			// Generate the Cache key based on
			$lookup_cache_key = md5( $this->sql_query );
			try {
				$result = Cache::get( $lookup_cache_key, $result_cache_group );
			} catch ( BadOperation $e ) {
				$this->utils->log( 'Error returning cached results for ' . $lookup_cache_key );
				$result = null;
			}

			if ( null === $result ) {
				$this->utils->log( "Cache miss. Query database for {$lookup_cache_key}/{$result_cache_group}" );

				// Fetch the data (The SQL query is prepared before we get here)
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
				$result = $wpdb->get_results( $this->sql_query, ARRAY_A );

				// Return the result set unless an error occurred.
				if ( ! empty( $result ) ) {
					$this->utils->log( 'Found records in DB...' );
					$order    = esc_sql( apply_filters( 'e20r_memberslist_sort_order', $this->utils->get_variable( 'order', 'DESC' ) ) );
					$order_by = esc_sql( apply_filters( 'e20r_memberslist_order_by', $this->utils->get_variable( 'orderby', 'ml.id' ) ) );

					if ( ! in_array( $order_by, array_keys( $this->sql_col_list ), true ) ) {
						$this->utils->log( "3rd Party sort of the returned records by {$order_by}/{$order} and " . count( $result ) . ' records' );
						$result = apply_filters( 'e20r_memberslist_sort_filter', $result, $order_by, $order, $page_number, $per_page );
					}

					$this->utils->log( ' Will return ' . count( $result ) . ' records' );
				}

				// Save the result to the cache based on the cache specific key
				$this->utils->log( "Attempting to cache the results for {$lookup_cache_key}" );
				try {
					if ( false === Cache::set( $lookup_cache_key, $result, $cache_timeout, $result_cache_group ) ) {
						$this->utils->log( 'Error saving the result to cache!' );
					}
				} catch ( BadOperation $e ) {
					$this->utils->log( 'Error: Unable to save to cache for ' . $lookup_cache_key );
				}
			} else {
				$this->utils->log( "Cache hit. Queried the DB for {$lookup_cache_key}/{$this->page->get( 'result_cache_group' )}" );
			}

			$error_msg = $wpdb->print_error();
			if ( empty( $result ) && ! empty( $error_msg ) ) {
				$msg = sprintf(
						// translators: %1$s query string.
					esc_attr__(
						'Error processing Members List database query: %1$s',
						'e20r-members-list'
					),
					$error_msg
				);

				$this->utils->add_message(
					$msg,
					'error',
					'backend'
				);

				throw new DBQueryError( $msg );
			}

			return $result;
		}

		/**
		 * Getter for member variables
		 *
		 * @param null|string $member_var The class parameter to return the value of.
		 *
		 * @return Members_List
		 * @throws InvalidProperty Raised when the parameter isn't defined in the class.
		 */
		public function get( $member_var = null ) {

			// Make sure we let the caller know there's a problem if the variable doesn't exist.
			if ( ! property_exists( $this, $member_var ) ) {
				throw new InvalidProperty(
					sprintf(
							// translators: %1$s - The class parameter, %2$s - The class name where we expect said parameter to exist.
						esc_attr__( '%1$s is not a member variable in %2$s', 'e20r-members-list' ),
						$member_var,
						__CLASS__
					)
				);
			}

			return $this->{$member_var};
		}

		/**
		 * Define the list of tables & joins we need to process for the member list query
		 *
		 * @return array|mixed
		 */
		public function set_tables_and_joins() {

			global $wpdb;

			// The default table and search (joins) definitions we use.
			$this->table_list = array(
				'from'  => array(
					'name'  => $wpdb->users,
					'alias' => 'u',
				),
				'joins' => array(
					0 => array(
						'name'      => $wpdb->pmpro_memberships_users,
						'join_type' => 'LEFT JOIN',
						'alias'     => 'mu',
						'condition' => "ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM {$wpdb->pmpro_memberships_users} AS mu3 WHERE mu3.user_id = u.ID ORDER BY mu3.id DESC LIMIT 1)",
					),
					1 => array(
						'name'      => $wpdb->pmpro_membership_levels,
						'join_type' => 'LEFT JOIN',
						'alias'     => 'ml',
						'condition' => 'ON mu.membership_id = ml.id',
					),
					2 => array(
						'name'      => $wpdb->usermeta,
						'join_type' => 'LEFT JOIN',
						'alias'     => 'um',
						'condition' => 'ON u.ID = um.user_id',
					),
				),
			);

			// We're searching so need to add the usermeta table.
			/* phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			if ( null !== $this->utils->get_variable( 'find', null ) ) {
			$this->table_list['joins'][2] = array(
				'name'      => $wpdb->usermeta,
				'join_type' => 'LEFT JOIN ',
				'alias'     => 'um',
				'condition' => 'ON u.ID = um.user_id',
			);
			} */

			$default_level          = apply_filters( 'e20r_members_list_default_member_status', 'active' );
			$this->requested_status = trim( $this->utils->get_variable( 'level', $default_level ) );

			// We're looking for a specific membership level.
			if (
					in_array( $this->requested_status, array( 'oldmembers', 'expired', 'cancelled', 'all' ), true )
					|| is_numeric( $this->requested_status )
			) {
				$this->table_list['joins'][3] = array(
					'name'      => $wpdb->pmpro_memberships_users,
					'join_type' => 'LEFT JOIN',
					'alias'     => 'mu2',
					'condition' => "ON u.ID = mu2.user_id AND mu2.status = 'active'",
				);
			}

			/**
			 * Filter to change/modify the tables and joins list. Will get (partially) validated, so sp
			 */
			$this->table_list = apply_filters( 'e20r_memberslist_tables_and_joins', $this->table_list );

			if ( false === $this->is_valid_tnj_list( $this->table_list ) ) {
				$this->utils->log( 'Error: Invalid database configuration!!!' );
				$this->utils->add_message(
					esc_attr__( 'Error: Invalid database configuration!!!', 'e20r-members-list' ),
					'error',
					'background'
				);
				return null;
			}

			return $this->table_list;
		}

		/**
		 * Test validity of the table & join list.
		 *
		 * @param array $list The list of tables to search.
		 *
		 * @return bool     Successfully validated list of tables & joined tables.
		 * @filter e20r_memberslist_verify_table_join_array - Allow validation of custom from & join options
		 */
		public function is_valid_tnj_list( $list ) {

			$from_n_joins = array_keys( $list );

			// Make sure we will have a "FROM <table>" statement.
			if ( ! in_array( 'from', $from_n_joins, true ) ) {
				$this->utils->log( 'Missing the "FROM" statement' );
				$this->utils->add_message(
					esc_attr__( 'Missing the "FROM" statement', 'e20r-members-list' ),
					'error',
					'background'
				);
				return false;
			}

			// Make sure there is at least one *JOIN* statement
			if ( ! in_array( 'joins', $from_n_joins, true ) ) {
				$this->utils->log( 'Missing the minimum expected number of "JOIN" statements' );
				$this->utils->add_message(
					esc_attr__( 'Missing the minimum expected number of "JOIN" statements', 'e20r-members-list' ),
					'error',
					'background'
				);
				return false;
			}

			// By default, we need at least 3 JOINs to search in user metadata, membership info, and membership level info.
			if ( 3 > count( $list['joins'] ) ) {
				$this->utils->log(
					sprintf(
						'Have %1$d JOIN entries, but need at least 3',
						count( $list['joins'] )
					)
				);
				$this->utils->add_message(
					sprintf(
								// translators: %1$d the number of JOIN entries in the Meembers_List::table_list definition
						esc_attr__( 'Have %1$d JOIN entries, but need at least 3!', 'e20r-members-list' ),
						count( $list['joins'] )
					),
					'error',
					'background'
				);
				return false;
			}

			// If we have the 'level' REQUEST parameter set, we have more checks to run.
			$default_level = apply_filters( 'e20r_members_list_default_member_status', 'active' );
			$search_level  = trim( $this->utils->get_variable( 'level', $default_level ) );
			if (
					in_array( $search_level, array( 'oldmembers', 'expired', 'cancelled', 'all' ), true )
					|| is_numeric( $search_level )
			) {

				// We need 4 JOINs to add the level ID or pre-defined block of levels to the search.
				if ( ! isset( $list['joins'][3] ) ) {
					$this->utils->log( 'Does not include the "JOIN" to support membership level search, but specified a level' );
					$this->utils->add_message(
						esc_attr__( 'Does not include the "JOIN" to support membership level search, but specified a level', 'e20r-members-list' ),
						'error',
						'background'
					);

					return false;
				}

				// We expect the 3rd join to represent the membership info.
				if ( "ON u.ID = mu2.user_id AND mu2.status = 'active'" !== $list['joins'][3]['condition'] ) {
					$this->utils->log( 'Unexpected condition value for the 4th "JOIN" element' );
					$this->utils->add_message(
						esc_attr__( 'Unexpected condition value for the 4th "JOIN" element', 'e20r-members-list' ),
						'error',
						'background'
					);
					return false;
				}

				global $wpdb;

				// It needs to include the pmpro_memberships_users table.
				if ( $list['joins'][3]['name'] !== $wpdb->pmpro_memberships_users ) {
					$this->utils->add_message(
						sprintf(
									// translators: %1$s table name defined by PMPro, %2$s Unexpected table name from user/developer
							esc_attr__( 'Unexpected table name value for the 4th "JOIN" element. Want: "%1$s", got: "%2$s"', 'e20r-members-list' ),
							$wpdb->pmpro_memberships_users,
							$list['joins'][3]['name']
						),
						'error',
						'background'
					);
					return false;
				}
			}

			/**
			 * Return the status after executing custom table/join check(s)
			 *
			 * @param bool $status - The default status after we've passed all the default checks
			 * @param array $list - List of tables & conditions for joins & the FROM table to use
			 *
			 * @filter e20r_memberslist_verify_table_join_array
			 */
			return apply_filters( 'e20r_memberslist_verify_table_join_array', true, $list );
		}

		/**
		 * Calculate pagination data (number of records found (total) based on the SQL used)
		 *
		 * @deprecated See https://wpartisan.me/tutorials/wordpress-database-queries-speed-sql_calc_found_rows
		 *
		 * @credit https://wpartisan.me/tutorials/wordpress-database-queries-speed-sql_calc_found_rows
		 *
		 * @return int
		 */
		public function record_count() {

			if ( ! is_null( $this->total_members_found ) ) {
				return $this->total_members_found;
			}

			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_var( 'SELECT FOUND_ROWS() AS found_rows' );
		}

		/**
		 * When exporting the members list, order by user's membership ID and email address (alphabetically)
		 *
		 * @param string $order_by The order by clause.
		 *
		 * @uses string e20r_memberslist_export_sort_order - Filter to return comma separated list of DB fields
		 *
		 * @return string
		 */
		public function export_order_by( $order_by ) {
			return apply_filters( 'e20r_memberslist_export_sort_order', 'mu.membership_id, u.user_email' );
		}

		/**
		 * Use A-Z sort order
		 *
		 * @param string $sort_order DB column sort order - Default: 'ASC'.
		 *
		 * @return string
		 */
		public function export_sort_order( $sort_order = 'ASC' ) {
			/**
			 * Filter to set the default sort order for the members list
			 *
			 * @filter e20r_memberslist_default_sort_order
			 *
			 * @param string $sort_order = 'ASC'
			 */
			return apply_filters( 'e20r_memberslist_default_sort_order', $sort_order );
		}

		/**
		 * Export specific SQL WHERE statement (for sorting)
		 *
		 * @param string $where The SQL where clause received.
		 * @param string $find The string to locate.
		 * @param string $levels The membership level IDs supplied.
		 * @param string $joins The join strings received.
		 *
		 * @return string
		 */
		public function export_member_where( $where, $find, $levels, $joins ) {

			$this->utils->log( 'Requesting (active/old/etc) member export' );

			$member_ids  = $this->utils->get_variable( 'member_user_ids', array() );
			$added_where = null;

			if ( ! empty( $where ) && ! empty( $member_ids ) ) {
				$added_where = ' AND ( ';
			} elseif ( empty( $where ) & ! empty( $member_ids ) ) {
				$added_where = ' ( ';
			}

			$this->utils->log( "Starting appended WHERE statement: {$added_where}" );
			// Is this a bulk export operation?
			if ( ! empty( $member_ids ) && is_array( $member_ids ) ) {

				sort( $member_ids );
				$this->utils->log( 'Processing list of ' . count( $member_ids ) . ' member IDs.' );
				$in_list      = implode( ', ', $member_ids );
				$added_where .= " mu.user_id IN ( {$in_list} )";

			} elseif ( ! empty( $member_ids ) ) {
				$this->utils->log( 'Processing single member ID: ' . count( $member_ids ) );
				$added_where .= sprintf( ' mu.user_id = %d', esc_sql( $member_ids ) );
			}

			if ( ! empty( $where ) && ! empty( $member_ids ) ) {
				$added_where .= ' ) ';
			}

			if ( ! empty( $added_where ) ) {
				$where .= $added_where;
			}

			return $where;
		}

		/**
		 * Update the SQL WHERE statement for the query, based on search values from the front-end (if applicable)
		 *
		 * @param string $where SQL Where statement.
		 * @param string $find Search supplied from the frontend.
		 * @param array  $levels The membership level(s) selected for the front-end list.
		 * @param array  $joins List of tables to JOIN and the JOIN type, etc.
		 *
		 * @return string
		 */
		public function metadata_where( $where, $find, $levels, $joins ) {

			$this->utils->log( 'Adding search based on search form' );

			$search      = $this->utils->get_variable( 'find', '' );
			$added_where = null;

			if ( ! empty( $where ) && ! empty( $search ) ) {
				$added_where = ' AND ( ';
			} elseif ( empty( $where ) & ! empty( $search ) ) {
				$added_where = ' ( ';
			}

			if ( ! empty( $search ) ) {
				$added_where .= '';
			}

			if ( ! empty( $where ) && ! empty( $search ) ) {
				$added_where .= ' ) ';
			}

			return $where;
		}

		/**
		 * Default list of bulk actions supported
		 *
		 * @return array
		 */
		public function get_bulk_actions() {

			$actions = array(
				'bulk-cancel' => esc_attr__( 'Cancel', 'e20r-members-list' ),
				'bulk-update' => esc_attr__( 'Update', 'e20r-members-list' ),
				'bulk-export' => esc_attr__( 'Export', 'e20r-members-list' ),
			);

			return apply_filters( 'e20r_memberlist_bulk_actions', $actions );
		}

		/**
		 * Configures the bulk item checkbox.
		 *
		 * @param object $item The record to process.
		 *
		 * @return string
		 */
		public function column_cb( $item ) {
			return apply_filters(
				'e20r_memberslist_bulk_checkbox',
				sprintf(
					'<input type="checkbox" name="%1$s[]" value="%2$s" />',
					"{$this->_args['singular']}_user_id",
					$item['ID']
				)
			);
		}

		/**
		 * Configure the user_login field in table
		 *
		 * @param array $item Database record for the row being processed.
		 *
		 * @return  string          Content for the cell
		 */
		public function column_user_login( $item ) {
			$user = new WP_User( $item['ID'] );

			$edit_url = add_query_arg(
				array(
					'user_id'         => $item['ID'],
					'wp_http_referer' => rawurlencode( wp_get_referer() ),
				),
				get_admin_url( get_current_blog_id(), 'user-edit.php' )
			);

			$row_nonce = wp_create_nonce( 'e20r_ml_nonce' );

			$actions = array(
				/* phpcs:ignore Squiz.PHP.CommentedOutCode.Found
				'cancel' => sprintf( '<a href="%1$s" title="%2$s" class="e20r-cancel-member">%3$s</a>',
					add_query_arg(
						array(
							'page_no'       => $this->utils->get_variable( 'page_no', 1 ),
							'action'        => 'cancel',
							'user_id'       => $item['ID'],
							'membership_id' => $item['membership_id'],
							'_row_nonce'    => $row_nonce,
						),
						get_admin_url( get_current_blog_id(), 'admin.php' )
					),
					esc_attr__( 'Cancel membership', 'e20r-members-list' ),
					esc_attr__( 'Cancel', 'e20r-members-list' )
				), */
				'update' => sprintf(
					'<a href="%1$s" title="%2$s" class="e20r-update-member">%3$s</a>',
					add_query_arg(
						array(
							'page_no'       => $this->utils->get_variable( 'page_no', 1 ),
							'action'        => 'update',
							'user_id'       => $item['ID'],
							'membership_id' => $item['membership_id'],
							'_row_nonce'    => $row_nonce,
						),
						get_admin_url( get_current_blog_id(), 'admin.php' )
					),
					esc_attr__( 'Update member info', 'e20r-members-list' ),
					esc_attr__( 'Update', 'e20r-members-list' )
				),
			);

			$avatar  = get_avatar( $item['ID'], 32 );
			$actions = apply_filters( 'e20r_memberslist_user_row_actions', $actions, $user );

			return sprintf(
				'%1$s
				<strong>
				%2$s
				</strong>
				<br>
				%3$s',
				$avatar,
				"<a href=\"{$edit_url}\">{$item['user_login']}</a>",
				$this->row_actions( $actions )
			);
		}

		/**
		 * Configure the user's first name field in table
		 *
		 * @param array $item Database record for the row being processed.
		 *
		 * @return string|null         Content for the cell
		 */
		public function column_first_name( $item ) {
			$user = get_userdata( $item['ID'] );

			if ( ! empty( $user->first_name ) ) {
				return $user->first_name;
			}

			if ( ! empty( $user->user_firstname ) ) {
				return $user->user_firstname;
			}

			$bfirstname = get_user_meta( $item['ID'], 'pmpro_bfirstname', true );

			if ( ! empty( $bfirstname ) ) {
				return $bfirstname;
			}

			return null;
		}

		/**
		 * Configure the user's last name field in table
		 *
		 * @param array $item Database record for the row being processed.
		 *
		 * @return string|null          Content for the cell
		 */
		public function column_last_name( $item ) {
			$user = get_userdata( $item['ID'] );

			if ( ! empty( $user->last_name ) ) {
				return $user->last_name;
			}

			if ( ! empty( $user->user_lastname ) ) {
				return $user->user_lastname;
			}

			$blastname = get_user_meta( $user->ID, 'pmpro_blastname', true );

			if ( ! empty( $blastname ) ) {
				return $blastname;
			}

			return null;
		}

		/**
		 * Configure the user's email/mailto field in table
		 *
		 * @param array $item Database record for the row being processed.
		 *
		 * @return  string          Content for the cell
		 */
		public function column_user_email( $item ) {
			return sprintf(
				'<a href="mailto:%s">%s</a>',
				$item['user_email'],
				$item['user_email']
			);
		}

		/**
		 * Configure the billing address info field in table
		 *
		 * @param array $item Database record for the row being processed.
		 *
		 * @return  string          Content for the cell
		 */
		public function column_baddress( $item ) {
			if ( ! function_exists( 'pmpro_formatAddress' ) ) {
				return esc_attr__( 'Not found', 'e20r-members-list' );
			}

			$user    = get_user_by( 'id', $item['ID'] );
			$address = pmpro_formatAddress(
				trim( "{$user->pmpro_bfirstname} {$user->pmpro_blastname}" ),
				$user->pmpro_baddress1,
				$user->pmpro_baddress2,
				$user->pmpro_bcity,
				$user->pmpro_bstate,
				$user->pmpro_bzipcode,
				$user->pmpro_bcountry,
				$user->pmpro_bphone
			);

			if ( empty( $address ) ) {
				$this->utils->log( "User {$item['ID']} has no billing address" );
				$address = esc_attr__( 'Not found', 'e20r-members-list' );

				if ( 0 >= intval( $item['initial_payment'] ) && 0 >= intval( $item['billing_amount'] ) ) {
					return esc_attr__( 'N/A', 'e20r-members-list' );
				}
			}

			return $address;
		}

		/**
		 * Generate the column information for the Membership Level(s) that the user belongs to/has purchased
		 *
		 * @param array $item The record being processed
		 *
		 * @return string
		 */
		private function single_membership_column( $item ) {
			// These are used to configure the membership level with JavaScript.
			$membership_input = sprintf(
				'
				<input type="hidden" value="%1$d" class="e20r-members-list-membership-id" name="e20r-members-list-membership_id_%2$s" />
				<input type="hidden" value="%2$d" class="e20r-members-list-user-id" name="e20r-members-list-membership_id_user_id_%2$s" />
				<input type="hidden" value="%3$s" class="e20r-members-list-membership_id-label" name="e20r-members-list-membership_label_%2$s" />
				<input type="hidden" value="%1$d" class="e20r-members-list-db-membership_id" name="e20r-members-list-db_membership_id_%2$s" />
				<input type="hidden" value=""     class="e20r-members-list-db-membership_level_ids" name="e20r-members-list-db_membership_level_ids_%2$s" />
				<input type="hidden" value="%5$d" class="e20r-members-list-db_record_id" name="e20r-members-list-db_record_id_%2$s" />
				<input type="hidden" value="%4$s" class="e20r-members-list-field-name" name="e20r-members-list-field_name_%2$s" />',
				$item['membership_id'],
				$item['ID'],
				$item['name'],
				'membership_id',
				$item['record_id']
			);

			$options = self::build_option_string( $item['membership_id'] );

			$new_membershiplevel_input = sprintf(
				'<div class="ml-row-settings clearfix">
						%1$s
						<select name="e20r-members-list-new_membership_id_%2$s" class="e20r-members-list-select-membership_id">
						%3$s
						</select>
						<br>
						<a href="#" class="e20r-members-list-cancel e20r-members-list-link">%4$s</a>
					</div>',
				$membership_input,
				$item['ID'],
				$options,
				esc_attr__( 'Reset', 'e20r-members-list' )
			);

			$value = sprintf(
				'<a href="#" class="e20r-members-list_membership_id e20r-members-list-editable" title="%1$s">%2$s<span class="dashicons dashicons-edit"></a>%3$s',
				esc_attr__( 'Click to edit membership level', 'e20rapp' ),
				$item['name'],
				$new_membershiplevel_input
			);

			return $value;
		}
		/**
		 * Does the specified module exist/is it activated on the site?
		 *
		 * @param string $plugin_path The Plugin (module) specific path to check for.
		 * @return bool
		 */
		private function is_module_enabled( $plugin_path ) {
			return $this->utils->plugin_is_active( null, $plugin_path );
		}

		/**
		 * Configure the user's membership level field in table
		 *
		 * @param array $item Database record for the row being processed.
		 *
		 * @return  string          Content for the cell
		 */
		public function column_name( $item ) {
			if ( true === $this->is_module_enabled( 'pmpro-multiple-memberships-per-user/pmpro-multiple-memberships-per-user.php' ) ) {
				$mmpu = new Multiple_Memberships();
				$this->utils->log( 'MMPU support is deactivated pending support for adding/removing MMPU memberships' );
				// return $mmpu->multiple_membership_column( $item ); FIXME: Actually enable MMPU support
			}

			return $this->single_membership_column( $item );
		}

		/**
		 * Calculate the user's initial/recurring fee
		 *
		 * @param array $item Database record for the row being processed.
		 *
		 * @return  string          Content for the cell
		 */
		public function column_fee( $item ) {
			$fee_string = array();

			if ( $item['initial_payment'] > 0 ) {

				$fee_string[] = sprintf( '%s', pmpro_formatPrice( (float) $item['initial_payment'] ) );
			}

			if ( $item['initial_payment'] > 0 && $item['billing_amount'] > 0 ) {
				$fee_string[] = ' + <br />';
			}

			if ( $item['billing_amount'] > 0 ) {

				if ( $item['cycle_number'] > 1 ) {
					$freq = sprintf( '%1$s %2$s(s)', $item['cycle_number'], $item['cycle_period'] );
				} else {
					$freq = sprintf( '%s', $item['cycle_period'] );
				}

				$fee_string[] = sprintf( '%1$s / %2$s', pmpro_formatPrice( $item['billing_amount'] ), $freq );
			}

			if ( empty( $fee_string ) ) {
				$fee_string[] = apply_filters( 'e20r_memberslist_column_value_free', esc_attr__( 'Free', 'e20r-members-list' ) );
			}

			return implode( ' ', $fee_string );
		}

		/**
		 * Display the Discount Code used by the member
		 *
		 * @param array $item The record to process.
		 *
		 * @return stdClass
		 */
		public function column_code( $item ) {
			$code_info     = Export_Members::get_pmpro_discount_code( $item['code_id'], $item['ID'], $item['membership_id'] );
			$discount_code = null;

			if ( ! empty( $code_info ) ) {
				$discount_code = $code_info->pmpro_discount_code;
			}

			return $discount_code;
		}

		/**
		 * Display the date for when the user first joined
		 *
		 * @param array $item Database record for the row being processed.
		 *
		 * @return  string          Content for the cell
		 */
		public function column_user_registered( $item ) {
			return sprintf(
				'%s',
				date_i18n(
					$this->date_format,
					strtotime( $item['user_registered'], time() )
				)
			);
		}

		/**
		 * Display the date when the user started at the current membership level(s).
		 *
		 * @param array $item Database record for the row being processed.
		 *
		 * @return  string          Content for the cell
		 */
		public function column_startdate( $item ) {

			if ( $this->has_empty_date( $item['startdate'] ) ) {
				$date_value  = null;
				$start_label = esc_attr__( 'Invalid', 'e20r-members-list' );
			} else {
				$date_value  = ! empty( $item['startdate'] ) ? gmdate( 'Y-m-d', strtotime( $item['startdate'], time() ) ) : null;
				$start_label = gmdate( $this->date_format, strtotime( $item['startdate'], time() ) );
			}

			$min_val = empty( $item['startdate'] ) ? sprintf( 'min="%s"', gmdate( 'Y-m-d', time() ) ) : null;

			$startdate_input = sprintf(
				'
				<input type="hidden" value="%1$s" class="e20r-members-list-membership-id" name="e20r-members-list-startdate_mid_%2$s" />
				<input type="hidden" value="%3$s" class="e20r-members-list-user-id" name="e20r-members-list-user_id_%2$s" />
				<input type="hidden" value="%4$s" class="e20r-members-list-startdate-label" name="e20r-members-list-startdatelabel_%2$s" />
				<input type="hidden" value="%3$s" class="e20r-members-list-db-startdate" name="e20r-members-list-db_startdate_%2$s" />
				<input type="hidden" value="%5$d" class="e20r-members-list-db_record_id" name="e20r-members-list-db_record_id_%2$s" />
				<input type="hidden" value="%6$s" class="e20r-members-list-field-name" name="e20r-members-list-field_name_%2$s" />',
				$item['membership_id'],
				$item['ID'],
				$date_value,
				$start_label,
				$item['record_id'],
				'startdate'
			);

			$new_date_input = sprintf(
				'<div class="ml-row-settings clearfix">
						%1$s
						<input type="date" placeholder="YYYY-MM-DD" pattern="(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))" title="Enter a date in this format YYYY-MM-DD" name="e20r-members-list-new_startdate_%2$s" class="e20r-members-list-input-startdate" value="%3$s" %4$s />
						<br>
						<a href="#" class="e20r-members-list-cancel e20r-members-list-list-link">%5$s</a>
					</div>',
				$startdate_input,
				$item['ID'],
				$date_value,
				$min_val,
				esc_attr__( 'Cancel', 'e20r-members-list' )
			);

			$value = sprintf(
				'<a href="#" class="e20r-members-list_startdate e20r-members-list-editable" title="%1$s">%2$s<span class="dashicons dashicons-edit"></a>%3$s',
				esc_attr__( 'Edit to bulk update membership start date', 'e20rapp' ),
				$start_label,
				$new_date_input
			);

			return $value;
		}

		/**
		 * Generate the HTML for the membership level options
		 *
		 * @param int $level_id The membership level ID to highlight
		 *
		 * @return string
		 */
		public static function build_option_string( $level_id ) {

			$options = '';

			if ( function_exists( 'pmpro_getAllLevels' ) ) {
				$levels = pmpro_getAllLevels( true, true );
			} else {

				// Default info if PMPro is disabled.
				$null_level           = new stdClass();
				$null_level->level_id = 0;
				$null_level->name     = esc_attr__( 'No levels found. Paid Memberships Pro is inactive!', 'e20r-members-list' );
				$levels               = array( $null_level );
			}

			foreach ( $levels as $level ) {
				$options .= sprintf(
					'<option value="%1$s" %2$s>%3$s</option>',
					$level->id,
					selected( $level->id, $level_id, false ),
					$level->name
				) . "\n";
			}

			return $options;
		}
		/**
		 * Does the received value represent an 'empty' date value
		 *
		 * @param string|int|null $date The value to check
		 *
		 * @return bool
		 */
		public function has_empty_date( $date ) {
			return in_array( $date, $this->empty_date_values, true );
		}
		/**
		 * Create the last column for the default Members_List table (Expiration date)
		 *
		 * @param array $item The record to process.
		 *
		 * @return string
		 */
		public function column_last( $item ) {

			$no_enddate    = false;
			$html_label    = esc_attr__( 'Unknown', 'e20r-members-list' );
			$enddate_label = '';

			// The end-date field is empty/not configured (never ending membership)
			if ( ! isset( $item['enddate'] ) || in_array( $item['enddate'], $this->empty_date_values, true ) ) {
				$enddate_label = esc_attr__( 'N/A', 'e20r-members-list' );
				$html_label    = $enddate_label;
				$no_enddate    = true;
			}

			// We have a user with a recurring billing membership.
			if ( true === $no_enddate && ! empty( $item['billing_amount'] ) && ! empty( $item['cycle_number'] ) ) {
				$next_payment = pmpro_next_payment( $item['ID'], 'success', 'timestamp' );
				if ( false === $next_payment ) {
					$next_payment_date = esc_attr__( 'Unknown', 'e20r-members-list' );
				} else {
					$next_payment_date = gmdate( $this->date_format, $next_payment );
				}
				$enddate_label = esc_attr__( 'N/A', 'e20r-members-list' );
				$html_label    = sprintf(
					// translators: %1$s - HTML %2$s - next payment date, %3$s - HTML.
					esc_attr__( 'N/A (%1$sNext Payment: %2$s%3$s)', 'e20r-members-list' ),
					'<span class="e20r-members-list-small" style="font-size: 10px; font-style: italic;">',
					$next_payment_date,
					'</span>'
				);
			}

			// Setting the date label only if the value is non-empty _AND_ the value is _NOT_ ''
			if ( false === $no_enddate ) {
				$enddate_label = gmdate(
					$this->date_format,
					strtotime( $item['enddate'], time() )
				);
				$html_label    = $enddate_label;
			}

			// BUG FIX: Didn't set the date format to match the WP setting as documented.
			$date_value = ( false === $no_enddate ) ?
				strtotime( $item['enddate'], time() ) :
				null;

			$date_value = apply_filters( 'e20r_members_list_enddate_col_result', $date_value, $item );

			// These are used to configure the enddate with JavaScript.
			$enddate_input = sprintf(
				'
				<input type="hidden" value="%1$s" class="e20r-members-list-membership-id" name="e20r-members-list-enddate_mid_%2$s" />
				<input type="hidden" value="%2$s" class="e20r-members-list-user-id" name="e20r-members-list-user_id_%2$s" />
				<input type="hidden" value="%3$s" class="e20r-members-list-enddate-label" name="e20r-members-list-enddatelabel_%2$s" />
				<input type="hidden" value="%4$s" class="e20r-members-list-db-enddate" name="e20r-members-list-db_enddate_%2$s" />
				<input type="hidden" value="%5$d" class="e20r-members-list-db_record_id" name="e20r-members-list-db_record_id_%2$s" />
				<input type="hidden" value="enddate" class="e20r-members-list-field-name" name="e20r-members-list-field_name_%2$s" />',
				$item['membership_id'],
				$item['ID'],
				$enddate_label,
				$date_value,
				$item['record_id'],
			);

			$new_date_input = sprintf(
				'<div class="ml-row-settings clearfix">
						%1$s
						<input type="date" placeholder="YYYY-MM-DD" pattern="(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))" title="Enter a date in this format YYYY-MM-DD" name="e20r-members-list-new_enddate_%2$s" class="e20r-members-list-input-enddate" value="%3$s"/>
						<br />
						<a href="#" class="e20r-members-list-cancel e20r-members-list-list-link">%4$s</a>
					</div>',
				$enddate_input,
				$item['ID'],
				$date_value ? gmdate( 'Y-m-d', $date_value ) : $date_value,
				esc_attr__( 'Cancel', 'e20r-members-list' )
			);

			return sprintf(
				'<a href="#" class="e20r-members-list_enddate e20r-members-list-editable" title="%1$s">%2$s<span class="dashicons dashicons-edit"></span></a>%3$s',
				esc_attr__( 'Update membership end/expiration date', 'e20r-members-list' ),
				$html_label,
				$new_date_input
			);
		}

		/**
		 * Configure the membership level status field
		 *
		 * @param array $item The record to process.
		 *
		 * @return null|string
		 */
		public function column_status( $item ) {

			$value   = null;
			$options = '';

			$status_list = apply_filters( 'e20r_memberslist_member_status', $this->get_pmpro_statuses() );
			$status_text = explode( '_', $item['status'] );
			$label_text  = '';

			if ( is_array( $status_text ) ) {
				$label_text = implode( ' ', array_map( 'ucfirst', $status_text ) );
			}

			$status_input = sprintf(
				'
				<input type="hidden" value="%1$s" class="e20r-members-list-status" name="e20r-members-list_status_mid_%2$s" />
				<input type="hidden" value="%2$s" class="e20r-members-list-user-id" name="e20r-members-list-user_id_%2$s" />
				<input type="hidden" value="%6$s" class="e20r-members-list-status-label" name="e20r-members-list-status_label_%2$s" />
				<input type="hidden" value="%3$s" class="e20r-members-list-db-status" name="e20r-members-list-db_status_%2$s" />
				<input type="hidden" value="%5$d" class="e20r-members-list-db_record_id" name="e20r-members-list-db_record_id_%2$s" />
				<input type="hidden" value="%4$s" class="e20r-members-list-field-name" name="e20r-members-list-field_name_%2$s" />',
				$item['membership_id'],
				$item['ID'],
				$item['status'],
				'status',
				$item['record_id'],
				$label_text
			);

			foreach ( $status_list as $status ) {

				$status_text = explode( '_', $status );
				$text        = is_array( $status_text ) ?
						implode( ' ', array_map( 'ucfirst', $status_text ) ) :
						ucfirst( $status_text ); /* @phpstan-ignore-line */

				$options .= sprintf(
					'\t<option value="%1$s" %2$s>%3$s</option>\n',
					$status,
					selected( $status, $item['status'], false ),
					$text
				);
			}
			$new_status_input = sprintf(
				'<div class="ml-row-settings clearfix">
						%1$s
						<select name="e20r-members-list-new_status_%2$s" class="e20r-members-list-select-status">
						%3$s
						</select>
						<br />
						<a href="#" class="e20r-members-list-cancel e20r-members-list-link">%4$s</a>
					</div>',
				$status_input,
				$item['ID'],
				$options,
				esc_attr__( 'Reset', 'e20r-members-list' )
			);

			$value = sprintf(
				'<a href="#" class="e20r-members-list_status e20r-members-list-editable" title="%1$s">%2$s<span class="dashicons dashicons-edit"></a></span>%3$s',
				esc_attr__( "Update the member's membership status", 'e20r-members-list' ),
				$label_text,
				$new_status_input
			);

			return $value;
		}

		/**
		 * Fetch, cache and return all recorded status types from the DB
		 *
		 * @return array
		 */
		private function get_pmpro_statuses() {
			global $wpdb;
			global $e20r_pmpro_statuses;

			if ( ! empty( $e20r_pmpro_statuses ) ) {
				return $e20r_pmpro_statuses;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$e20r_pmpro_statuses = $wpdb->get_col(
				"SELECT DISTINCT mu.status FROM {$wpdb->pmpro_memberships_users} AS mu"
			);

			if ( empty( $e20r_pmpro_statuses ) ) {
				$e20r_pmpro_statuses = array();
			}

			return $e20r_pmpro_statuses;
		}

		/**
		 * Handle any columns that don't have explicit handlers in this class
		 *
		 * @param object $item Database record for the row.
		 * @param string $name Field/column name.
		 *
		 * @return mixed
		 */
		public function column_default( $item, $name ) {

			// To avoid warnings/notices.
			$value = $item[ $name ] ?? null;

			/**
			 * If it's not one of the Members_List default columns, apply a filter so other
			 * plugins can load their own column info.
			 */
			if ( ! in_array( $name, $this->default_columns, true ) ) {
				// Apply a filter for this column in the memberslist.
				$value = apply_filters( 'e20r_memberslist_custom_column', $value, $item, $name );
			}

			// Default.
			return $value;
		}

		/**
		 * Configure columns to use for Member Listing
		 *
		 * @return array
		 */
		public function get_columns() {
			return $this->all_columns();
		}

		/**
		 * Default text when no records are found/returned
		 */
		public function no_items() {
			$default_level = apply_filters( 'e20r_members_list_default_member_status', 'active' );
			$level         = trim( $this->utils->get_variable( 'level', $default_level ) );
			$this->search  = $this->utils->get_variable( 'find', '' );

			$active_members_url = add_query_arg(
				array(
					'page'  => 'pmpro-memberslist',
					'level' => 'active',
					'find'  => esc_attr( $this->search ),
				),
				admin_url( 'admin.php' )
			);

			$all_members_url = add_query_arg(
				array(
					'page'  => 'pmpro-memberslist',
					'level' => 'all',
					'find'  => esc_attr( $this->search ),
				),
				admin_url( 'admin.php' )
			);

			$cancelled_url = add_query_arg(
				array(
					'page'  => 'pmpro-memberslist',
					'level' => 'cancelled',
					'find'  => esc_attr( $this->search ),
				),
				admin_url( 'admin.php' )
			);

			$expired_url = add_query_arg(
				array(
					'page'  => 'pmpro-memberslist',
					'level' => 'expired',
					'find'  => esc_attr( $this->search ),
				),
				admin_url( 'admin.php' )
			);

			$old_members_url = add_query_arg(
				array(
					'page'  => 'pmpro-memberslist',
					'level' => 'oldmembers',
					'find'  => esc_attr( $this->search ),
				),
				admin_url( 'admin.php' )
			);

			$all_users_url = add_query_arg(
				array(
					's' => esc_attr( $this->search ),
				),
				admin_url( 'users.php' )
			);

			esc_attr_e( 'No members found', 'e20r-members-list' );
			?>
			<hr/>
			<div class="e20r-pmpro-memberslist-no-members-found-list">
				<p class=""><?php esc_attr_e( 'It\'s possible the information you\'re looking for can be found in one of the following categories:', 'e20r-members-list' ); ?></p>
				<ul class="ul-disc">
					<?php if ( 'active' !== $level ) { ?>
						<li class="e20r-pmpro-memberslist-not-found active-members">
							<?php
							printf(
									// translators: %1$s HTML, %2%s HTML.
								esc_attr__( 'Repeat search: %1$sActive Members list%2$s', 'e20r-members-list' ),
								sprintf( '<a href="%1$s">', esc_url_raw( $active_members_url ) ),
								'</a>'
							);
							?>
						</li>
					<?php } ?>

					<?php if ( 'all' !== $level ) { ?>
						<li class="e20r-pmpro-memberslist-not-found all-members">
							<?php
							printf(
									// translators: %1$s HTML, %2%s HTML.
								esc_attr__( 'Repeat search: %1$sAll Members list%2$s', 'e20r-members-list' ),
								sprintf( '<a href="%1$s">', esc_url_raw( $all_members_url ) ),
								'</a>'
							);
							?>
						</li>
					<?php } ?>
					<?php if ( 'cancelled' !== $level ) { ?>
						<li class="e20r-pmpro-memberslist-not-found cancelled-members">
							<?php
							printf(
									// translators: %1$s HTML, %2$s HTML.
								esc_attr__( 'Repeat search: %1$sCancelled Members list%2$s', 'e20r-members-list' ),
								sprintf(
									'<a href="%1$s">',
									esc_url_raw( $cancelled_url )
								),
								'</a>'
							);
							?>
						</li>
					<?php } ?>
					<?php if ( 'expired' !== $level ) { ?>
						<li class="e20r-pmpro-memberslist-not-found expired-members">
							<?php
							printf(
								// translators: %1$s HTML, %2$s HTML.
								esc_attr__( 'Repeat search: %1$sExpired Members list%2$s', 'e20r-members-list' ),
								sprintf( '<a href="%1$s">', esc_url_raw( $expired_url ) ),
								'</a>'
							);
							?>
						</li>
					<?php } ?>
					<?php if ( 'oldmembers' !== $level ) { ?>
						<li class="e20r-pmpro-memberslist-not-found old-members">
							<?php
							printf(
									// translators: %1$s HTML, %2$s HTML.
								esc_attr__( 'Repeat search: %1$sOld Members list%2$s', 'e20r-members-list' ),
								sprintf( '<a href="%1$s">', esc_url_raw( $old_members_url ) ),
								'</a>'
							);
							?>
						</li>
					<?php } ?>
					<li class="e20r-pmpro-memberslist-not-found all-users">
						<?php
						printf(
								// translators: %1$s HTML, %2$s HTML.
							esc_attr__( 'Repeat search: %1$sAll Users list%2$s', 'e20r-members-list' ),
							sprintf( '<a href="%1$s">', esc_url_raw( $all_users_url ) ),
							'</a>'
						);
						?>
					</li>
				</ul>
			</div>
			<?php
		}
	}
}
