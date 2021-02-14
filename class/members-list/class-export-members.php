<?php
/**
 * Copyright (c) 2018-2021 - Eighty / 20 Results by Wicked Strong Chicks.
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
 */

namespace E20R\Members_List\Admin;

use E20R\Members_List\Controller\E20R_Members_List;
use E20R\Utilities\E20R_Background_Process;
use E20R\Utilities\Utilities;

class Export_Members {

	/**
	 * @var array $member_list
	 */
	private $member_list = array();

	/**
	 * @var string|null $sql
	 */
	private $sql = null;

	/**
	 * @var array $headers
	 */
	private $headers = array();

	/**
	 * @var array $csv_headers
	 */
	private $csv_headers = array();

	/**
	 * @var array $csv_rows
	 */
	private $csv_rows = array();

	/**
	 * @var bool|null|string
	 */
	private $file_name = null;

	/**
	 * Cached $member discount information
	 *
	 * @var null|\stdClass $member_discount_info
	 */
	private $member_discount_info = null;

	/**
	 * Export_Members constructor.
	 *
	 * @param stdClass[] $db_records
	 */
	public function __construct( $db_records ) {

		$this->member_list = $db_records;

		$this->file_name = $this->create_temp_file();

		$this->default_columns = array(
			array( "wp_user", "ID" ),
			array( "wp_user", "user_login" ),
			array( "meta_values", "first_name" ),
			array( "meta_values", "last_name" ),
			array( "wp_user", "user_email" ),
			array( "meta_values", "pmpro_bfirstname" ),
			array( "meta_values", "pmpro_blastname" ),
			array( "meta_values", "pmpro_baddress1" ),
			array( "meta_values", "pmpro_baddress2" ),
			array( "meta_values", "pmpro_bcity" ),
			array( "meta_values", "pmpro_bstate" ),
			array( "meta_values", "pmpro_bzipcode" ),
			array( "meta_values", "pmpro_bcountry" ),
			array( "meta_values", "pmpro_bphone" ),
			array( "member_level", "membership_id" ),
			array( "pmpro_level", "name" ),
			// array( "member_level", "membership" ),
			array( "member_level", "initial_payment" ),
			array( "member_level", "billing_amount" ),
			array( "member_level", "billing_limit" ),
			array( "member_level", "trial_amount" ),
			array( "member_level", "trial_limit" ),
			array( "member_level", "cycle_number" ),
			array( "member_level", "cycle_period" ),
			array( "member_level", "status" ),
			array( "pmpro_discount_code", "code_id" ),
			array( "pmpro_discount_code", "code" ),
			array( "wp_user", 'user_registered' ),
			array( "member_level", 'startdate' ),
			array( "member_level", 'enddate' ),
		);

		$this->default_columns = apply_filters( 'pmpro_members_list_csv_default_columns', $this->default_columns );
		$this->default_columns = apply_filters( 'e20r-members-list-default-csv-columns', $this->default_columns );

		/**
		 * Map of Database column keys and CSV export column keys
		 */
		$this->header_map = apply_filters( 'e20r-members-list-db-type-header-map', array(
				'user_id'          => array( 'db_key' => 'ID', 'type' => 'wp_user', 'header_key' => 'ID' ),
				'user_login'       => array(
					'db_key'     => 'user_login',
					'type'       => 'wp_user',
					'header_key' => 'user_login',
				),
				'first_name'       => array(
					'db_key'     => 'first_name',
					'type'       => 'meta_values',
					'header_key' => 'first_name',
				),
				'last_name'        => array(
					'db_key'     => 'last_name',
					'type'       => 'meta_values',
					'header_key' => 'last_name',
				),
				'user_email'       => array(
					'db_key'     => 'user_email',
					'type'       => 'wp_user',
					'header_key' => 'user_email',
				),
				'pmpro_bfirstname' => array(
					'db_key'     => 'pmpro_bfirstname',
					'type'       => 'meta_values',
					'header_key' => 'pmpro_bfirstname',
				),
				'pmpro_blastname'  => array(
					'db_key'     => 'pmpro_blastname',
					'type'       => 'meta_values',
					'header_key' => 'pmpro_blastname',
				),
				'pmpro_baddress1'  => array(
					'db_key'     => 'pmpro_baddress1',
					'type'       => 'meta_values',
					'header_key' => 'pmpro_baddress1',
				),
				'pmpro_baddress2'  => array(
					'db_key'     => 'pmpro_baddress2',
					'type'       => 'meta_values',
					'header_key' => 'pmpro_baddress2',
				),
				'pmpro_bcity'      => array(
					'db_key'     => 'pmpro_bcity',
					'type'       => 'meta_values',
					'header_key' => 'pmpro_bcity',
				),
				'pmpro_bstate'     => array(
					'db_key'     => 'pmpro_bstate',
					'type'       => 'meta_values',
					'header_key' => 'pmpro_bstate',
				),
				'pmpro_bzipcode'   => array(
					'db_key'     => 'pmpro_bzipcode',
					'type'       => 'meta_values',
					'header_key' => 'pmpro_bzipcode',
				),
				'pmpro_bcountry'   => array(
					'db_key'     => 'pmpro_bcountry',
					'type'       => 'meta_values',
					'header_key' => 'pmpro_bcountry',
				),
				'pmpro_bphone'     => array(
					'db_key'     => 'pmpro_bphone',
					'type'       => 'meta_values',
					'header_key' => 'pmpro_bphone',
				),
				'membership_id'    => array(
					'db_key'     => 'membership_id',
					'type'       => 'member_level',
					'header_key' => 'membership_id',
				),
				'name'             => array(
					'db_key'     => 'name',
					'type'       => 'pmpro_level',
					'header_key' => 'pmpro_level_name',
				),
				'initial_payment'  => array(
					'db_key'     => 'initial_payment',
					'type'       => 'member_level',
					'header_key' => 'membership_initial_payment',
				),
				'billing_amount'   => array(
					'db_key'     => 'billing_amount',
					'type'       => 'member_level',
					'header_key' => 'membership_billing_amount',
				),
				'cycle_number'     => array(
					'db_key'     => 'cycle_number',
					'type'       => 'member_level',
					'header_key' => 'membership_cycle_number',
				),
				'cycle_period'     => array(
					'db_key'     => 'cycle_period',
					'type'       => 'member_level',
					'header_key' => 'membership_cycle_period',
				),
				'billing_limit'    => array(
					'db_key'     => 'billing_limit',
					'type'       => 'member_level',
					'header_key' => 'membership_billing_limit',
				),
				'trial_amount'     => array(
					'db_key'     => 'trial_amount',
					'type'       => 'member_level',
					'header_key' => 'membership_trial_amount',
				),
				'trial_limit'      => array(
					'db_key'     => 'trial_limit',
					'type'       => 'member_level',
					'header_key' => 'membership_trial_limit',
				),
				'status'           => array(
					'db_key'     => 'status',
					'type'       => 'member_level',
					'header_key' => 'membership_status',
				),
				'code_id'          => array(
					'db_key'     => 'code_id',
					'type'       => 'pmpro_discount_code',
					'header_key' => 'pmpro_discount_code_id',
				),
				'code'             => array(
					'db_key'     => 'code',
					'type'       => 'pmpro_discount_code',
					'header_key' => 'pmpro_discount_code',
				),
				'user_registered'  => array(
					'db_key'     => 'user_registered',
					'type'       => 'wp_user',
					'header_key' => 'user_registered',
				),
				'startdate'        => array(
					'db_key'     => 'startdate',
					'type'       => 'member_level',
					'header_key' => 'membership_startdate',
				),
				'enddate'          => array(
					'db_key'     => 'enddate',
					'type'       => 'member_level',
					'header_key' => 'membership_enddate',
				),
			)
		);

		// Generate the header for the .csv file
		$this->csv_header();
		$this->set_upload_headers();

		/**
		 * Trigger the default 'e20r-members-list-load-export-value' filter handler first
		 */
		add_filter( 'e20r-members-list-load-export-value', array( $this, 'load_export_value' ), - 1, 3 );
	}

