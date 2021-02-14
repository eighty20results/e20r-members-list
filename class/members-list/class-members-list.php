<?php
/*
 * License:

	Copyright 2016-2021 - Eighty / 20 Results by Wicked Strong Chicks, LLC (thomas@eighty20results.com)

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
*/

namespace E20R\Members_List\Admin;

/** Load WP_List_Table if it's not already loaded */
if ( ! class_exists( '\\WP_List_Table' ) ) {
	require_once( ABSPATH . "wp-admin/includes/class-wp-list-table.php" );
}

use E20R\Members_List\Controller\E20R_Members_List;
use E20R\Utilities\Utilities;

class Members_List extends \WP_List_Table {

	/**
	 * Class instance variable (singleton support).
	 * @var     Members_List $instance
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
	 * @var int $total_member_records
	 */
	private $total_member_records = 0;

	/**
	 * The status of the membership when searching for totals
	 *
	 * @var string $membership_status
	 */
	private $membership_status = 'active';

	/**
	 * The completed SQL query used to generate the membership list
	 *
	 * @var string $sqlQuery
	 */
	private $sqlQuery = '';
	/**
	 * Instance of the Utilities class
	 *
	 * @var     Utilities $utils
	 */
	private $utils;
	/**
	 * Various elements of the SQL query as it's being built.
	 */
	private $table_list = array();
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
	 * The LIMIT OFFSET part of the SQL statement
	 * @var null|string $offset
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
	 * @var null|string $levels
	 */
	private $levels = null;
	/**
	 * The SQL search string
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
	 * @var mixed
	 */
	private $action = null;

	/**
	 * Members_List constructor.
	 */
	public function __construct() {

		parent::__construct( array(
				'singular' => __( "member", E20R_Members_List::plugin_slug ),
				'plural'   => __( "members", E20R_Members_List::plugin_slug ),
				'ajax'     => false,
			)
		);

		if ( method_exists( 'E20R\Utilities\Utilities', 'get_instance' ) ) {
			$this->utils = Utilities::get_instance();
			$this->utils->log( "Loaded Utilities class for the Members List" );
		}

		$this->sql_col_list = $this->set_sql_columns();

		$level        = $this->utils->get_variable( 'level', '' );
		$this->action = $this->utils->get_variable( 'action', '' );

		if ( ! empty( $level ) ) {
			switch ($level) {
				case 'cancelled':
					$this->membership_status = array( 'cancelled' );
					break;
				case 'expired':
					$this->membership_status = array( 'expired' );
					break;
				case 'old':
					$this->membership_status = array( 'cancelled', 'expired' );
					break;
				default:
					$this->membership_status = array('active');
			}
		}
		/**
		 * The default Members List columns to display (with labels)
		 */
		$this->default_columns = array(
			'cb'              => '<input type="checkbox" />',
			// 'user_id'    => _x( "ID", E20R_Members_List::plugin_slug ),
			'user_login'      => _x( "Login", E20R_Members_List::plugin_slug ),
			'first_name'      => _x( "First Name", E20R_Members_List::plugin_slug ),
			'last_name'       => _x( "Last Name", E20R_Members_List::plugin_slug ),
			'user_email'      => _x( "Email", E20R_Members_List::plugin_slug ),
			'baddress'        => _x( "Billing Info", E20R_Members_List::plugin_slug ),
			'name'            => _x( "Level", E20R_Members_List::plugin_slug ),
			'fee'             => _x( "Fee", E20R_Members_List::plugin_slug ),
			'code'            => _x( "Discount Code", E20R_Members_List::plugin_slug ),
			'status'          => _x( "Status", E20R_Members_List::plugin_slug ),
			'user_registered' => _x( "Joined", E20R_Members_List::plugin_slug ),
			'startdate'       => _x( "Start", E20R_Members_List::plugin_slug ),
		);

		/**
		 * Should the final column use 'Expired' or 'Expires' as the label
		 */
		if ( 'oldmembers' == $level ) {
			$this->default_columns['last'] = apply_filters( 'e20r-members-list-enddate-col-name', _x( "Expired", E20R_Members_List::plugin_slug ), $level );
		} else {
			$this->default_columns['last'] = apply_filters( 'e20r-members-list-enddate-col-name', _x( "Expires", E20R_Members_List::plugin_slug ), $level );
		}

		$this->total_member_records = $this->get_member_record_count();

		/**
		 * Prepare the Export bulk action
		 */
		if ( 'e20rml_export_records' === $this->utils->get_variable( 'action', null ) ) {
			$this->utils->log( "Adding export handler" . ( headers_sent() ? ' Sent' : ' Not sent' ) );
			add_action( 'e20r_memberslist_process_action', array( $this, 'export_members' ), 10, 3 );
		}
	}

	/**
	 * Private function to capture the count of records in the membership database
	 *
	 * @return int|null
	 */
	private function get_member_record_count() {

		$status = $this->utils->get_variable( 'level', 'active' );

		// Get SQL for all records in the paginated data
		$this->generate_member_sql( $status );
		$records = $this->get_members( -1, -1, $status );

		return is_countable( $records ) ? count( $records ) : 0;
	}

