<?php
/**
 * Copyright (c) 2018 - 2022 - Eighty / 20 Results by Wicked Strong Chicks.
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
 * @package E20R\Members_List\Admin\Export\Export_Members
 */

namespace E20R\Members_List\Admin\Export;

use E20R\Utilities\Utilities;
use stdClass;

/**
 * Handles exporting member data to CSV file.
 */
class Export_Members {

	/**
	 * The list of members to export data for.
	 *
	 * @var array $member_list
	 */
	private $member_list = array();

	/**
	 * The SQL statement used to fetch data to export.
	 *
	 * @var string|null $sql
	 */
	private $sql = null;

	/**
	 * The HTTP headers we'll use for the export operation.
	 *
	 * @var array $headers
	 */
	private $headers = array();

	/**
	 * The headers we're going to use for the CSV file.
	 *
	 * @var array $csv_headers
	 */
	private $csv_headers = array();

	/**
	 * The rows of CSV data we're processing.
	 *
	 * @var array $csv_rows
	 */
	private $csv_rows = array();

	/**
	 * The file name we use to save the CSV data.
	 *
	 * @var bool|null|string
	 */
	private $file_name = null;

	/**
	 * Cached $member discount information
	 *
	 * @var null|stdClass $member_discount_info
	 */
	private $member_discount_info = null;

	/**
	 * The list of default columns (empty by default)
	 *
	 * @var array|void $default_columns
	 */
	private $default_columns = array();