	/**
	 * Create the temporary file name for this export operation
	 *
	 * @return bool|string
	 */
	private function create_temp_file() {

		// Generate a temporary file to store the data in.
		$tmp_dir   = sys_get_temp_dir();
		$file_name = tempnam( $tmp_dir, 'pmpro_ml_' );

		return $file_name;
	}

	/**
	 * Create the export file header (DB columns)
	 */
	private function csv_header() {

		$utils = Utilities::get_instance();
		$level = $utils->get_variable( 'membership_id', '' );

		$extra_cols = apply_filters( "pmpro_members_list_csv_extra_columns", array() );

		if ( ! empty( $extra_cols ) ) {

			foreach ( $extra_cols as $col_header => $callback ) {
				// $this->header_map[]               = $col_header;
				$this->header_map[ $col_header ] = array(
					'db_key'         => $col_header,
					'type'           => 'callback',
					'header_key'     => $col_header,
					'callback_value' => $callback,
				);
			}
		}

		$header_list       = apply_filters( 'pmpro_members_list_csv_heading', implode( ',', array_keys( $this->header_map ) ) );
		$this->csv_headers = array_map( 'trim', explode( ',', $header_list ) );

		$utils->log( "Using " . count( $this->csv_headers ) . " header columns" );
	}

	/**
	 * Set headers for .CSV file upload
	 */
	private function set_upload_headers() {

		$this->headers[] = "Content-Type: text/csv";
		$this->headers[] = "Cache-Control: max-age=0, no-cache, no-store";
		$this->headers[] = "Pragma: no-cache";
		$this->headers[] = "Connection: close";
		$this->headers[] = 'Content-Disposition: attachment; filename="members_list.csv"';
		$this->headers   = apply_filters( 'e20r-memberslist-http-headers', $this->headers );
	}

