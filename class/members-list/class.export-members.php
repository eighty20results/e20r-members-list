<?php
/**
 * *
 *   * Copyright (c) 2018. - Eighty / 20 Results by Wicked Strong Chicks.
 *   * ALL RIGHTS RESERVED
 *   *
 *   * This program is free software: you can redistribute it and/or modify
 *   * it under the terms of the GNU General Public License as published by
 *   * the Free Software Foundation, either version 3 of the License, or
 *   * (at your option) any later version.
 *   *
 *   * This program is distributed in the hope that it will be useful,
 *   * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   * GNU General Public License for more details.
 *   *
 *   * You should have received a copy of the GNU General Public License
 *   * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Copyright (c) $today.year. - Eighty / 20 Results by Wicked Strong Chicks.
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
	
	public function __construct( $db_records ) {
		
		$this->member_list = $db_records;
		
		$this->file_name = $this->create_temp_file();
		
		$this->default_columns = array(
			array( "theuser", "ID" ),
			array( "theuser", "user_login" ),
			array( "metavalues", "first_name" ),
			array( "metavalues", "last_name" ),
			array( "theuser", "user_email" ),
			array( "metavalues", "pmpro_bfirstname" ),
			array( "metavalues", "pmpro_blastname" ),
			array( "metavalues", "pmpro_baddress1" ),
			array( "metavalues", "pmpro_baddress2" ),
			array( "metavalues", "pmpro_bcity" ),
			array( "metavalues", "pmpro_bstate" ),
			array( "metavalues", "pmpro_bzipcode" ),
			array( "metavalues", "pmpro_bcountry" ),
			array( "metavalues", "pmpro_bphone" ),
			array( "theuser", "membership" ),
			array( "theuser", "initial_payment" ),
			array( "theuser", "billing_amount" ),
			array( "theuser", "cycle_period" ),
			array( "discount_code", "id" ),
			array( "discount_code", "code" ),
			array( "theuser", 'registered' ),
			array( "theuser", 'membership start' ),
			array( "theuser", 'membership end' ),
			array( "theuser", 'membership expired' ),
		);
		
		$this->default_columns = apply_filters( 'pmpro_members_list_csv_default_columns', $this->default_columns );
		
		$this->header_map = array(
			'user_id'            => array( 'db_key' => 'ID', 'type' => 'theuser', 'header_key' => 'user_id' ),
			'username'           => array( 'db_key' => 'user_login', 'type' => 'theuser', 'header_key' => 'username' ),
			'firstname'          => array(
				'db_key'     => 'first_name',
				'type'       => 'meta_values',
				'header_key' => 'first_name',
			),
			'lastname'           => array(
				'db_key'     => 'last_name',
				'type'       => 'meta_values',
				'header_key' => 'last_name',
			),
			'email'              => array( 'db_key'     => 'user_email',
			                               'type'       => 'theuser',
			                               'header_key' => 'user_email',
			),
			'billing firstname'  => array(
				'db_key'     => 'pmpro_bfirstname',
				'type'       => 'meta_values',
				'header_key' => 'pmpro_bfirstname',
			),
			'billing lastname'   => array(
				'db_key'     => 'pmpro_blastname',
				'type'       => 'meta_values',
				'header_key' => 'pmpro_blastname',
			),
			'address1'           => array(
				'db_key'     => 'pmpro_baddress1',
				'type'       => 'meta_values',
				'header_key' => 'pmpro_baddress1',
			),
			'address2'           => array(
				'db_key'     => 'pmpro_baddress2',
				'type'       => 'meta_values',
				'header_key' => 'pmpro_baddress2',
			),
			'city'               => array(
				'db_key'     => 'pmpro_bcity',
				'type'       => 'meta_values',
				'header_key' => 'pmpro_bcity',
			),
			'state'              => array(
				'db_key'     => 'pmpro_bstate',
				'type'       => 'meta_values',
				'header_key' => 'pmpro_bstate',
			),
			'zipcode'            => array(
				'db_key'     => 'pmpro_bzipcode',
				'type'       => 'meta_values',
				'header_key' => 'pmpro_bzipcode',
			),
			'country'            => array(
				'db_key'     => 'pmpro_bcountry',
				'type'       => 'meta_values',
				'header_key' => 'pmpro_bcountry',
			),
			'phone'              => array(
				'db_key'     => 'pmpro_bphone',
				'type'       => 'meta_values',
				'header_key' => 'pmpro_bphone',
			),
			'membership'         => array( 'db_key'     => 'membership',
			                               'type'       => 'theuser',
			                               'header_key' => 'membership',
			),
			'initial payment'    => array(
				'db_key'     => 'initial_payment',
				'type'       => 'theuser',
				'header_key' => 'initial_payment',
			),
			'fee'                => array(
				'db_key'     => 'billing_amount',
				'type'       => 'theuser',
				'header_key' => 'recurring_payment',
			),
			'term'               => array(
				'db_key'     => 'cycle_period',
				'type'       => 'theuser',
				'header_key' => 'billing_period_name',
			),
			'discount_code_id'   => array(
				'db_key'     => 'id',
				'type'       => 'discount_code',
				'header_key' => 'discount_code_id',
			),
			'discount_code'      => array(
				'db_key'     => 'code',
				'type'       => 'discount_code',
				'header_key' => 'discount_code',
			),
			'registered'         => array( 'db_key' => 'registered', 'type' => 'theuser', 'header_key' => 'joindate' ),
			'membership start'   => array(
				'db_key'     => 'membership start',
				'type'       => 'meta_values',
				'header_key' => 'startdate',
			),
			'membership expired' => array( 'db_key'     => 'membership expired',
			                               'type'       => 'meta_values',
			                               'header_key' => 'enddate',
			),
			'membership end'     => array( 'db_key'     => 'membership end',
			                               'type'       => 'meta_values',
			                               'header_key' => 'enddate',
			),
		);
		
		// Generate the header for the .csv file
		$this->csv_header();
		$this->set_upload_headers();
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
		
		$header_list = "user_id,user_loginname,first_name,last_name,user_email,pmpro_bfirstname,pmpro_blastname,pmpro_baddress1,pmpro_baddress2,pmpro_bcity,pmpro_bstate,pmpro_bzipcode,pmpro_bcountry,pmpro_bphone,membership_id,membership_initial_payment,membership_billing_amount,membership_cycle_period,membership_code_id,discount_code,user_registered,membership_startdate";
		
		if ( in_array( $level, array( "oldmembers", "expired", 'cancelled' ) ) ) {
			$header_list .= ",membership_enddate";
		} else {
			$header_list .= ",membership_enddate";
		}
		
		$extra_cols = apply_filters( "pmpro_members_list_csv_extra_columns", array() );
		
		if ( ! empty( $extra_cols ) ) {
			
			foreach ( $extra_cols as $col_header => $callback ) {
				$header_list             .= ",{$col_header}";
				$this->header_map[ $col_header ] = array(
					'db_key' => null,
					'type' => 'callback',
					'header_key' => $col_header,
					'callback_value' => $callback
				);
			}
		}
		
		$header_list       = apply_filters( 'pmpro_members_list_csv_heading', $header_list );
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
		$date_format        = apply_filters( 'pmpro_memberslist_csv_dateformat', get_option( 'date_format' ) );
		$level              = $utils->get_variable( 'membership_id', '' );
		
		do_action( 'pmpro_before_members_list_csv_export', $this->member_list );
		
		$extra_columns = apply_filters( "pmpro_members_list_csv_extra_columns", array() );
		
		$this->add_csv_header_to_file();
		
		$i_start     = 0;
		$i_limit     = 0;
		$iterations  = 1;
		$users_found = count( $this->member_list );
		
		if ( $users_found >= $max_users_per_loop ) {
			$iterations = ceil( $users_found / $max_users_per_loop );
		}
		
		$start      = current_time( 'timestamp' );
		$end        = 0;
		$time_limit = ini_get( 'max_execution_time' );
		
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
				
				$csv_entry = array();
				
				// Cast the Member array to an object
				$member = (object) $member;
				
				/**
				 * Fetch any user metadata for the user
				 */
				$member->meta_values = $this->load_user_meta( $member->user_id );
				
				/**
				 * Fetch (any) discount code for the user/member (will run twice, probably
				 */
				if ( ! empty( $member->discount_code_id ) ) {
					
					$utils->log( "Grab the discount code data for {$member->user_id}/{$member->discount_code_id}" );
					$member->discount_code = $this->get_discount_code( $member->discount_code_id, $member->user_id, $member->membership_id );
				}
				
				// Process the membership data (by column)
				foreach ( $this->default_columns as $field_def ) {
					
					$type        = $field_def[0];
					$column_name = $this->map_db_keys( $field_def[1] );
					
					// Get the correct data (value)
					if ( 'theuser' === $type ) {
						$column_value = isset( $member->{$column_name} ) ? $member->{$column_name} : null;
					} else if ( 'metavalues' === $type ) { // Backwards compatible with PMPro's export to CSV function
						$column_value = isset( $member->meta_values->{$column_name} ) ? $member->meta_values->{$column_name} : null;
					} else {
						$column_value = isset( $member->{$type}->{$column_name} ) ? $member->{$type}->{$column_name} : null;
					}
					
					// Process Join/Start dates for membership
					if ( in_array( $column_name, array( 'registered', 'membership start' ) ) ) {
						$csv_entry[ $column_name ] = $this->enclose(
							date(
								$date_format,
								strtotime( $column_value,
									current_time( 'timestamp' )
								)
							)
						);
						
						// Next column
						continue;
					}
					
					// Process End of membership column
					if ( 'membership end' === $column_name && ! isset( $csv_entry[ $column_name ] ) ) {
						
						if ( ! is_null( $member->membership_id ) && ! empty( $column_value ) ) {
							
							// Membership is terminated or about to be terminated
							if ( in_array( $level, array( "oldmembers", "expired", 'cancelled' ) ) &&
							     ( ! empty( $column_value ) && '0000-00-00 00:00:00' !== $column_value ) ) {
								
								$enddate_value = apply_filters( "pmpro_memberslist_expires_column", date( $date_format, strtotime( $column_value, current_time( 'timestamp' ) ) ), $member );
								
							}
							
							if ( ! empty( $member->membership_id ) && ( empty( $column_value ) || '0000-00-00 00:00:00' === $column_value ) ) {
								$enddate_value = apply_filters( "pmpro_memberslist_expires_column", __( "Never", 'e20r-members-list' ), $member );
							}
							
							if ( ! empty( $member->membership_id ) && ( ! empty( $column_value ) && '0000-00-00 00:00:00' !== $column_value ) ) {
								$enddate_value = date( $date_format, strtotime( $column_value, current_time( 'timestamp' ) ) );
							}
							
							$csv_entry[ $column_name ] = $this->enclose( $enddate_value );
							continue;
						}
						
					}
					
					$csv_entry[ $column_name ] = $this->enclose( $column_value );
					
					if ( $type === 'discount_code' && ! empty( $member->discount_code_id ) ) {
						
						$utils->log( "Discount data for {$member->user_id}: {$column_name}/{$column_value}" );
					}
				}
				
				// Add data from extra columns
				if ( ! empty( $extra_columns ) ) {
					
					foreach ( $extra_columns as $col_heading => $callback ) {
						
						$val = call_user_func( $callback, $member, $col_heading );
						$val = ! empty( $val ) ? $val : null;
						
						$csv_entry[ $col_heading ] = $this->enclose( $val );
					}
				}
				
				// Add the data to the list of
				$this->csv_rows[] = $csv_entry;
				
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
		
		$utils->log( "Adding " . count( $this->csv_headers ) . " header columns to {$this->file_name}" );
		
		//Add the CSV header to the file
		fprintf( $file_handle, '%s', implode( ',', $this->csv_headers ) . "\n" );
		
		// Close the CSV file for now
		fclose( $file_handle );
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
		
		// Returns array of meta keys containing array(s) of metavalues.
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
	 * Fetch discount code (ID and code) for the code ID/User ID/Level ID combination
	 *
	 * @param $code_id
	 * @param $user_id
	 * @param $level_id
	 *
	 * @return \stdClass
	 */
	private function get_discount_code( $code_id, $user_id, $level_id ) {
		global $wpdb;
		
		$disSql = $wpdb->prepare( "
				SELECT
					c.id AS discount_code_id,
					c.code AS discount_code
				FROM {$wpdb->pmpro_discount_codes_uses} AS cu
				LEFT JOIN {$wpdb->pmpro_discount_codes} AS c ON cu.code_id = c.id
				WHERE cu.id = %d AND cu.user_id = %d
				ORDER BY c.id DESC
				LIMIT 1",
			$code_id,
			$user_id
		);
		
		$discount_code = $wpdb->get_row( $disSql );
		
		// Make sure there's data for the discount code info
		if ( empty( $discount_code ) ) {
			$empty_dc                   = new \stdClass();
			$empty_dc->discount_code_id = null;
			$empty_dc->discount_code    = null;
			$discount_code              = $empty_dc;
		}
		
		return $discount_code;
	}
	
	/**
	 * Returns the expected header key for the requested DB Key
	 *
	 * @param string $db_key
	 *
	 * @return null|string
	 */
	private function map_db_keys( $db_key ) {
		
		foreach ( $this->header_map as $header_key => $field_def ) {
			
			if ( $field_def['db_key'] == $db_key ) {
				return $field_def['header_key'];
			}
		}
		
		return null;
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
	 * Save the data rows to the temporary Export file
	 */
	public function save_data_for_export() {
		
		$utils = Utilities::get_instance();
		$utils->log( "Saving " . count( $this->csv_rows ) . " records to {$this->file_name}. " );
		
		$fh = fopen( $this->file_name, 'a' );
		
		foreach ( $this->csv_rows as $row_id => $row_data ) {
			
			$data = array();
			foreach ( $this->csv_headers as $col_key ) {
				
				$col_name = $this->map_header_to_column( $col_key );
				
				$data[]   = isset( $row_data[ $col_name ] ) ? $row_data[$col_name] : $this->enclose( null );
			}
			
			$file_line = implode( ',', $data ) . "\r\n";
			fprintf( $fh, '%s', $file_line );
		}
		
		fclose( $fh );
		$utils->log( "Saved data to {$this->file_name}" );
	}
	
	/**
	 * Returns the column name to use from the specified $header_name
	 *
	 * @param $header_name
	 *
	 * @return mixed
	 */
	private function map_header_to_column( $header_name ) {
		
		return isset( $this->header_map[ $header_name ]['header_key'] ) ? $this->header_map[$header_name]['header_key'] : null;
	}
	
	/**
	 * Send the .CSV to the requesting browser
	 */
	public function return_content() {
		
		// Send the data to the recipent browser
		if ( ! empty( $this->headers ) && false === headers_sent() && file_exists( $this->file_name ) ) {
			
			ob_get_clean();
			
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
			
			// Open and send the file contents to the remote location
			$fh = fopen( $this->file_name, 'rb' );
			fpassthru( $fh );
			fclose( $fh );
			
			// Remove the temp file
			unlink( $this->file_name );
			exit();
			
		} else {
			$utils = Utilities::get_instance();
			$msg = __( "Cannot transmit the .CSV file to the user's browser!", 'e20r-members-list' );
			$utils->log( $msg . print_r( ob_get_contents(), true ) );
			$utils->add_message( $msg, 'error', 'backend' );
		}
	}
}