	/**
	 * List of header labels
	 *
	 * @var array|void
	 */
	private $header_map = array();
	/**
	 * Export_Members constructor.
	 *
	 * @param stdClass[] $db_records The array of records to export.
	 */
	public function __construct( $db_records ) {

		$this->member_list = is_array( $db_records ) ? $db_records : array();

		$this->file_name = $this->create_temp_file();

		$this->default_columns = array(
			array( 'wp_user', 'ID' ),
			array( 'wp_user', 'user_login' ),
			array( 'meta_values', 'first_name' ),
			array( 'meta_values', 'last_name' ),
			array( 'wp_user', 'user_email' ),
			array( 'meta_values', 'pmpro_bfirstname' ),
			array( 'meta_values', 'pmpro_blastname' ),
			array( 'meta_values', 'pmpro_baddress1' ),
			array( 'meta_values', 'pmpro_baddress2' ),
			array( 'meta_values', 'pmpro_bcity' ),
			array( 'meta_values', 'pmpro_bstate' ),
			array( 'meta_values', 'pmpro_bzipcode' ),
			array( 'meta_values', 'pmpro_bcountry' ),
			array( 'meta_values', 'pmpro_bphone' ),
			array( 'member_level', 'membership_id' ),
			array( 'pmpro_level', 'name' ),
			array( 'member_level', 'initial_payment' ),
			array( 'member_level', 'billing_amount' ),
			array( 'member_level', 'billing_limit' ),
			array( 'member_level', 'trial_amount' ),
			array( 'member_level', 'trial_limit' ),
			array( 'member_level', 'cycle_number' ),
			array( 'member_level', 'cycle_period' ),
			array( 'member_level', 'status' ),
			array( 'pmpro_discount_code', 'code_id' ),
			array( 'pmpro_discount_code', 'code' ),
			array( 'wp_user', 'user_registered' ),
			array( 'member_level', 'startdate' ),
			array( 'member_level', 'enddate' ),
		);

		$this->default_columns = apply_filters( 'pmpro_members_list_csv_default_columns', $this->default_columns );
		$this->default_columns = apply_filters( 'e20r_members_list_default_csv_columns', $this->default_columns );

		/**
		 * Map of Database column keys and CSV export column keys
		 *
		 * @filter e20r_members_list_db_type_header_map
		 */
		$this->header_map = apply_filters(
			'e20r_members_list_db_type_header_map',
			array(
				'user_id'          => array(
					'db_key'     => 'ID',
					'type'       => 'wp_user',
					'header_key' => 'ID',
				),
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

		// Generate the header for the .csv file.
		$this->csv_header();
		$this->set_upload_headers();

		/**
		 * Trigger the default 'e20r_members_list_load_export_value' filter handler first
		 */
		add_filter( 'e20r_members_list_load_export_value', array( $this, 'load_export_value' ), - 1, 3 );
	}

	/**
	 * Create the temporary file name for this export operation
	 *
	 * @return bool|string
	 */
	private function create_temp_file() {

		// Generate a temporary file to store the data in.
		$tmp_dir = sys_get_temp_dir();

		return tempnam( $tmp_dir, 'pmpro_ml_' );
	}

	/**
	 * Create the export file header (DB columns)
	 */
	private function csv_header() {

		$utils = Utilities::get_instance();
		$level = $utils->get_variable( 'membership_id', '' );

		$extra_cols = apply_filters( 'pmpro_members_list_csv_extra_columns', array() );

		if ( ! empty( $extra_cols ) ) {

			foreach ( $extra_cols as $col_header => $callback ) {
				$this->header_map[ $col_header ] = array(
					'db_key'         => $col_header,
					'type'           => 'callback',
					'header_key'     => $col_header,
					'callback_value' => $callback,
				);
			}
		}

		$header_list       = apply_filters(
			'pmpro_members_list_csv_heading',
			implode( ',', array_keys( $this->header_map ) )
		);
		$this->csv_headers = array_map( 'trim', explode( ',', $header_list ) );

		$utils->log( 'Using ' . count( $this->csv_headers ) . ' header columns' );
	}

	/**
	 * Set headers for .CSV file upload
	 */
	private function set_upload_headers() {

		$this->headers[] = 'Content-Type: text/csv';
		$this->headers[] = 'Cache-Control: max-age=0, no-cache, no-store';
		$this->headers[] = 'Pragma: no-cache';
		$this->headers[] = 'Connection: close';
		$this->headers[] = 'Content-Disposition: attachment; filename="members_list.csv"';
		$this->headers   = apply_filters( 'e20r_memberslist_http_headers', $this->headers );
	}

	/**
	 * Clear old temporary files
	 */
	public static function clear_temp_files() {

		$temp_dir_name = sys_get_temp_dir();
		$utils         = Utilities::get_instance();

		$files = glob( "{$temp_dir_name}/pmpro_ml_*.csv" );
		$now   = time();

		$utils->log( 'Notice: Clearing temporary export files (if needed)' );

		foreach ( $files as $file_name ) {

			if ( is_file( $file_name ) ) {

				if ( $now - filemtime( $file_name ) >= DAY_IN_SECONDS ) { // Delete after 1 day.
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
		$utils->log( 'Headers have been sent already..?!? ' . ( headers_sent() ? 'Yes' : 'No' ) );

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

		$extra_columns = apply_filters( 'pmpro_members_list_csv_extra_columns', array() );

		$this->add_csv_header_to_file();

		$i_start     = 0;
		$iterations  = 1;
		$users_found = is_countable( $this->member_list ) ? count( $this->member_list ) : 0;

		if ( $users_found >= $max_users_per_loop ) {
			$iterations = ceil( $users_found / $max_users_per_loop );
		}

		$start      = time();
		$end        = 0;
		$time_limit = (int) get_cfg_var( 'max_execution_time' );

		// Split up the export operation in multiple rounds/iterations.
		for ( $ic = 1; $ic <= $iterations; $ic ++ ) {

			// Try to avoid timing out during the export operation.
			if ( 0 !== $end ) {

				$iteration_diff = $end - $start;
				$new_time_limit = ceil( $iteration_diff * $iterations * 1.2 );

				if ( $time_limit < $new_time_limit ) {
					$time_limit = $new_time_limit;
					set_time_limit( $time_limit );
				}
			}

			$start = time();

			$utils->log( "For iterations: {$i_start} -> " . ( $i_start + $max_users_per_loop ) );

			$member_list = array_slice( $this->member_list, $i_start, $max_users_per_loop );

			$utils->log( 'Will process ' . count( $member_list ) . " member records in iteration {$ic}" );

			// Increment starting position.
			if ( 0 < $iterations ) {
				$i_start += $max_users_per_loop;
			}

			foreach ( $member_list as $member ) {

				$csv_record = array();

				// Cast the Member array to an object.
				$member = (object) $member;

				/**
				 * Fetch any user metadata for the user
				 */
				$member->meta_values = $this->load_user_meta( $member->ID );

				/**
				 * Fetch/update the CSV export entry for the $member
				 *
				 * @filter e20r_members_list_load_export_value
				 *
				 * @param array $csv_entry Current user value(s) for the data to be written to the CSV record
				 */
				$csv_record = apply_filters( 'e20r_members_list_load_export_value', $csv_record, $member );

				// Add data from extra columns.
				if ( ! empty( $extra_columns ) ) {

					foreach ( $extra_columns as $col_heading => $callback ) {

						$val = call_user_func( $callback, $member, $col_heading );
						$val = ! empty( $val ) ? $val : null;

						$csv_record[ $col_heading ] = $this->enclose( $val );
					}
				}

				// Add the data to the list of.
				$this->csv_rows[] = $csv_record;

			}

			wp_cache_flush();

			// need to increase max running time?
			$end = time();
		}

		do_action( 'pmpro_after_members_list_csv_export' );
	}

	/**
	 * Add the CSV header to the (new) file
	 */
	private function add_csv_header_to_file() {

		$utils = Utilities::get_instance();

		// Open our designated temporary file.
		// phpcs:ignore
		$file_handle = fopen( $this->file_name, 'a' );
		$header_type = 'header_key';

		$utils->log( 'Adding ' . count( $this->csv_headers ) . " header columns to {$this->file_name}" );

		// Add the CSV header to the file.
		// phpcs:ignore
		fprintf(
			$file_handle,
			'%s',
			implode(
				',',
				array_map(
					function ( $csv_header ) use ( $header_type ) {
						return $this->map_keys( $csv_header, $header_type );
					},
					$this->csv_headers
				)
			) . "\n"
		);

		// Close the CSV file for now.
		// phpcs:ignore
		fclose( $file_handle );
	}

	/**
	 * Returns the expected header key for the requested DB Key
	 *
	 * @param string $key The header key to find the header for.
	 * @param string $requested The requested header value.
	 * @param string $column_type The type of column it represents.
	 *
	 * @return null|string
	 */
	private function map_keys( $key, $requested, $column_type = null ) {

		$utils = Utilities::get_instance();

		foreach ( $this->header_map as $map_key => $field_def ) {

			if ( $key === $map_key ) {
				return $field_def[ $requested ];
			}

			if ( 'header_key' === $requested && $field_def['header_key'] === $key ) {

				return $map_key;
			}

			if ( 'db_key' === $requested && $field_def['db_key'] === $key ) {

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
	 * Return all the user's metadata that we (may) care about
	 *
	 * @param int $user_id The ID of the user for whom we intend to load user metadata.
	 *
	 * @return null|stdClass
	 */
	private function load_user_meta( $user_id ) {

		$meta_values = null;

		// Returns array of meta keys containing array(s) of meta_values.
		$um_values = get_user_meta( $user_id );

		// Process user metadata.
		if ( ! empty( $um_values ) ) {

			$meta_values = new stdClass();

			foreach ( $um_values as $key => $value ) {

				$meta_values->{$key} = $value[0] ?? null;
			}
		}

		return $meta_values;

	}

	/**
	 * Enclose the data we're adding to the export file
	 *
	 * @param string $text The text to quote.
	 *
	 * @return string
	 */
	private function enclose( $text ) {
		return '"' . str_replace( '"', '\\"', $text ) . '"';
	}

	/**
	 * Loads the value we're going to be exporting.
	 *
	 * @param array             $csv_record The record (CSV) to load.
	 * @param stdClass|\WP_User $member The member (user) information.
	 *
	 * @return array
	 */
	public function load_export_value( $csv_record, $member ) {

		$utils = Utilities::get_instance();

		$datetime_format = $this->set_datetime_format();
		$level           = $utils->get_variable( 'membership_id', '' );

		// Process the membership data (by column).
		foreach ( $this->default_columns as $field_def ) {

			$column_type  = $field_def[0];
			$column_name  = $this->map_keys( $field_def[1], 'db_key', $column_type );
			$column_value = $this->get_column_value(
				$member,
				$column_name,
				$column_type
			);

			// Process Join/Start dates for membership.
			if ( in_array( $column_name, array( 'user_registered', 'startdate' ), true ) ) {

				$column_value = date_i18n(
					$datetime_format,
					strtotime(
						$column_value,
						time()
					)
				);
			}

			// Process End of membership column.
			if ( 'enddate' === $column_name ) {

				$enddate_value = $column_value;

				if ( ! is_null( $member->membership_id ) && ! empty( $column_value ) ) {

					// Membership is terminated or about to be terminated.
					if (
						in_array( $level, array( 'oldmembers', 'expired', 'cancelled' ), true ) &&
						'0000-00-00 00:00:00' !== $column_value
					) {

						$enddate_value = apply_filters(
							'pmpro_memberslist_expires_column',
							date_i18n(
								$datetime_format,
								strtotime( $column_value, time() )
							),
							$member
						);

					}

					if (
						! empty( $member->membership_id ) &&
						( empty( $column_value ) || '0000-00-00 00:00:00' === $column_value )
					) {
						$enddate_value = apply_filters( 'pmpro_memberslist_expires_column', null, $member );
					}

					if (
						! empty( $member->membership_id ) &&
						( ! empty( $column_value ) && '0000-00-00 00:00:00' !== $column_value )
					) {
						$enddate_value = date_i18n(
							$datetime_format,
							strtotime(
								$column_value,
								time()
							)
						);
					}

					// Save record info for the column.
					$column_value = apply_filters( 'e20r_members_list_expires_col_value', $enddate_value, $member );

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
			if ( 'pmpro_discount_code' === $column_type && ! empty( $this->member_discount_info ) ) {

				$param        = "pmpro_discount_{$column_name}";
				$column_value = $this->member_discount_info->{$param};
			}

			if ( empty( $column_value ) ) {
				$column_value = null;
			}

			// Save the entry info.
			$csv_record[ $column_name ] = $this->enclose( $column_value );
		}

		// Clear the Discount Info (for the member).
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
		 * @filter e20r_members_list_csv_dateformat
		 *
		 * @param string $date_format
		 *
		 * @since  4.0
		 */
		$date_format = apply_filters( 'pmpro_memberslist_csv_dateformat', get_option( 'date_format' ) );
		$date_format = apply_filters( 'e20r_members_list_csv_dateformat', $date_format );

		/**
		 * Filter to the format for the time (default is the WordPress General Setting value for Time)
		 *
		 * @filter e20r_members_list_csv_timeformat
		 *
		 * @param string $time_format
		 *
		 * @since  4.0
		 */
		$time_format = apply_filters( 'e20r_members_list_csv_timeformat', get_option( 'time_format' ) );

		// Assume that we want a valid MySQL DateTime format if date is Y-m-d.
		if ( 'Y-m-d' === $date_format && 'H:i' === $time_format ) {
			$expected_format = "{$date_format} {$time_format}:00";
		} else {
			$expected_format = "{$date_format} {$time_format}";
		}

		/**
		 * Filter to the format for the time (default is the WordPress General Setting value for Time)
		 *
		 * @filter e20r_members_list_csv_datetime_format
		 *
		 * @param string $datetime_format
		 * @param string $date_format
		 * @param string $time_format
		 *
		 * @since  4.0
		 */
		return apply_filters(
			'e20r_members_list_csv_datetime_format',
			$expected_format,
			$date_format,
			$time_format
		);
	}

	/**
	 * Return a value for the specified column (name)
	 *
	 * @param stdClass $member The member record we intend to fetch data for.
	 * @param string   $column_name The column name to return data for.
	 * @param string   $column_type The type of column data we'll be returning.
	 *
	 * @return mixed|null
	 */
	private function get_column_value( $member, $column_name, $column_type ) {

		$utils       = Utilities::get_instance();
		$dc_col_name = 'pmpro_discount_code_';

		switch ( $column_type ) {

			case 'wp_user':
			case 'member_level':
			case 'pmpro_level':
			case 'callback':
				$column_value = $member->{$column_name} ?? null;
				break;

			case 'pmpro_discount_code':
				$dc_col_name  = "{$dc_col_name}{$column_name}";
				$column_value = $member->{$column_name} ?? null;
				break;

			case 'meta_values':
				$column_value = $member->meta_values->{$column_name} ?? null;
				break;

			default:
				$utils->log( "Using default type (type = {$column_type}) for {$column_name}" );
				$column_value = $member->{$column_type}->{$column_name} ?? null;

				/**
				 * Let 3rd party define the value to use for a 3rd party defined default column
				 *
				 * @filter e20r_members_list_set_default_column_value
				 *
				 * @param mixed  $column_value
				 * @param string $column_name
				 * @param string $column_type
				 * @param array  $member
				 *
				 * @since  v4.0
				 */
				$column_value = apply_filters(
					'e20r_members_list_set_default_column_value',
					$column_value,
					$column_name,
					$column_type,
					$member
				);
		}

		return $column_value;
	}

	/**
	 * Fetch discount code (ID and code) for the code ID/User ID/Level ID combination
	 *
	 * @param int $code_id The discount code ID we're collecting data for.
	 * @param int $user_id The user ID for whom we're collecting discount data.
	 * @param int $level_id The membership Level ID the member has.
	 *
	 * @return stdClass
	 */
	public static function get_pmpro_discount_code( $code_id, $user_id, $level_id ) {

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$pmpro_discount_code = $wpdb->get_row(
			$wpdb->prepare(
				"
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
			)
		);

		// Make sure there's data for the discount code info.
		if ( empty( $pmpro_discount_code ) ) {
			$empty_dc                         = new stdClass();
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
		$utils->log( 'Saving ' . count( $this->csv_rows ) . " records to {$this->file_name}. " );

		// phpcs:ignore
		$fh = fopen( $this->file_name, 'a' );

		/**
		 * The CSV data
		 *
		 * @var $row_data -> $csv_entry[ $col_heading ] = $this->enclose( $val );
		 */
		foreach ( $this->csv_rows as $row_id => $row_data ) {

			$data = array();

			foreach ( $this->csv_headers as $col_key ) {

				/* phpcs:ignore Squiz.PHP.CommentedOutCode.Found
				if ( 'ID' == $col_key ) {
					$col_key = 'user_id';
				}
				*/

				$col_name = $this->map_keys( $col_key, 'db_key' );

				$value  = $row_data[ $col_name ] ?? $this->enclose( null );
				$data[] = $value;
			}

			$file_line = implode( ',', $data ) . "\r\n";
			fprintf( $fh, '%s', $file_line );
		}
		// phpcs:ignore
		fclose( $fh );
		$utils->log( "Saved data to {$this->file_name}" );
	}

	/**
	 * Send the .CSV to the requesting browser
	 */
	public function return_content() {

		$utils = Utilities::get_instance();
		$utils->log( 'Headers have been sent already..?!? ' . ( headers_sent() ? 'Yes' : 'No' ) );

		// Problem with the HTTP headers we need to use to send a file?
		if ( empty( $this->headers ) ) {
			$msg = esc_attr__( 'Error - Undefined HTTP headers. Exiting!', 'e20r-members-list' );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			$utils->log( $msg . print_r( ob_get_contents(), true ) );
			$utils->add_message( $msg, 'error', 'backend' );
			exit();
		}

		// Problem: The browser already received headers so it can't receive this file.
		if ( true === headers_sent() ) {
			$msg = esc_attr__(
				'Cannot transmit export file. Review web server error logs for notices/warnings/errors. Exiting!',
				'e20r-members-list'
			);
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			$utils->log( $msg . print_r( ob_get_contents(), true ) );
			$utils->add_message( $msg, 'error', 'backend' );
			exit();
		}

		// The temporary export file we'll use to send data to the browser is gone?!?
		if ( ! file_exists( $this->file_name ) ) {
			$msg = esc_attr__( 'Error: No export data found to transmit...', 'e20r-members-list' );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			$utils->log( $msg . print_r( ob_get_contents(), true ) );
			$utils->add_message( $msg, 'error', 'backend' );
			exit();
		}

		// Actually send the data to the browser.
		$sent_content = ob_get_clean();

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$utils->log( 'DEBUG: Browser received: ' . print_r( $sent_content, true ) );

		if ( version_compare( phpversion(), '5.3.0', '>' ) ) {

			// Clear the file cache for the export file.
			clearstatcache( true, $this->file_name );
		} else {
			// for any PHP version prior to v5.3.0.
			clearstatcache();
		}

		// Set the download size for the file.
		$this->headers[] = sprintf( 'Content-Length: %1$s', filesize( $this->file_name ) );

		// Set transmission (PHP) headers.
		foreach ( $this->headers as $header ) {
			header( $header . "\r\n" );
		}

		// Disable compression for the duration of file download.
		if ( ini_get( 'zlib.output_compression' ) ) {
			// phpcs:ignore WordPress.PHP.IniSet.Risky
			ini_set( 'zlib.output_compression', 'Off' );
		}

		// Bug fix for Flywheel Hosted like hosts where fpassthru() is disabled.
		if ( function_exists( 'fpassthru' ) ) {
			// Open and send the file contents to the remote location.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
			$fh = fopen( $this->file_name, 'rb' );
			fpassthru( $fh );
			// phpcs:ignore
			fclose( $fh );
		} else {
			// phpcs:ignore
			readfile( $this->file_name );
		}

		// Remove the temp file.
		unlink( $this->file_name );
		exit();
	}

	/**
	 * Returns the column name to use from the specified $header_name
	 *
	 * @param string $header_name The header to retrieve the column name for.
	 *
	 * @return string
	 */
	private function map_header_to_column( $header_name ) {

		return $this->header_map[ $header_name ]['header_key'] ?? null;
	}
}