	/**
	 * Clear old temporary files
	 */
	public static function clear_temp_files() {

		$temp_dir_name = sys_get_temp_dir();
		$utils         = Utilities::get_instance();

		$files = glob( "{$temp_dir_name}/pmpro_ml_*.csv" );
		$now   = current_time( 'timestamp' );

		$utils->log( "Notice: Clearing temporary export files (if needed)" );

		foreach ( $files as $file_name ) {

			if ( is_file( $file_name ) ) {

				if ( $now - filemtime( $file_name ) >= DAY_IN_SECONDS ) { // Delete after 1 day
					unlink( $file_name );
				}
			}
		}
	}

	/**
	 * Fetches the data for the export operation
	 */
	public function get_data_list() {

		$utils = Utilities::get_instance();
		$utils->log( "Headers have been sent already..?!? " . ( headers_sent() ? 'Yes' : 'No' ) );

		/**
		 * Filter to set max number of records to process at a time
		 * for the export (helps manage memory footprint)
		 *
		 * Rule of thumb: 2000 records: ~50-60 MB of addl. memory (memory_limit needs to be between 128MB and 256MB)
		 *                4000 records: ~70-100 MB of addl. memory (memory_limit needs to be >= 256MB)
		 *                6000 records: ~100-140 MB of addl. memory (memory_limit needs to be >= 256MB)
		 *
		 * NOTE: Use the pmpro_before_members_list_csv_export hook to increase memory "on-the-fly"
		 *       Can reset with the pmpro_after_members_list_csv_export hook
		 *
		 * @since 1.8.7
		 */
		$max_users_per_loop = apply_filters( 'pmpro_set_max_user_per_export_loop', 2000 );

		/**
		 * Pre-export actions
		 *
		 * @action pmpro_before_members_list_csv_export
		 *
		 * @param array $member_list - List of Member records
		 *
		 * @since  v4.0
		 */
		do_action( 'pmpro_before_members_list_csv_export', $this->member_list );

		$extra_columns = apply_filters( "pmpro_members_list_csv_extra_columns", array() );

		$this->add_csv_header_to_file();

		$i_start     = 0;
		$iterations  = 1;
		$users_found = count( $this->member_list );

		if ( $users_found >= $max_users_per_loop ) {
			$iterations = ceil( $users_found / $max_users_per_loop );
		}

		$start      = current_time( 'timestamp' );
		$end        = 0;
		$time_limit = (int) get_cfg_var( 'max_execution_time' );

		// Split up the export operation in multiple rounds/iterations
		for ( $ic = 1; $ic <= $iterations; $ic ++ ) {

			// Try to avoid timing out during the export operation
			if ( 0 !== $end ) {

				$iteration_diff = $end - $start;
				$new_time_limit = ceil( $iteration_diff * $iterations * 1.2 );

				if ( $time_limit < $new_time_limit ) {
					$time_limit = $new_time_limit;
					set_time_limit( $time_limit );
				}
			}

			$start = current_time( 'timestamp' );

			$utils->log( "For iterations: {$i_start} -> " . ( $i_start + $max_users_per_loop ) );

			$member_list = array_slice( $this->member_list, $i_start, $max_users_per_loop );

			$utils->log( "Will process " . count( $member_list ) . " member records in iteration {$ic}" );

			// Increment starting position
			if ( 0 < $iterations ) {
				$i_start += $max_users_per_loop;
			}

			foreach ( $member_list as $member ) {

				$csv_record = array();

				// Cast the Member array to an object
				$member = (object) $member;

				/**
				 * Fetch any user metadata for the user
				 */
				$member->meta_values = $this->load_user_meta( $member->ID );

				/**
				 * Fetch/update the CSV export entry for the $member
				 *
				 * @filter e20r-members-list-load-export-value
				 *
				 * @param array $csv_entry Current user value(s) for the data to be written to the CSV record
				 */
				$csv_record = apply_filters( 'e20r-members-list-load-export-value', $csv_record, $member );

				// Add data from extra columns
				if ( ! empty( $extra_columns ) ) {

					foreach ( $extra_columns as $col_heading => $callback ) {

						$val = call_user_func( $callback, $member, $col_heading );
						$val = ! empty( $val ) ? $val : null;

						$csv_record[ $col_heading ] = $this->enclose( $val );
					}
				}

				// $utils->log( "Exportable info : " . print_r( $csv_record, true ) );

				// Add the data to the list of
				$this->csv_rows[] = $csv_record;

			}

			wp_cache_flush();

			//need to increase max running time?
			$end = current_time( 'timestamp' );
		}

		do_action( 'pmpro_after_members_list_csv_export' );
	}

