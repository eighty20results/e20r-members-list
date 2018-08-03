<?php
/**
 * Copyright 2017-2018 Eighty / 20 Results by Wicked Strong Chicks, LLC (thomas@eighty20results.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * Thanks to @A5hleyRich at https://github.com/A5hleyRich/wp-background-processing
 */

namespace E20R\Utilities;

/**
 * WP Background Process
 *
 * @package E20R-Background-Processing
 *
 * @credit  A5hleyRich at https://github.com/A5hleyRich/wp-background-processing
 * @since   v1.9.6 - ENHANCEMENT: Added fixes and updates from EWWW Image Optimizer code
 * @since   1.9.13 - BUG FIX: Would sometimes double up on the entry count in the queue
 * @since   1.9.14 - BUG FIX: Minor nits to make code more readable
 * @since   1.9.15 - ENHANCEMENT: Added is_queue_good() method to verify content of queue
 * @since   1.9.15 - ENHANCEMENT: Exit maybe_handle() if queue is invalid (clear the queue too)
 */

/**
 * Abstract E20R_Background_Process class.
 *
 * @abstract
 * @extends E20R_Async_Request
 */
abstract class E20R_Background_Process extends E20R_Async_Request {
	/**
	 * Action
	 *
	 * (default value: 'background_process')
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'background_process';
	/**
	 * Start time of current process.
	 *
	 * (default value: 0)
	 *
	 * @var int
	 * @access protected
	 */
	protected $start_time = 0;
	/**
	 * Cron_hook_identifier
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $cron_hook_identifier;
	/**
	 * Cron_interval_identifier
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $cron_interval_identifier;
	
	/**
	 * @var string $active_queue - Either 'a' or 'b' depending on running queue
	 */
	protected $active_queue;
	
	/**
	 * @var string $second_queue - Either 'b' or 'a', depending on running queue
	 */
	protected $second_queue;
	
	/**
	 * Lock duration for queue
	 *
	 * @var int
	 */
	protected $queue_lock_time = 60;
	
	/**
	 * Initiate new background process
	 */
	public function __construct() {
		
		parent::__construct();
		
		$this->cron_hook_identifier     = $this->identifier . '_cron';
		$this->cron_interval_identifier = $this->identifier . '_cron_interval';
		
		add_action( $this->cron_hook_identifier, array( $this, 'handle_cron_healthcheck' ) );
		add_filter( 'cron_schedules', array( $this, 'schedule_cron_healthcheck' ) );
	}
	
	/**
	 * Dispatch
	 *
	 * @access public
	 * @return mixed
	 */
	public function dispatch() {
		
		// Schedule the cron healthcheck.
		$this->schedule_event();
		
		// Perform remote post.
		return parent::dispatch();
	}
	
	/**
	 * Push to queue
	 *
	 * @param mixed $data Data.
	 *
	 * @return $this
	 */
	public function push_to_queue( $data ) {
		
		$this->data[] = $data;
		
		return $this;
	}
	
	/**
	 * Save and clear queue
	 *
	 * @return $this
	 *
	 * @since 1.9.13 - BUG FIX: Would sometimes double up on the entry count in the queue
	 */
	public function save() {
		
		$key   = $this->generate_key();
		$utils = Utilities::get_instance();
		
		if ( ! empty( $this->data ) ) {
			
			$utils->log( "Found " . count( $this->data ) . " items in the queue to save/process for {$key}" );
			/*
			$existing_data = get_option( $key );
			
			if ( ! empty( $existing_data ) ) {
				$utils->log("Have to add " . count( $existing_data ) . " entries from {$key}");
				$this->data = array_merge( $existing_data, $this->data );
			}
			*/
			delete_option( $key );
			update_option( $key, $this->data, 'no' );
		}
		
		// Clear the data list (will load before processing anyway)
		$this->data = array();
		
		return $this;
	}
	
	/**
	 * Update queue
	 *
	 * @param string $key  Key.
	 * @param array  $data Data.
	 *
	 * @return $this
	 */
	public function update( $key, $data ) {
		
		if ( ! empty( $data ) ) {
			
			$existing_data = get_option( $key );
			
			if ( ! empty( $existing_data ) ) {
				update_option( $key, $data, 'no' );
			}
		}
		
		return $this;
	}
	
	/**
	 * Delete queue
	 *
	 * @param string $key Key.
	 *
	 * @return $this
	 */
	public function delete( $key ) {
		
		update_option( $key, '', 'no' );
		
		return $this;
	}
	