	/**
	 * Default list of columns to fetch.
	 *
	 * @return array - Array of SQL table columns/aliases to use when selecting data for membership list.
	 */
	private function set_sql_columns() {

		// Format: 'name' => 'alias'
		$sql_cols = array(
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
		 * @filter e20r-members-list-default-sql-column-alias-map
		 */
		return apply_filters( 'e20r-members-list-default-sql-column-alias-map', $sql_cols );
	}

	/**
	 * Creates or returns an instance of the PMPro_Approvals class.
	 *
	 * @return  Members_List A single instance of this class.
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

	/**
	 * Handle data query & filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->utils->log( "Loading column headers" );
		$total_items = 0;

		// Configure the column headers
		$this->_column_headers = array(
			$this->all_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		);

		$should_export = $this->utils->get_variable( 'action', '' );

		if ( false !== $this->current_action() || 'e20rml_export_records' === $should_export ) {

			$this->utils->log( "Trigger bulk action(s)/export" );

			// Handle bulk & save actions
			$this->process_bulk_action();
		}

		// How many rows per page
		$per_page = $this->get_items_per_page( 'per_page', 15 );

		// Do we need to limit?
		$level = $this->utils->get_variable( 'level', 'active' );

		// Get the current page number
		$current_page = $this->get_pagenum();

		$this->utils->log( "Fetch records from DB" );
		// Load  & count records
		$this->items = $this->get_members( $per_page, $current_page, $level );

		// BUG FIX: Handle situation(s) where there are no records found
		if ( null !== $this->items ) {
			$this->utils->log(
				sprintf(
				"Configure pagination for %d total records and %d counted (returned) records",
				$this->total_member_records,
				(is_countable( $this->items ) ? count( $this->items ) : 0)
				)
			);
			// $total_items = $this->record_count();
		}

		// Configure pagination
		$this->set_pagination_args(
			array(
				'total_items' => $this->total_member_records,
				'per_page'    => $per_page,
				'total_pages' => ceil( $this->total_member_records / $per_page ),
			)
		);

	}

	/**
	 * Default columns to use for Member Listing (filterable)
	 *
	 * @return array
	 */
	private function all_columns() {

		$columns = $this->default_columns;

		/**
		 * Add/remove columns from the members list.
		 *
		 * @filter 'e20r-members-list-add-to-default-table-columns'
		 *
		 * @param array $new_columns - New columns to add to the WP_List_Table output
		 * @param array $columns     - List of existing/default columns
		 */
		$new_columns = apply_filters( 'e20r_memberslist_columnlist', array(), $columns );
		$new_columns = apply_filters( 'e20r-members-list-add-to-default-table-columns', $new_columns, $columns );


		if ( apply_filters( 'e20r-members-list-page-prepend-cols', false ) && ! empty( $new_columns ) ) {
			$columns = $new_columns + $this->default_columns;
		} else if ( ! empty( $new_columns ) ) {
			$columns = $this->default_columns + $new_columns;
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

			if ( ! in_array( 'code_id', $columns_to_hide ) ) {
				$columns_to_hide[] = 'code_id';
			}

			$this->hidden_columns = $columns_to_hide;
		}

		$this->hidden_columns = apply_filters( 'e20r_memberslist_hidden_columns', $this->hidden_columns );

		return apply_filters( 'e20r-members-list-hidden-columns', $this->hidden_columns );
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

			// Some of the default columns are sortable
			switch ( $col ) {
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

		$a    = $this->utils->get_variable( 'action', '' );
		$a2   = $this->utils->get_variable( 'action2', '' );
		$page = $this->utils->get_variable( 'page', '' );

		if ( $a == - 1 && $a2 == - 1 ) {
			$this->utils->log( "No bulk action to execute" );

			return;
		}

		if ( 'e20r-memberslist' !== $page ) {
			$this->utils->log( "Not on the Members List page, so nothing to do" );

			return;
		}

		// Are we processing a bulk action?
		if ( 1 === preg_match( '/bulk-/', $a ) || 1 === preg_match( '/bulk-/', $a2 ) ) {

			$this->utils->log( "Processing a bulk action" );

			// Process any plugin/add-on bulk actions first.
			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $this->utils->get_variable( '_wpnonce', null ) );

			$this->utils->log( "Nonce is: {$nonce} for action: " . $this->current_action() );

			if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
				$this->utils->add_message( __( 'Error: Insecure bulk action denied.', E20R_Members_List::plugin_slug ), 'warning', 'backend' );

				return;
			}

			$level_id = $this->utils->get_variable( 'membership_id', array() );
			$action   = $this->current_action();
			$data     = array();

			$selected_members = $this->utils->get_variable( 'member_id', array() );

			foreach ( $selected_members as $key => $user_id ) {
				$user_level = $this->utils->get_variable( "e20r-members-list-membership_id_{$user_id}", 0 );
				$data[]     = array( 'user_id' => $user_id, 'level_id' => $user_level );
			}

			$bulk_actions = array( $a, $a2 );

			// Prepare plugin specific data for member list bulk action processing
			$data = apply_filters( 'e20r_memberslist_bulk_action_data_array', $data, $action, $level_id );

			// Process member list bulk action in add-ons/plugins
			do_action( 'e20r_memberslist_process_bulk_actions', $nonce, $action, $bulk_actions, $data, $this->_args['plural'] );

			$this->utils->log( "About to try and trigger one of the default actions" );

			if ( in_array( 'bulk-cancel', $bulk_actions ) ) {

				$cancel = Bulk_Cancel::get_instance();
				$cancel->set_members( $data );
				$cancel->cancel();

				return;

			} else if ( in_array( 'bulk-export', $bulk_actions ) ) {

				$this->utils->log( "Requested Export of members!" );
				$this->export_members();

				// To push the export file to the browser, we have to terminate execution of this process.
				$this->utils->log( "Returned from export_members(). That's unexpected!" );

				// We should never get here.
				return;

			} else if ( in_array( 'bulk-update', $bulk_actions ) ) {

				$this->utils->log( "Requested member updates for " . count( $data ) . " records" );

				$update = Bulk_Update::get_instance();
				$update->set_members( $data );
				$update->update();

				return;

			}
		} else {

			$this->utils->log( "Single action for the Members List..." );

			$user_id  = $this->utils->get_variable( 'member_id', array() );
			$level_id = $this->utils->get_variable( 'membership_id', array() );
			$action   = $this->current_action();

			switch ( $action ) {

				case 'cancel':

					$user_ids = array(
						array( 'user_id' => $user_id, 'level_id' => $level_id ),
					);

					$cancel = Bulk_Cancel::get_instance();
					$cancel->set_members( $user_ids );

					if ( false === $cancel->cancel() ) {
						if ( function_exists( 'pmpro_setMessage' ) ) {
							pmpro_setMessage( __( "Error cancelling membership", E20R_Members_List::plugin_slug ), 'error' );
						} else {
							global $msg;
							global $msgt;

							$msg  = __( "Error cancelling membership", E20R_Members_List::plugin_slug );
							$msgt = 'error';
						}
					}

					break;

				default:
					$this->utils->log( "Trigger external process_action for memberslist" );
					// Process add-on Members list actions
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
	 * @param null|string $action
	 * @param null|int    $user_id
	 * @param null|int    $level_id
	 */
	public function export_members( $action = null, $user_id = null, $level_id = null ) {

		$this->utils->log( "Called by: " . $this->utils->_who_called_me() );
		$search_level = $this->utils->get_variable( 'level', '' );

		$this->utils->log( "Content sent...?" . ( headers_sent() ? 'Yes' : 'No' ) );
		if ( empty( $search_level ) ) {
			$search_level = 'active';
		}

		add_filter( 'e20r_memberslist_sql_where_statement', array( $this, 'export_member_where' ), 20, 4 );
		add_filter( 'e20r_memberslist_sort_order', array( $this, 'export_sort_order' ), 10, 1 );
		add_filter( 'e20r_memberslist_order_by', array( $this, 'export_order_by' ), 10, 1 );

		$members_to_export = $this->get_members( - 1, 1, $search_level );

		$export = new Export_Members( $members_to_export );
		$export->get_data_list();
		$export->save_data_for_export();
		$export->return_content();
	}

	/**
	 * Load member data for listing
	 *
	 * @param int    $per_page
	 * @param int    $page_number
	 * @param string $status
	 *
	 * @return array|null|object
	 */
	public function get_members( $per_page = 15, $page_number = 1, $status = 'all' ) {

		global $wpdb;

		// Get Pagination SQL
		$this->sqlQuery = $this->generate_member_sql( $status, $per_page, $page_number, );

		// Fetch the data
		$result = $wpdb->get_results( $this->sqlQuery, ARRAY_A );

		if ( ! empty( $result ) ) {
			$this->utils->log("Found records in DB...");
			$this->total_members_found = $wpdb->num_rows;
		}

		// Return the result set unless an error occurred.
		if ( ! empty( $result ) ) {

			$order    = esc_sql( apply_filters( 'e20r_memberslist_sort_order', $this->utils->get_variable( 'order', 'DESC' ) ) );
			$order_by = esc_sql( apply_filters( 'e20r_memberslist_order_by', $this->utils->get_variable( 'orderby', 'ml.id' ) ) );

			if ( ! in_array( $order_by, array_keys( $this->sql_col_list ) ) ) {
				$this->utils->log( "3rd Party sort of the returned records by {$order_by}/{$order} and " . count( $result ) . " records" );
				$result = apply_filters( 'e20r_memberslist_sort_filter', $result, $order_by, $order, $page_number, $per_page );
			}

			$this->utils->log( " Returning " . count( $result ) . " records" );

			return $result;

		} else {

			$error_msg = $wpdb->print_error();
			if ( ! empty( $error_msg ) ) {
				$this->utils->add_message( sprintf( __( "Error processing Members List database query: %s", E20R_Members_List::plugin_slug ), $error_msg ), 'error', 'backend' );
			}

			return null;
		}
	}

	/**
	 * Generate the SQL for the page query
	 *
	 * @param int    $per_page
	 * @param int    $page_number
	 * @param string $status
	 *
	 * @return string - Returns the SQL statement
	 */
	private function generate_member_sql( $status = 'active', $per_page = -1, $page_number = -1 ) {

		$this->utils->log( "Called by: " . $this->utils->_who_called_me() );

		// Default sort order and field (membership ID)
		$order    = esc_sql( apply_filters( 'e20r_memberslist_sort_order', $this->utils->get_variable( 'order', 'DESC' ) ) );
		$order_by = esc_sql( apply_filters( 'e20r_memberslist_order_by', $this->utils->get_variable( 'orderby', 'ml.id' ) ) );

		// handle the 'last' column (which is really the enddate field when sorting/ordering)
		if ( 'last' == $order_by ) {
			$order_by = 'enddate';
		}

		// Start the SELECT statement (FIXME: Remove dependency on SQL_CALC_FOUND_ROWS - Fixed?)
		$sql = "SELECT
		";
		/*
		$sql = "SELECT SQL_CALC_FOUND_ROWS
		";
		*/
		// The columns to fetch data for
		$columns = $this->set_sql_columns();

		// Add to the SQL statement
		foreach ( $columns as $name => $alias ) {
			$sql .= "{$name} AS {$alias}, ";
		}

		// Clean up trailing comma from column list
		$sql = rtrim( $sql, ', ' );

		// Add the tables to search (and configure JOIN operations)
		$this->set_tables_and_joins();

		// Error out if something is wrong here.
		if ( false === $this->table_list ) {
			wp_die( __( "Error: Invalid list of tables & joins for member list!", E20R_Members_List::plugin_slug ) );
		}

		if ( ! empty( $this->table_list['from'] ) ) {

			$this->from = " FROM {$this->table_list['from']['name']}";
			$this->from .= ( empty( $this->table_list['from']['alias'] ) ? null : " AS {$this->table_list['from']['alias']}" );
		} else {

			wp_die( __( "Error: No FROM table specified for member list!", E20R_Members_List::plugin_slug ) );
		}

		foreach ( $this->table_list as $type => $config ) {
			if ( 'joins' === $type ) {

				// Avoid duplicate joins
				if ( ! empty( $this->joins ) ) {
					$this->joins = '';
				}

				foreach ( $config as $k => $join ) {
					$this->joins .= "\t{$join['join_type']} {$join['name']} AS {$join['alias']} {$join['condition']} \n";
				}
			}
		}

		$user_search = $this->utils->get_variable( 'find', '' );

		// Only need users who have (had) memberships
		if ( ( empty( $status ) || 'active' === $status ) && empty( $user_search ) ) {
			$this->where = " WHERE (mu.membership_id IS NOT NULL OR mu.membership_id > 0) ";
			$status      = 'active';
		} else {
			$this->where = " WHERE ";
		}


		// Is the user searching for something (meta value, user_login, email, start or end date
		if ( ! empty ( $user_search ) ) {

			$is_time = false;

			$this->utils->log( "Searching for: {$user_search}" );

			// Check if this is a date value
			if ( ! is_numeric( $user_search ) && false !== strtotime( $user_search ) ) {

				$user_search = date( 'Y-m-d', strtotime( $user_search ) );
				$is_time     = true;
			}

			// Set up the search-for part of the query (i.e. user_login, usermeta, nicename,
			// dispay_name and user_email)
			$srch_str          = esc_sql( sanitize_text_field( $user_search ) );

			$user_table_search = apply_filters(
					'e20r_memberslist_search_user_fields',
					array(
						'user_login',
						'user_nicename',
						'display_name',
						'user_email'
					)
			);

			$meta_table_fields = apply_filters(
					'e20r_memberslist_search_usermeta_fields',
					array( 'meta_value' )
			);

			// Start the search portion of the SQL statement
			$this->find = sprintf( " ( u.%s LIKE '%%%s%%'",
					array_shift( $user_table_search),
					$srch_str
			);

			// Add all user table fields to search by
			foreach( $user_table_search as $idx => $field_name ) {
				$this->find .= "OR u.${field_name} LIKE '%{$srch_str}%' ";
			}

			// Handle SQL if there's no user table fields include
			if ( ! empty($meta_table_fields) &&
				 0 === preg_match( '/ OR /', $this->find ) &&
				 0 === preg_match( '/\( ', $this->find )
			) {
				$this->find = sprintf( " ( um.%s LIKE '%%%s%%'",
						array_shift( $meta_table_fields ),
						$srch_str
				);
			} else if ( ! empty($meta_table_fields) &&
					   0 === preg_match( "/ OR /", $this->find ) &&
					   1 === preg_match( '/\( ', $this->find )
			) {
				$this->find = sprintf( " ( um.%s LIKE \'%%%s%%\'",
						array_shift( $meta_table_fields ),
					$srch_str
				);
			}

			// Add all/any metadata fields to search by
			// Frankly surprising if this is more than the meta_value field..
			foreach( $meta_table_fields as $field_name ) {
				$this->find .= "OR um.meta_value LIKE '%{$srch_str}%' ";
			}

			// Search for records by start-date or end-date
			if ( true === $is_time && 'desc' === strtolower( $order ) ) {
				$this->find .= "OR mu.startdate >= '{$srch_str} 00:00:00' OR mu.enddate >= '{$srch_str} 00:00:00' ";
			}

			if ( true === $is_time && 'asc' === strtolower( $order ) ) {
				$this->find .= "OR mu.startdate <= '{$srch_str} 00:00:00' OR mu.enddate <= '{$srch_str} 23:59:59' ";
			}

			$this->find .= ") ";
		}

		// Are they only looking for a specific membership level.
		if ( ! empty( $status ) ) {

			$this->levels = '';

			if ( ( ' WHERE ' !== $this->where || ! empty( $this->find ) ) && ( ( ! empty( $status ) || 'all' === $status ) || ! empty( $user_search ) ) ) {
				$this->levels = "AND ";
			}

			$cancelled_statuses = apply_filters( 'e20r_memberslist_cancelled_statuses', array(
				'cancelled',
				'admin_cancelled',
				'admin_change',
				'admin_changed',
				'changed',
				'inactive',
			) );

			$active_statuses  = apply_filters( 'e20r_memberslist_active_statuses', array( 'active' ) );
			$expired_statuses = apply_filters( 'e20r_memberslist_expired_statuses', array( 'expired' ) );

			$this->utils->log( "Only returning records for membership level: {$status}" );

			switch ( $status ) {

				case 'oldmembers':
					$statuses     = implode( "','", array_map( 'sanitize_text_field', $active_statuses ) );
					$this->levels .= " mu.status NOT IN ('{$statuses}') AND mu2.status IS NULL ";
					break;

				case 'expired':
					$statuses     = implode( "','", array_map( 'sanitize_text_field', $expired_statuses ) );
					$this->levels .= " mu.status IN ('{$statuses}') AND mu2.status IS NULL ";
					break;

				case 'cancelled':
					$statuses     = implode( "','", array_map( 'sanitize_text_field', $cancelled_statuses ) );
					$this->levels .= " mu.status IN ('{$statuses}') AND mu2.status IS NULL ";
					break;

				case 'all':
					$any_status = array_merge( $cancelled_statuses, $active_statuses, $expired_statuses );

					$statuses     = implode( "','", array_map( 'sanitize_text_field', $any_status ) );
					$this->levels .= " mu.status IN ('{$statuses}') ";
					break;
				case 'active':

					$statuses     = implode( "','", array_map( 'sanitize_text_field', $active_statuses ) );
					$this->levels .= " mu.status IN ('{$statuses}') ";
					break;

				default:
					$statuses     = implode( "','", array_map( 'sanitize_text_field', $active_statuses ) );
					$this->levels .= " mu.status IN ('{$statuses}') ";

					if ( ! empty( $status ) && ! in_array( $status, array( 'active', 'all' ) ) ) {
						$this->levels .= " AND mu.membership_id = " . esc_sql( $status ) . " ";
					}
			}
		}

		// Append any search & level info to the WHERE statement
		if ( ! empty( $this->find ) || ! empty( $this->levels ) ) {

			if ( ! empty( $this->find ) ) {
				$this->where .= $this->find;
			}

			if ( ! empty( $this->levels ) ) {
				$this->where .= $this->levels;
			}
		}

		$this->where    = apply_filters( 'e20r_memberslist_sql_where_statement', $this->where, $this->find, $this->levels, $this->joins );
		$this->group_by = " GROUP BY u.ID ";
		$this->order_by = apply_filters( 'e20r_memberslist_order_by_statement', " ORDER BY {$order_by} {$order}", $order_by, $order );

		// $cols     = apply_filters( 'e20r_memberslist_columnlist', $this->all_columns() );
		$per_page     = apply_filters( 'e20r_memberslist_per_page', $per_page );
		$this->offset = ( $page_number - 1 ) * $per_page;

		if ( - 1 != $per_page ) {
			$this->limit = " LIMIT {$per_page} OFFSET {$this->offset} ";
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

		// Created the SQL statement
		$sqlQuery = $sql . self::$sql_from;

		$this->utils->log( "SQL for fetching membership records:\n {$sqlQuery}" );
		return $sqlQuery;
	}

	/**
	 * Define the list of tables & joins we need to process for the member list query
	 *
	 * @return array|mixed
	 */
	private function set_tables_and_joins() {

		global $wpdb;

		$this->table_list = array(
			'from'  => array( 'name' => $wpdb->users, 'alias' => 'u' ),
			'joins' => array(
				0 => array(
					'name'      => $wpdb->pmpro_memberships_users,
					'join_type' => 'LEFT JOIN',
					'alias'     => 'mu',
					'condition' => "ON u.ID = mu.user_id AND mu.id = (SELECT mu3.id FROM {$wpdb->pmpro_memberships_users} AS mu3 WHERE mu3.user_id = u.id ORDER BY mu3.id DESC LIMIT 1)",
				),
				1 => array(
					'name'      => $wpdb->pmpro_membership_levels,
					'join_type' => 'LEFT JOIN',
					'alias'     => 'ml',
					'condition' => 'ON mu.membership_id = ml.id',
				),
				2 => array(
					'name'      => $wpdb->usermeta,
					'join_type' => 'LEFT JOIN ',
					'alias'     => 'um',
					'condition' => 'ON u.ID = um.user_id',
				),
			),
		);

		// We're searching so need to add the usermeta table.
		/* if ( null !== $this->utils->get_variable( 'find', null ) ) {
		$this->table_list['joins'][2] = array(
			'name'      => $wpdb->usermeta,
			'join_type' => 'LEFT JOIN ',
			'alias'     => 'um',
			'condition' => 'ON u.ID = um.user_id',
		);
		} */

		$search_level = $this->utils->get_variable( 'level', null );

		// We're looking for a specific membership level
		if ( in_array(
				$search_level,
				array(
						'oldmembers',
						'expired',
						'cancelled',
						'all',
						)
			 ) ||
			 is_numeric( $search_level )
		) {
			$this->table_list['joins'][3] = array(
				'name'      => $wpdb->pmpro_memberships_users,
				'join_type' => 'LEFT JOIN ',
				'alias'     => 'mu2',
				'condition' => "ON u.ID = mu2.user_id AND mu2.status = 'active'",
			);
		}

		$this->table_list = apply_filters( 'e20r_memberslist_tables_and_joins', $this->table_list );

		// FIXME: Need to (actually!) validate the table/join array (only one 'from' allowed, etc.)
		if ( true === $this->is_valid_tnj_list( $this->table_list ) ) {
			return $this->table_list;
		} else {
			pmpro_setMessage( __( "Error: Invalid configuration!!!", E20R_Members_List::plugin_slug ), "error" );
		}
	}

	/**
	 * Test validity of the table & join list.
	 *
	 * FIXME: Actually test the validity of the tables and jointed tables list
	 *
	 * @param array $list The list of tables to search
	 *
	 * @return bool     Successfully validated list of tables & joined tables.
	 */
	public function is_valid_tnj_list( $list ) {

		return true;
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

		return $wpdb->get_var( "SELECT FOUND_ROWS() AS found_rows" );
	}

	/**
	 * When exporting the members list, order by user's membership ID and email address (alphabetically)
	 *
	 * @param string $order_by
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
	 * @param string $sort_order - Default: 'ASC'
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
		return apply_filters( 'e20r_memberslist_default_sort_order', $sort_order);
	}

	/**
	 * Export specific SQL WHERE statement (for sorting)
	 *
	 * @param string $where
	 * @param string $find
	 * @param string $levels
	 * @param string $joins
	 *
	 * @return string
	 */
	public function export_member_where( $where, $find, $levels, $joins ) {

		$this->utils->log( "Requesting (active/old/etc) member export" );

		$member_ids  = $this->utils->get_variable( 'member_id', array() );
		$added_where = null;

		if ( ! empty( $where ) && ! empty( $member_ids ) ) {
			$added_where = " AND ( ";
		} else if ( empty( $where ) & ! empty( $member_ids ) ) {
			$added_where = " ( ";
		}

		$this->utils->log( "Starting appended WHERE statement: {$added_where}" );
		// Is this a bulk export operation?
		if ( ! empty( $member_ids ) && is_array( $member_ids ) ) {

			sort( $member_ids );
			$this->utils->log( "Processing list of " . count( $member_ids ) . " member IDs." );
			$in_list     = implode( ', ', $member_ids );
			$added_where .= " mu.user_id IN ( {$in_list} )";

		} else if ( ! empty( $member_ids ) ) {
			$this->utils->log( "Processing single member ID: " . count( $member_ids ) );
			$added_where .= sprintf( " mu.user_id = %d", esc_sql( $member_ids ) );
		}

		if ( ! empty( $where ) && ! empty( $member_ids ) ) {
			$added_where .= " ) ";
		}

		if ( ! empty( $added_where ) ) {
			$where .= $added_where;
		}

		return $where;
	}

	/**
	 * Update the SQL WHERE statement for the query, based on search values from the front-end (if applicable)
	 *
	 * @param string $where  - SQL Where statement
	 * @param string $find   - Search supplied from the frontend
	 * @param array  $levels - The membership level(s) selected for the front-end list
	 * @param array  $joins  - List of tables to JOIN and the JOIN type, etc.
	 *
	 * @return string
	 */
	public function metadata_where( $where, $find, $levels, $joins ) {

		$this->utils->log( "Adding search based on search form" );

		$search      = $this->utils->get_variable( 'find', '' );
		$added_where = null;

		if ( ! empty( $where ) && ! empty( $search ) ) {
			$added_where = " AND ( ";
		} else if ( empty( $where ) & ! empty( $search ) ) {
			$added_where = " ( ";
		}

		if ( ! empty( $search ) ) {
			$added_where .= "";
		}

		if ( ! empty( $where ) && ! empty( $search ) ) {
			$added_where .= " ) ";
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
			'bulk-cancel' => __( 'Cancel', E20R_Members_List::plugin_slug ),
			'bulk-update' => __( 'Update', E20R_Members_List::plugin_slug ),
			'bulk-export' => __( 'Export', E20R_Members_List::plugin_slug ),
		);

		return apply_filters( 'e20r_memberlist_bulk_actions', $actions );
	}

	/**
	 * Configures the bulk item checkbox.
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {

		return apply_filters(
			'e20r_memberslist_bulk_checkbox',
			sprintf(
				'<input type="checkbox" name="%1$s[]" value="%2$s" />',
				"{$this->_args['singular']}_id",
				$item['ID']
			)
		);
	}

	/**
	 * Configure the user_login field in table
	 *
	 * @param array $item Database record for the row being processed
	 *
	 * @return  string          Content for the cell
	 */
	public function column_user_login( $item ) {

		$user = new \WP_User( $item['ID'] );

		$edit_url = add_query_arg(
			array(
				'user_id'         => $item['ID'],
				'wp_http_referer' => urlencode( wp_get_referer() ),
			),
			get_admin_url( get_current_blog_id(), 'user-edit.php' )
		);

		$row_nonce = wp_create_nonce( 'e20r_ml_nonce' );

		$actions = array(
			/*
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
				__( 'Cancel membership', E20R_Members_List::plugin_slug ),
				__( 'Cancel', E20R_Members_List::plugin_slug )
			), */
			'update' => sprintf( '<a href="%1$s" title="%2$s" class="e20r-update-member">%3$s</a>',
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
				__( 'Update member info', E20R_Members_List::plugin_slug ),
				__( 'Update', E20R_Members_List::plugin_slug )
			),
		);

		$avatar  = get_avatar( $item['ID'], 32 );
		$actions = apply_filters( 'e20r_memberslist_user_row_actions', $actions, $user );

		$col_content = sprintf(
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

		return $col_content;
	}

	/**
	 * Configure the user's first name field in table
	 *
	 * @param array $item Database record for the row being processed
	 *
	 * @return  string          Content for the cell
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
	 * @param array $item Database record for the row being processed
	 *
	 * @return  string          Content for the cell
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
	 * @param array $item Database record for the row being processed
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
	 * @param array $item Database record for the row being processed
	 *
	 * @return  string          Content for the cell
	 */
	public function column_baddress( $item ) {

		if ( ! function_exists( 'pmpro_formatAddress' ) ) {
			return __( "Not found", E20R_Members_List::plugin_slug );
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

			$address = __( "Not found", E20R_Members_List::plugin_slug );

			if ( 0 >= intval( $item['initial_payment'] ) && 0 >= intval( $item['billing_amount'] ) ) {
				return $address = __( "N/A", E20R_Members_List::plugin_slug );
			}

		}

		return $address;
	}

	/**
	 * Configure the user's membership level field in table
	 *
	 * @param array $item Database record for the row being processed
	 *
	 * @return  string          Content for the cell
	 */
	public function column_name( $item ) {

		// These are used to configure the membership level with JavaScript
		$membership_input = sprintf(
			'
			<input type="hidden" value="%1$d" class="e20r-members-list-membership-id" name="e20r-members-list-membership_id_%2$s" />
			<input type="hidden" value="%2$d" class="e20r-members-list-user-id" name="e20r-members-list-membership_id_user_id_%2$s" />
			<input type="hidden" value="%3$s" class="e20r-members-list-membership_id-label" name="e20r-members-list-membership_label_%2$s" />
			<input type="hidden" value="%1$d" class="e20r-members-list-db-membership_id" name="e20r-members-list-db_membership_id_%2$s" />
			<input type="hidden" value="%5$d" class="e20r-members-list-db_record_id" name="e20r-members-list-db_record_id_%2$s" />
			<input type="hidden" value="%4$s" class="e20r-members-list-field-name" name="e20r-members-list-field_name_%2$s" />',
			$item['membership_id'],
			$item['ID'],
			$item['name'],
			'membership_id',
			$item['record_id']
		);

		$options = '';
		if ( function_exists( 'pmpro_getAllLevels' ) ) {
			$levels = pmpro_getAllLevels( true, true );
		} else {

			// Default info if PMPro is disabled
			$null_level           = new \stdClass();
			$null_level->level_id = 0;
			$null_level->name     = __( 'No levels found. Paid Memberships Pro is inactive!', E20R_Members_List::plugin_slug );
			$levels               = array( $null_level );
		}

		foreach ( $levels as $level ) {
			$options .= sprintf(
				            '<option value="%1$s" %2$s>%3$s</option>',
				            $level->id,
				            selected( $level->id, $item['membership_id'], false ),
				            $level->name
			            ) . "\n";
		}
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
			__( "Reset", E20R_Members_List::plugin_slug )
		);

		$value = sprintf(
			'<a href="#" class="e20r-members-list_membership_id e20r-members-list-editable" title="%1$s">%2$s<span class="dashicons dashicons-edit"></a>%3$s',
			__( "Click to edit membership level", "e20rapp" ),
			$item['name'],
			$new_membershiplevel_input
		);

		return $value;
	}

	/**
	 * Calculate the user's initial/recurring fee
	 *
	 * @param array $item Database record for the row being processed
	 *
	 * @return  string          Content for the cell
	 */
	public function column_fee( $item ) {

		$fee_string = array();

		if ( $item['initial_payment'] > 0 ) {

			$fee_string[] = sprintf( "%s", pmpro_formatPrice( (float) $item['initial_payment'] ) );
		}

		if ( $item['initial_payment'] > 0 && $item['billing_amount'] > 0 ) {
			$fee_string[] = " + <br />";
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
			$fee_string[] = apply_filters( 'e20r-memberslist-column-value-free', __( 'Free', E20R_Members_List::plugin_slug ) );
		}

		return implode( ' ', $fee_string );
	}

	/**
	 * Display the Discount Code used by the member
	 *
	 * @param array $item
	 *
	 * @return \stdClass
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
	 * @param array $item Database record for the row being processed
	 *
	 * @return  string          Content for the cell
	 */
	public function column_user_registered( $item ) {

		return sprintf( '%s', date( 'M j, \'y', strtotime( $item['user_registered'], current_time( 'timestamp' ) ) ) );
	}

	/**
	 * Display the date when the user started at the current membership level(s).
	 *
	 * @param array $item Database record for the row being processed
	 *
	 * @return  string          Content for the cell
	 */
	public function column_startdate( $item ) {

		if ( '0000-00-00 00:00:00' == $item['startdate'] || empty( $item['startdate'] ) ) {
			$date_value  = null;
			$start_label = __( 'Invalid', E20R_Members_List::plugin_slug );
		} else {
			$date_value  = ! empty( $item['startdate'] ) ? date( 'Y-m-d', strtotime( $item['startdate'], current_time( 'timestamp' ) ) ) : null;
			$start_label = date( 'M j, \'y', strtotime( $item['startdate'], current_time( 'timestamp' ) ) );
		}


		$min_val = empty( $item['startdate'] ) ? sprintf( 'min="%s"', date( 'Y-m-d', current_time( 'timestamp' ) ) ) : null;

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
			__( "Cancel", E20R_Members_List::plugin_slug )
		);

		$value = sprintf(
			'<a href="#" class="e20r-members-list_startdate e20r-members-list-editable" title="%1$s">%2$s<span class="dashicons dashicons-edit"></a>%3$s',
			__( "Edit to bulk update membership start date", "e20rapp" ),
			$start_label,
			$new_date_input
		);

		return $value;
	}

	/**
	 * Create the last column for the default Members_List table (Expiration date)
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function column_last( $item ) {

		$date_format = get_option( 'date_format' );

		if ( empty( $item['enddate'] ) || '0000-00-00 00:00:00' === $item['enddate'] ) {
			$enddate       = __( "Never", E20R_Members_List::plugin_slug );
			$enddate_label = $enddate;
		} else {
			$enddate       = date(
				$date_format,
				strtotime( $item['enddate'], current_time( 'timestamp' ) )
			);
			$enddate_label = $enddate;
		}

		// The membership level has recurring payment
		if ( ( empty( $item['enddate'] ) || '0000-00-00 00:00:00' === $item['enddate'] ) &&
		     ! empty( $item['billing_amount'] && ! empty( $item['cycle_number'] ) )
		) {
			$enddate_label = sprintf(
				__( 'N/A (%1$sNext Payment: %2$s%3$s)', E20R_Members_List::plugin_slug ),
				'<span class="e20r-members-list-small" style="font-size: 10px; font-style: italic;">',
				date(
					$date_format,
					pmpro_next_payment(
						$item['ID'],
						'success',
						'timestamp'
					)
				),
				'</span>'
			);

			$enddate = null;
		}

		$enddate = apply_filters( 'e20r-members-list-enddate-col-result', $enddate, $item );

		$date_value = ! ( empty( $item['enddate'] ) || '0000-00-00 00:00:00' === $item['enddate'] ) ? date( 'Y-m-d', strtotime( $item['enddate'], current_time( 'timestamp' ) ) ) : null;
		// $min_val    = ( empty( $item['enddate'] ) || '0000-00-00 00:00:00' === $item['enddate'] ) ? sprintf( 'min="%s"', date( 'Y-m-d', current_time( 'timestamp' ) ) ) : null;


		// These are used to configure the enddate with JavaScript
		$enddate_input = sprintf(
			'
			<input type="hidden" value="%1$s" class="e20r-members-list-membership-id" name="e20r-members-list-enddate_mid_%2$s" />
			<input type="hidden" value="%2$s" class="e20r-members-list-user-id" name="e20r-members-list-user_id_%2$s" />
			<input type="hidden" value="%3$s" class="e20r-members-list-enddate-label" name="e20r-members-list-enddatelabel_%2$s" />
			<input type="hidden" value="%4$s" class="e20r-members-list-db-enddate" name="e20r-members-list-db_enddate_%2$s" />
			<input type="hidden" value="%5$d" class="e20r-members-list-db_record_id" name="e20r-members-list-db_record_id_%2$s" />
			<input type="hidden" value="%6$s" class="e20r-members-list-field-name" name="e20r-members-list-field_name_%2$s" />',
			$item['membership_id'],
			$item['ID'],
			$enddate,
			$date_value,
			$item['record_id'],
			'enddate'
		);

		$new_date_input = sprintf(
			'<div class="ml-row-settings clearfix">
					%1$s
					<input type="date" placeholder="YYYY-MM-DD" pattern="(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))" title="Enter a date in this format YYYY-MM-DD" name="e20r-members-list-new_enddate_%2$s" class="e20r-members-list-input-enddate" value="%3$s"/>
					<br>
					<a href="#" class="e20r-members-list-cancel e20r-members-list-list-link">%4$s</a>
				</div>',
			$enddate_input,
			$item['ID'],
			$date_value,
			__( "Cancel", E20R_Members_List::plugin_slug )
		);

		$value = sprintf(
			'<a href="#" class="e20r-members-list_enddate e20r-members-list-editable" title="%1$s">%2$s<span class="dashicons dashicons-edit"></a></span>%3$s',
			__( "Bulk update membership end/expiration date", E20R_Members_List::plugin_slug ),
			$enddate_label,
			$new_date_input

		);

		return $value;
	}

	/**
	 * Configure the membership level status field
	 *
	 * @param array $item
	 *
	 * @return null|string
	 */
	public function column_status( $item ) {

		$value   = null;
		$options = '';

		$status_list = apply_filters( ' e20r_memberslist_member_status', $this->get_pmpro_statuses() );

		$status_text = explode( '_', $item['status'] );
		if ( is_array( $status_text ) ) {
			$label_text = implode( ' ', array_map( 'ucfirst', $status_text ) );
		} else {
			$label_text = ucfirst( $status_text );
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

			if ( is_array( $status_text ) ) {
				$text = implode( ' ', array_map( 'ucfirst', $status_text ) );
			} else {
				$text = ucfirst( $status_text );
			}

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
					<br>
					<a href="#" class="e20r-members-list-cancel e20r-members-list-link">%4$s</a>
				</div>',
			$status_input,
			$item['ID'],
			$options,
			__( "Reset", E20R_Members_List::plugin_slug )
		);

		$value = sprintf(
			'<a href="#" class="e20r-members-list_status e20r-members-list-editable" title="%1$s">%2$s<span class="dashicons dashicons-edit"></a></span>%3$s',
			__( "Update the member's membership status", E20R_Members_List::plugin_slug ),
			$label_text,
			$new_status_input

		);

		return $value;
	}

	/**
	 * Fetch and return all recorded status types from the DB
	 *
	 * @return array
	 */
	private function get_pmpro_statuses() {
		global $wpdb;
		global $e20r_pmpro_statuses;

		if ( empty( $e20r_pmpro_statuses ) ) {
			$e20r_pmpro_statuses = $wpdb->get_col(
					"SELECT DISTINCT mu.status FROM {$wpdb->pmpro_memberships_users} AS mu"
			);
		}

		if ( empty( $e20r_pmpro_statuses ) ) {
			$e20r_pmpro_statuses = array();
		}

		return $e20r_pmpro_statuses;
	}

	/**
	 * Handle any columns that don't have explicit handlers in this class
	 *
	 * @param object $item -   Database record for the row
	 * @param string $name -   Field/column name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $name ) {

		// To avoid warnings/notices
		$value = isset( $item[ $name ] ) ? $item[ $name ] : null;

		/**
		 * If it's not one of the Members_List default columns, apply a filter so other
		 * plugins can load their own column info.
		 */
		if ( ! in_array( $name, $this->default_columns ) ) {

			// Apply a filter for this column in the memberslist.
			$value = apply_filters( 'e20r_memberslist_custom_column', $value, $item, $name );
		}

		// Default
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

		$utils      = Utilities::get_instance();
		$search_for = $utils->get_variable( 'find', '' );
		$level      = $utils->get_variable( 'level', '' );

		$active_members_url = add_query_arg(
			array(
				'page'  => 'pmpro-memberslist',
				'level' => 'active',
				'find'  => esc_attr( $search_for ),
			),
			admin_url( 'admin.php' )
		);

		$all_members_url = add_query_arg(
			array(
				'page'  => 'pmpro-memberslist',
				'level' => 'all',
				'find'  => esc_attr( $search_for ),
			),
			admin_url( 'admin.php' )
		);

		$cancelled_url = add_query_arg(
			array(
				'page'  => 'pmpro-memberslist',
				'level' => 'cancelled',
				'find'  => esc_attr( $search_for ),
			),
			admin_url( 'admin.php' )
		);

		$expired_url = add_query_arg(
			array(
				'page'  => 'pmpro-memberslist',
				'level' => 'expired',
				'find'  => esc_attr( $search_for ),
			),
			admin_url( 'admin.php' )
		);

		$old_members_url = add_query_arg(
			array(
				'page'  => 'pmpro-memberslist',
				'level' => 'oldmembers',
				'find'  => esc_attr( $search_for ),
			),
			admin_url( 'admin.php' )
		);

		$all_users_url = add_query_arg(
			array(
				's' => esc_attr( $search_for ),
			),
			admin_url( 'users.php' )
		);

		_e( "No members found", E20R_Members_List::plugin_slug );
		?>
		<hr/>
		<div class="e20r-pmpro-memberslist-no-members-found-list">
			<p class=""><?php _e( "It's possible the information you're looking for can be found in one of the following categories:", E20R_Members_List::plugin_slug ); ?></p>
			<ul class="ul-disc">
				<?php if ( $level !== 'active' ) { ?>
					<li class="e20r-pmpro-memberslist-not-found active-members">
						<?php printf(
							__( 'Repeat search: %1$sActive Members list%2$s', E20R_Members_List::plugin_slug ),
							sprintf( '<a href="%1$s">', $active_members_url ),
							'</a>'
						); ?>
					</li>
				<?php } ?>

				<?php if ( $level !== 'all' ) { ?>
					<li class="e20r-pmpro-memberslist-not-found all-members">
						<?php printf(
							__( 'Repeat search: %1$sAll Members list%2$s', E20R_Members_List::plugin_slug ),
							sprintf( '<a href="%1$s">', $all_members_url ),
							'</a>'
						); ?>
					</li>
				<?php } ?>
				<?php if ( $level !== 'cancelled' ) { ?>
					<li class="e20r-pmpro-memberslist-not-found cancelled-members">
						<?php printf(
							__( 'Repeat search: %1$sCancelled Members list%2$s', E20R_Members_List::plugin_slug ),
							sprintf( '<a href="%1$s">', $cancelled_url ),
							'</a>'
						); ?>
					</li>
				<?php } ?>
				<?php if ( $level !== 'expired' ) { ?>
					<li class="e20r-pmpro-memberslist-not-found expired-members">
						<?php printf(
							__( 'Repeat search: %1$sExpired Members list%2$s', E20R_Members_List::plugin_slug ),
							sprintf( '<a href="%1$s">', $expired_url ),
							'</a>'
						); ?>
					</li>
				<?php } ?>
				<?php if ( $level !== 'oldmembers' ) { ?>
					<li class="e20r-pmpro-memberslist-not-found old-members">
						<?php printf(
							__( 'Repeat search: %1$sOld Members list%2$s', E20R_Members_List::plugin_slug ),
							sprintf( '<a href="%1$s">', $old_members_url ),
							'</a>'
						); ?>
					</li>
				<?php } ?>
				<li class="e20r-pmpro-memberslist-not-found all-users">
					<?php printf(
						__( 'Repeat search: %1$sAll Users list%2$s', E20R_Members_List::plugin_slug ),
						sprintf( '<a href="%1$s">', $all_users_url ),
						'</a>'
					); ?>
				</li>
			</ul>
		</div>
		<?php
	}
}