	/**
	 * Add the CSV header to the (new) file
	 */
	private function add_csv_header_to_file() {

		$utils = Utilities::get_instance();

		// Open our designated temporary file
		$file_handle = fopen( $this->file_name, 'a' );
		$header_type = 'header_key';

		$utils->log( "Adding " . count( $this->csv_headers ) . " header columns to {$this->file_name}" );

		//Add the CSV header to the file
		fprintf( $file_handle, '%s',
			implode( ',',
				array_map(
					function ( $csv_header ) use ( $header_type ) {
						return $this->map_keys( $csv_header, $header_type );
					},
					$this->csv_headers )
			) . "\n" );

		// Close the CSV file for now
		fclose( $file_handle );
	}

	/**
	 * Returns the expected header key for the requested DB Key
	 *
	 * @param string $key
	 * @param string $requested
	 * @param string $column_type
	 *
	 * @return null|string
	 */
	private function map_keys( $key, $requested, $column_type = null ) {

		$utils = Utilities::get_instance();

		foreach ( $this->header_map as $map_key => $field_def ) {

			if ( $key === $map_key ) {
				return $field_def[ $requested ];
			}

			if ( 'header_key' === $requested && $field_def['header_key'] == $key ) {

				return $map_key;
			}

			if ( 'db_key' === $requested && $field_def['db_key'] == $key ) {


				if ( 'username' === $key ) {
					return $map_key;
				}

				return $field_def[ $requested ];
			}
		}

		$utils->log( "No value (key) found for {$requested} key {$key}" );

		return null;
	}