	/**
	 * Generate key
	 *
	 * Generates a unique key based on microtime. Queue items are
	 * given a unique key so that they can be merged upon save.
	 *
	 * @param int $length Length.
	 *
	 * @return string
	 */
	protected function generate_key( $length = 64 ) {
		
		// $unique  = md5( microtime() . rand() );
		$unique = 'a';
		
		if ( $this->is_queue_active( $unique ) ) {
			$unique = 'b';
		}
		
		$this->second_queue = $unique;
		$prepend            = $this->identifier . '_batch_';
		
		return substr( $prepend . $unique, 0, $length );
	}
	
	/**
	 * Maybe process queue
	 *
	 * Checks whether data exists within the queue and that
	 * the process is not already running.
	 
	 * @since   1.9.15 - ENHANCEMENT: Exit maybe_handle() if queue is invalid (clear the queue too)
	 */
	public function maybe_handle() {
		
		$utils = Utilities::get_instance();
		
		// Don't lock up other requests while processing
		session_write_close();
		
		// Background process already running.
		if ( $this->is_process_running() ) {
			$utils->log( "Terminating: Have an active queue already!" );
			wp_die();
		}
		
		if ( $this->is_queue_good() ) {
			$utils->log("Terminating: Invalid queue content");
			$this->clear_queue();
			wp_die();
		}
		
		// No data to process.
		if ( $this->is_queue_empty() ) {
			
			$utils->log( "Terminating: No data to process" );
			wp_die();
		}
		
		$utils->log( "Nonce is active for {$this->identifier}?" );
		check_ajax_referer( $this->identifier, 'nonce' );
		
		$this->handle();
		
		$utils->log( "Terminating: After the 'handle()' function" );
		wp_die();
	}
	
	/**
	 * The queue should be an array of entries/data to process
	 *
	 * @return bool
	 *
	 * @since   1.9.15 - ENHANCEMENT: Added is_queue_good() method to verify content of queue
	 */
	public function is_queue_good() {
		
		global $wpdb;
		$utils = Utilities::get_instance();
		
		$key = $wpdb->esc_like( "{$this->identifier}_batch_" ) . '%';
		
		$utils->log( "Checking for content in {$key} variable from {$wpdb->options} in option_value while looking for option_name" );
		
		$sql = $wpdb->prepare( "
					SELECT option_value
					FROM {$wpdb->options}
						WHERE option_name LIKE %s
						AND option_value != ''",
			$key
		);
		
		$result = $wpdb->get_var( $sql );
		
		return ( empty( $result ) || is_array($result ) );
	}
	
	/**
	 * Is queue empty
	 *
	 * @return bool
	 */
	protected function is_queue_empty() {
		
		global $wpdb;
		$utils = Utilities::get_instance();
		
		/*
					if ( is_multisite() ) {
						$table  = $wpdb->sitemeta;
						$column = 'meta_key';
					}
		*/
		$key = $wpdb->esc_like( "{$this->identifier}_batch_" ) . '%';
		
		$utils->log( "Checking for content in {$key} variable from {$wpdb->options} in option_value while looking for option_name" );
		
		$sql = $wpdb->prepare( "
					SELECT COUNT(*)
					FROM {$wpdb->options}
						WHERE option_name LIKE %s
						AND option_value != ''",
			$key
		);
		
		$count = $wpdb->get_var( $sql );
		
		$utils->log( "Found {$count} entries" );
		
		return ( intval( $count ) > 0 ) ? false : true;
	}
	
	/**
	 * Return the key for the currently active queue
	 *
	 * @return string
	 */
	public function get_active_queue() {
		return $this->active_queue;
	}
	
	/**
	 * Is process running
	 *
	 * Check whether the current process is already running
	 * in a background process.
	 */
	protected function is_process_running() {
		
		$utils = Utilities::get_instance();
		
		$locked  = get_transient( "{$this->identifier}_process_lock" );
		$timeout = get_option( "_transient_timeout_{$this->identifier}_process_lock" );
		
		if ( $locked && $timeout > current_time( 'timestamp' ) ) {
			$utils->log( "Queue ({$locked}) is running" );
			
			// Process already running.
			return true;
		} else {
			$utils->log( "Removing stale lock for {$this->identifier}_process_lock/{$locked}" );
			delete_transient( "{$this->identifier}_process_lock" );
		}
		
		return false;
	}
	