	/**
	 * Return all of the user's metadata that we (may) care about
	 *
	 * @param int $user_id
	 *
	 * @return null|\stdClass
	 */
	private function load_user_meta( $user_id ) {

		$meta_values = null;

		// Returns array of meta keys containing array(s) of meta_values.
		$um_values = get_user_meta( $user_id );

		// Process user metadata
		if ( ! empty( $um_values ) ) {

			$meta_values = new \stdClass();

			foreach ( $um_values as $key => $value ) {

				$meta_values->{$key} = isset( $value[0] ) ? $value[0] : null;
			}
		}

		return $meta_values;

	}

	/**
	 * Enclose the data we're adding to the export file
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	private function enclose( $text ) {
		return "\"" . str_replace( "\"", "\\\"", $text ) . "\"";
	}

	/**
	 * @param array     $csv_record
	 * @param \stdClass $member
	 *
	 * @return array
	 */
	public function load_export_value( $csv_record, $member ) {

		$utils = Utilities::get_instance();

		$datetime_format = $this->set_datetime_format();
		$level           = $utils->get_variable( 'membership_id', '' );

		// $utils->log( "For member: " . print_r( $member, true ) );

		// Process the membership data (by column)
		foreach ( $this->default_columns as $field_def ) {

			$column_type  = $field_def[0];
			$column_name  = $this->map_keys( $field_def[1], 'db_key', $column_type );
			$column_value = $this->get_column_value(
				$member,
				$column_name,
				$column_type
			);

			// Process Join/Start dates for membership
			if ( in_array( $column_name, array( 'user_registered', 'startdate' ) ) ) {

				$column_value = date(
					$datetime_format,
					strtotime( $column_value,
						current_time( 'timestamp' )
					)
				);
			}

			// Process End of membership column
			if ( 'enddate' == $column_name ) {

				$enddate_value = $column_value;

				if ( ! is_null( $member->membership_id ) && ! empty( $column_value ) ) {

					// Membership is terminated or about to be terminated
					if ( in_array( $level, array( "oldmembers", "expired", 'cancelled' ) ) &&
					     ( ! empty( $column_value ) && '0000-00-00 00:00:00' !== $column_value ) ) {

						$enddate_value = apply_filters( "pmpro_memberslist_expires_column", date( $datetime_format, strtotime( $column_value, current_time( 'timestamp' ) ) ), $member );

					}

					if ( ! empty( $member->membership_id ) && ( empty( $column_value ) || '0000-00-00 00:00:00' === $column_value ) ) {
						$enddate_value = apply_filters( "pmpro_memberslist_expires_column", null, $member );
					}

					if ( ! empty( $member->membership_id ) && ( ! empty( $column_value ) && '0000-00-00 00:00:00' !== $column_value ) ) {
						$enddate_value = date( $datetime_format, strtotime( $column_value, current_time( 'timestamp' ) ) );
					}

					// Save record info for the column
					$column_value = apply_filters( 'e20r-members-list-expires-col-value', $enddate_value, $member );;
				}
			}

			/**
			 * Fetch the discount code data for this user/member
			 */
			if ( ! empty( $member->code_id ) && empty( $this->member_discount_info ) ) {

				$utils->log( "Grab the discount code data for {$member->ID}/{$member->code_id}" );

				$this->member_discount_info = self::get_pmpro_discount_code(
					$member->code_id,
					$member->ID,
					$member->membership_id
				);
			}

			/**
			 * Fetch the discount code value for the user/member
			 */
			if ( $column_type === 'pmpro_discount_code' && ! empty( $this->member_discount_info ) ) {

				$param        = "pmpro_discount_{$column_name}";
				$column_value = $this->member_discount_info->{$param};
			}


			if ( empty( $column_value ) ) {
				$column_value = null;
			}

			// $utils->log( "Saving {$column_name} (looked for {$field_def[1]}): " . print_r( $column_value, true ) );

			// Save the entry info
			$csv_record[ $column_name ] = $this->enclose( $column_value );
		}

		// Clear the Discount Info (for the member)
		$this->member_discount_info = null;

		return $csv_record;
	}

	/**
	 * Configure the format to use for the export datetime value
	 *
	 * @return string
	 */
	private function set_datetime_format() {

		/**
		 * Filter to the format for the date (default is the WordPress General Setting value for Date)
		 *
		 * @filter e20r-members-list-csv-dateformat
		 *
		 * @param string $date_format
		 *
		 * @since  4.0
		 */
		$date_format = apply_filters( 'pmpro_memberslist_csv_dateformat', get_option( 'date_format' ) );
		$date_format = apply_filters( 'e20r-members-list-csv-fateformat', $date_format );

		/**
		 * Filter to the format for the time (default is the WordPress General Setting value for Time)
		 *
		 * @filter e20r-members-list-csv-timeformat
		 *
		 * @param string $time_format
		 *
		 * @since  4.0
		 */
		$time_format = apply_filters( 'e20r-members-list-csv-timeformat', get_option( 'time_format' ) );

		// Assume that we want a valid MySQL DateTime format if date is Y-m-d
		if ( 'Y-m-d' == $date_format && 'H:i' == $time_format ) {
			$expected_format = "{$date_format} {$time_format}:00";
		} else {
			$expected_format = "{$date_format} {$time_format}";
		}

		/**
		 * Filter to the format for the time (default is the WordPress General Setting value for Time)
		 *
		 * @filter e20r-members-list-csv-datetime-format
		 *
		 * @param string $datetime_format
		 * @param string $date_format
		 * @param string $time_format
		 *
		 * @since  4.0
		 */
		$datetime_format = apply_filters( 'e20r-members-list-csv-datetime-format', $expected_format, $date_format, $time_format );

		return $datetime_format;
	}

	/**
	 * Return a value for the specified column (name)
	 *
	 * @param \stdClass $member
	 * @param string    $column_name
	 * @param string    $column_type
	 *
	 * @return mixed|null
	 */
	private function get_column_value( $member, $column_name, $column_type ) {

		$utils       = Utilities::get_instance();
		$dc_col_name = "pmpro_discount_code_";

		switch ( $column_type ) {

			case 'wp_user':
			case 'member_level':
			case 'pmpro_level':
			case 'callback':
				$column_value = isset( $member->{$column_name} ) ? $member->{$column_name} : null;
				break;

			case 'pmpro_discount_code':
				$dc_col_name  = "{$dc_col_name}{$column_name}";
				$column_value = isset( $member->{$column_name} ) ? $member->{$column_name} : null;
				break;

			case 'meta_values':
				$column_value = isset( $member->meta_values->{$column_name} ) ? $member->meta_values->{$column_name} : null;
				break;

			default:
				$utils->log( "Using default type (type = {$column_type}) for {$column_name}" );
				$column_value = isset( $member->{$column_type}->{$column_name} ) ? $member->{$column_type}->{$column_name} : null;

				/**
				 * Let 3rd party define the value to use for a 3rd party defined default column
				 *
				 * @filter e20r-members-list-set-default-column-value
				 *
				 * @param mixed  $column_value
				 * @param string $column_name
				 * @param string $column_type
				 * @param array  $member
				 *
				 * @since  v4.0
				 */
				$column_value = apply_filters( 'e20r-members-list-set-default-column-value', $column_value, $column_name, $column_type, $member );
		}

		return $column_value;
	}