	/**
	 * Verify whether the specified Queue is active
	 *
	 * @param string $queue_id
	 *
	 * @return bool
	 */
	protected function is_queue_active( $queue_id ) {
		
		global $wpdb;
		$utils = Utilities::get_instance();
		
		$lock_transient = "_transient_{$this->identifier}_process_lock";
		
		if ( $queue_id == $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s", $lock_transient ) )  ) {
			
			$utils->log( "Queue ({$queue_id}) is running" );
			
			return true;
		}
		
		$utils->log( "Queue ({$queue_id}) is not running, checked with: {$this->identifier}_process_lock" );
		
		return false;
	}
	
	/**
	 * Lock process
	 *
	 * Lock the process so that multiple instances can't run simultaneously.
	 * Override if applicable, but the duration should be greater than that
	 * defined in the time_exceeded() method.
	 */
	protected function lock_process() {
		
		$utils = Utilities::get_instance();
		
		$this->start_time = current_time( 'timestamp' ); // Set start time of current process.
		
		$lock_duration = ( property_exists( $this, 'queue_lock_time' ) ) ? $this->queue_lock_time : 55; // 1 minute
		$lock_duration = apply_filters( "{$this->identifier}_queue_lock_time", $lock_duration );
		
		if ( empty( $this->active_queue ) ) {
			$this->active_queue = 'a';
		}
		
		$utils->log( "Lock lasts for {$lock_duration} seconds" );
		//update_option( "{$this->identifier}_process_lock", ( current_time('timestamp' ) + $lock_duration ) );
		set_transient( "{$this->identifier}_process_lock", $this->active_queue, $lock_duration );
	}
	
	/**
	 * Update the lock for the queue(s)
	 */
	protected function update_lock() {
		
		if ( empty( $this->active_queue ) ) {
			delete_transient( "{$this->identifier}_process_lock" );
			
			return;
		}
		
		$lock_duration = ( property_exists( $this, 'queue_lock_time' ) ) ? $this->queue_lock_time : 55; // 1 minute
		$lock_duration = apply_filters( "{$this->identifier}_queue_lock_time", $lock_duration );
		
		set_transient( "{$this->identifier}_process_lock", $this->active_queue, $lock_duration );
	}
	
	/**
	 * Unlock process
	 *
	 * Unlock the process so that other instances can spawn.
	 *
	 * @return $this
	 */
	protected function unlock_process() {
		
		$utils = Utilities::get_instance();
		
		if ( false === delete_transient( "{$this->identifier}_process_lock" ) ) {
			$utils->log( "Unable to delete {$this->identifier}_process_lock!!!" );
		}
		
		return $this;
	}
	
	/**
	 * Get batch
	 *
	 * @return \stdClass Return the first batch from the queue
	 */
	protected function get_batch() {
		
		global $wpdb;
		
		$utils = Utilities::get_instance();
		
		$key   = $wpdb->esc_like( "{$this->identifier}_batch_") . '%';
		$query = $wpdb->get_row(
			$wpdb->prepare( "
				SELECT *
					FROM {$wpdb->options}
					WHERE option_name LIKE %s AND option_value != ''
					ORDER BY option_id ASC
					LIMIT 1",
			$key )
		);
		
		$utils->log( "Will fetch batch: {$query->option_name}" );
		
		$batch       = new \stdClass();
		$batch->key  = $query->option_name;
		$batch->data = maybe_unserialize( $query->option_value );
		
		$this->active_queue = substr( $batch->key, - 1 );

		$utils->log( "Using queue name: {$this->active_queue} and processing " . count( $batch->data ) . " batch entries" );
		$this->update_lock();
		
		return $batch;
	}
	
	/**
	 * Handle
	 *
	 * Pass each queue item to the task handler, while remaining
	 * within server memory and time limit constraints.
	 */
	protected function handle() {
		
		$this->lock_process();
		$utils = Utilities::get_instance();
		
		do {
			$batch = $this->get_batch();
			
			foreach ( $batch->data as $key => $value ) {
				
				// Don't start if we're out of time or out of memory
				if ( $this->time_exceeded() || $this->memory_exceeded() ) {
					$utils->log( "We've exceeded the time or memory limits" );
					// Batch limits reached.
					break;
				} else {
					$utils->log( "Continue processing job #{$key}" );
				}
				
				$task = $this->task( $value );
				
				if ( false !== $task ) {
					$batch->data[ $key ] = $task;
				} else {
					unset( $batch->data[ $key ] );
					$utils->log( "Removed task with Key {$key} - We have " . count( $batch->data ) . " tasks left..." );
				}
			}
			
			// Update or delete current batch.
			if ( ! empty( $batch->data ) ) {
				$utils->log( "Update batch queue" );
				$this->update( $batch->key, $batch->data );
			} else {
				$utils->log( "Clear batch queue" );
				$this->delete( $batch->key );
			}
			
		} while ( ! $this->time_exceeded() && ! $this->memory_exceeded() && ! $this->is_queue_empty() );
		
		$utils->log( "Batch operation done. Time: " . ( $this->time_exceeded() ? 'Yes' : 'No' ) . ' Memory: ' . ( $this->memory_exceeded() ? 'Yes' : 'No' ) . " Queue complete: " . ( $this->is_queue_empty() && empty( $this->batch ) ? 'Yes' : 'No' ) );
		
		$this->unlock_process();
		
		// Start next batch or complete process.
		if ( ! $this->is_queue_empty() ) {
			$utils->log( "Prepare another execution for the queue" );
			$this->dispatch();
		} else {
			$utils->log( "Queue is empty. Nothing more to do!" );
			$this->complete();
		}
		
		$utils->log( "Terminating execution of the handler function" );
		wp_die();
	}
	
	/**
	 * Memory exceeded
	 *
	 * Ensures the batch process never exceeds 90%
	 * of the maximum WordPress memory.
	 *
	 * @return bool
	 */
	protected function memory_exceeded() {
		
		$memory_limit   = $this->get_memory_limit() * 0.9; // 90% of max memory
		$current_memory = memory_get_usage( true );
		$return         = false;
		
		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}
		