	/**
	 * Fetch discount code (ID and code) for the code ID/User ID/Level ID combination
	 *
	 * @param int $code_id
	 * @param int $user_id
	 * @param int $level_id
	 *
	 * @return \stdClass
	 */
	public static function get_pmpro_discount_code( $code_id, $user_id, $level_id ) {

		global $wpdb;

		$disSql = $wpdb->prepare( "
				SELECT
					c.id AS pmpro_discount_code_id,
					c.code AS pmpro_discount_code
				FROM {$wpdb->pmpro_discount_codes_uses} AS cu
				LEFT JOIN {$wpdb->pmpro_discount_codes} AS c ON cu.code_id = c.id
				WHERE c.id = %d AND cu.user_id = %d
				ORDER BY c.id DESC
				LIMIT 1",
			$code_id,
			$user_id
		);

		$pmpro_discount_code = $wpdb->get_row( $disSql );

		// Make sure there's data for the discount code info
		if ( empty( $pmpro_discount_code ) ) {
			$empty_dc                         = new \stdClass();
			$empty_dc->pmpro_discount_code_id = null;
			$empty_dc->pmpro_discount_code    = null;
			$pmpro_discount_code              = $empty_dc;
		}

		return $pmpro_discount_code;
	}

	/**
	 * Save the data rows to the temporary Export file
	 */
	public function save_data_for_export() {

		$utils = Utilities::get_instance();
		$utils->log( "Saving " . count( $this->csv_rows ) . " records to {$this->file_name}. " );

		$fh = fopen( $this->file_name, 'a' );

		/**
		 * @var $row_data -> $csv_entry[ $col_heading ] = $this->enclose( $val );
		 */
		foreach ( $this->csv_rows as $row_id => $row_data ) {

			$data = array();

			foreach ( $this->csv_headers as $col_key ) {

				/*
				if ( 'ID' == $col_key ) {
					$col_key = 'user_id';
				}
				*/

				$col_name = $this->map_keys( $col_key, 'db_key' );

				$value  = isset( $row_data[ $col_name ] ) ? $row_data[ $col_name ] : $this->enclose( null );
				$data[] = $value;
			}

			$file_line = implode( ',', $data ) . "\r\n";
			fprintf( $fh, '%s', $file_line );
		}

		fclose( $fh );
		$utils->log( "Saved data to {$this->file_name}" );
	}

	/**
	 * Send the .CSV to the requesting browser
	 */
	public function return_content() {

		$utils = Utilities::get_instance();
		$utils->log( "Headers have been sent already..?!? " . ( headers_sent() ? 'Yes' : 'No' ) );
		// false === headers_sent() &&
		// Send the data to the recipient browser
		if ( ! empty( $this->headers ) && false === headers_sent() && file_exists( $this->file_name ) ) {

			$sent_content = ob_get_clean();
			$utils->log("Browser received: " . print_r( $sent_content, true) );

			if ( version_compare( phpversion(), '5.3.0', '>' ) ) {

				//Clear the file cache for the export file
				clearstatcache( true, $this->file_name );
			} else {
				// for any PHP version prior to v5.3.0
				clearstatcache();
			}

			//Set the download size for the file
			$this->headers[] = "Content-Length: " . filesize( $this->file_name );

			//Set transmission (PHP) headers
			foreach ( $this->headers as $header ) {
				header( $header . "\r\n" );
			}

			// Disable compression for the duration of file download
			if ( ini_get( 'zlib.output_compression' ) ) {
				ini_set( 'zlib.output_compression', 'Off' );
			}

			// Bug fix for Flywheel Hosted like hosts where fpassthru() is disabled
			if ( function_exists( 'fpassthru' ) ) {
				// Open and send the file contents to the remote location
				$fh = fopen( $this->file_name, 'rb' );
				fpassthru( $fh );
				fclose( $fh );
			} else {
				readfile( $this->file_name );
			}

			// Remove the temp file
			unlink( $this->file_name );
			exit();

		} else {
			$msg = __( "Unable to send the .CSV file to your browser!", 'e20r-members-list' );
			$utils->log( $msg . print_r( ob_get_contents(), true ) );
			$utils->add_message( $msg, 'error', 'backend' );
		}
	}

	/**
	 * Returns the column name to use from the specified $header_name
	 *
	 * @param $header_name
	 *
	 * @return mixed
	 */
	private function map_header_to_column( $header_name ) {

		return isset( $this->header_map[ $header_name ]['header_key'] ) ? $this->header_map[ $header_name ]['header_key'] : null;
	}
}