		return apply_filters( "{$this->identifier}_memory_exceeded", $return );
	}
	
	/**
	 * Get memory limit
	 *
	 * @return int
	 */
	protected function get_memory_limit() {
		
		if ( function_exists( 'ini_get' ) ) {
			
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			
			// Sensible default.
			$memory_limit = '128M';
		}
		
		if ( ! $memory_limit || - 1 === intval( $memory_limit ) ) {
			
			// Unlimited, set to 32GB.
			$memory_limit = '32000M';
		}
		
		$limit = preg_replace( '/M$/i', '', $memory_limit );
		
		return intval( $limit ) * 1024 * 1024;
	}
	
	/**
	 * Time exceeded.
	 *
	 * Ensures the batch never exceeds a sensible time limit.
	 * A timeout limit of 30s is common on shared hosting.
	 *
	 * @return bool
	 */
	protected function time_exceeded() {
		
		$utils = Utilities::get_instance();
		
		$current_timeout    = intval( ini_get( 'max_execution_time' ) );
		$default_time_limit = apply_filters( 'e20r-background-processing-time-limit', 20 );
		$return             = false;
		
		if ( ! empty( $current_timeout ) ) {
			
			$time_limit = intval( floor( $current_timeout * 0.80 ) );
			
			// Shouldn't be less than 20 seconds (change web host provider if this is necessary!)
		} else {
			$time_limit = $default_time_limit;
		}
		
		if ( $time_limit <= $default_time_limit ) {
			
			$utils->add_message( __( "PHP setting 'max_execution_time' is too low!", "e20r-utilities" ), 'warning', 'backend' );
			$time_limit = $default_time_limit;
		}
		
		$finish = $this->start_time + apply_filters( "{$this->identifier}_time_limit", $time_limit ); // 20 seconds
		$now    = current_time( 'timestamp' );
		
		if ( $time_limit >= 60 ) {
			$utils->log( "Using lock time of {$time_limit}, supposed to finish at {$finish} (finish) vs {$now} (now)" );
			add_filter( "{$this->identifier}_queue_lock_time", array( $this, 'increase_lock_timeout' ) );
		}
		
		if ( $now >= $finish ) {
			
			$utils->log( "Max execution time ({$time_limit} vs {$finish} vs {$now}) exceeded!" );
			$return = true;
		}
		
		return $return;
	}
	
	/**
	 * @param int $lock_duration
	 *
	 * @return int
	 */
	public function increase_lock_timeout( $lock_duration ) {
		
		$utils = Utilities::get_instance();
		
		$current_timeout    = intval( ini_get( 'max_execution_time' ) );
		$default_time_limit = apply_filters( 'e20r-background-processing-time-limit', 20 );
		
		if ( ! empty( $current_timeout ) ) {
			
			$time_limit = intval( floor( $current_timeout * 0.80 ) );
			
			// Shouldn't be less than 20 seconds (change web host provider if this is necessary!)
			if ( $time_limit < $default_time_limit ) {
				$time_limit = 18;
			}
		}
		
		if ( $time_limit >= 60 ) {
			
			$lock_duration = $time_limit;
		}
		
		$utils->log( "Setting lock duration to: {$lock_duration}" );
		
		return $lock_duration;
	}
	
	/**
	 * Complete.
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		// Unschedule the cron healthcheck.
		$this->clear_scheduled_event();
	}
	
	/**
	 * Clear queue of entries for the handler
	 */
	public function clear_queue() {
		
		$utils = Utilities::get_instance();
		
		global $wpdb;
		
		$table  = $wpdb->options;
		$column = 'option_name';
		
		if ( is_multisite() ) {
			$table  = $wpdb->sitemeta;
			$column = 'meta_key';
		}
		
		$key = $this->identifier . "_batch_%";
		$utils->log( "Attempting to manually clear the job queue for {$key}. Has " . count( $this->data ) . " data/job entries left" );
		
		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE {$column} LIKE %s", $key ) ) ) {
			$utils->log( "ERROR: Unable to clear the job queue for {$key}!" );
		}
	}
	
	/**
	 * Schedule cron healthcheck
	 *
	 * @access public
	 *
	 * @param mixed $schedules Schedules.
	 *
	 * @return mixed
	 */
	public function schedule_cron_healthcheck( $schedules ) {
		
		$current_timeout = ini_get( 'max_execution_time' );
		$min_interval    = 2;
		
		if ( ! empty( $current_timeout ) ) {
			$max_in_mins  = ceil( $current_timeout / 60 );
			$min_interval = $max_in_mins + 1;
		}
		
		$interval = apply_filters( "{$this->identifier}_cron_interval", $min_interval );
		
		if ( property_exists( $this, 'cron_interval' ) ) {
			$interval = apply_filters( "{$this->identifier}_cron_interval", $this->cron_interval_identifier );
		}
		
		// Adds every the calculated minutes (+1) to the existing schedules.
		$schedules["{$this->identifier}_cron_interval"] = array(
			'interval' => MINUTE_IN_SECONDS * $interval,
			'display'  => sprintf( __( 'Every %d Minutes' ), $interval ),
		);
		
		return $schedules;
	}
	
	/**
	 * Handle cron healthcheck
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 */
	public function handle_cron_healthcheck() {
		
		$utils = Utilities::get_instance();
		
		if ( $this->is_process_running() ) {
			$utils->log( "Exiting since we're probably processing the queue already" );
			
			// Background process already running.
			wp_die();
		}
		
		if ( $this->is_queue_empty() ) {
			
			$utils->log( "Exiting since queue is empty" );
			// No data to process.
			$this->clear_scheduled_event();
			wp_die();
		}
		
		$this->handle();
		
		$utils->log( "Exiting after completing the handler() method" );
		wp_die();
	}
	
	/**
	 * Schedule event
	 */
	protected function schedule_event() {
		
		$util = Utilities::get_instance();
		
		if ( false === wp_next_scheduled( $this->cron_hook_identifier ) ) {
			
			$util->log( "Scheduling {$this->cron_hook_identifier} to run:  {$this->cron_interval_identifier}" );
			wp_schedule_event( current_time( 'timestamp' ), $this->cron_interval_identifier, $this->cron_hook_identifier );
		}
	}
	
	/**
	 * Clear scheduled event
	 */
	protected function clear_scheduled_event() {
		$utils = Utilities::get_instance();
		
		$timestamp = wp_next_scheduled( $this->cron_hook_identifier );
		$utils->log( "Found scheduled event for {$this->cron_hook_identifier}? {$timestamp}" );
		
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $this->cron_hook_identifier );
		}
	}
	
	/**
	 * Cancel Process
	 *
	 * Stop processing queue items, clear cronjob and delete batch.
	 *
	 */
	public function cancel_process() {
		
		if ( ! $this->is_queue_empty() ) {
			
			$batch = $this->get_batch();
			
			$this->delete( $batch->key );
			
			wp_clear_scheduled_hook( $this->cron_hook_identifier );
		}
	}
	
	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over.
	 *
	 * @return mixed
	 */
	abstract protected function task( $item );
}
