<?php
/**
 * Copyright (c) 2016-2018 - Eighty / 20 Results by Wicked Strong Chicks.
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
 * @version 3.0 - GDPR opt-in, erasure and data access framework
 */

namespace E20R\Utilities;

// Disallow direct access to the class definition

if ( ! defined( 'ABSPATH' ) && function_exists( 'wp_die' ) ) {
	wp_die( "Cannot access file directly" );
}

if ( ! class_exists( '\E20R\Utilities\Utilities' ) ) {
	
	/**
	 * Class Utilities
	 * @package E20R\Utilities
	 */
	class Utilities {
		
		/**
		 * Version number for the Utilities class
		 */
		const Version = '3.0';
		
		/**
		 * URI to the library path (Utilities)
		 *
		 * @var string
		 */
		public static $LIBRARY_URL = '';
		
		/**
		 * Path to the Utilities library
		 *
		 * @var string
		 */
		public static $LIBRARY_PATH = '';
		
		/**
		 * @var string Cache key
		 */
		private static $cache_key;
		
		/**
		 * @var null|string
		 */
		public static $plugin_slug = null;
		
		/**
		 * @var null|Utilities
		 */
		private static $instance = null;
		
		private $blog_id = null;
		
		/**
		 * Utilities constructor.
		 */
		private function __construct() {
			
			self::$LIBRARY_URL  = plugins_url( null, __FILE__ );
			self::$LIBRARY_PATH = plugin_dir_path( __FILE__ );
			
			if ( empty( self::$plugin_slug ) ) {
				self::$plugin_slug = apply_filters( 'e20r-licensing-text-domain', null );
			}
			
			$this->log( 'Plugin Slug: ' . self::$plugin_slug );
			
			$this->blog_id = get_current_blog_id();
			
			self::$cache_key = "e20r_pw_utils_{$this->blog_id}";
			$messages        = new Message();
			
			$this->log( "Front or backend???" );
			
			if ( self::is_admin() ) {
				
				// Clear cache when updating discount codes or membership level definitions
				add_action( 'pmpro_save_discount_code', array( $this, 'clear_delay_cache' ), 9999, 1 );
				add_action( 'pmpro_save_membership_level', array( $this, 'clear_delay_cache' ), 9999, 1 );
				
				if ( ! has_action( 'admin_notices', array( $messages, 'display' ) ) ) {
					
					$this->log( "Loading message(s) for backend" );
					add_action( 'admin_notices', array( $messages, 'display' ), 10 );
					
				}
			} else {
				
				$this->log( "Loading message(s) for frontend" );
				add_filter( 'woocommerce_update_cart_action_cart_updated', array( $messages, 'clearNotices' ), 10, 1 );
				add_action( 'woocommerce_init', array( $messages, 'display' ), 1 );
				
				add_filter( 'pmpro_email_field_type', array( $messages, 'filter_passthrough' ), 1, 1 );
				add_filter( 'pmpro_get_membership_levels_for_user', array( $messages, 'filter_passthrough' ), 10, 2 );
			}
		}
		
		
		/**
		 * Pattern recognize whether the data is a valid date format for this plugin
		 * Expected format: YYYY-MM-DD
		 *
		 * @param $data -- Data to test
		 *
		 * @return bool -- true | false
		 *
		 * @access private
		 */
		public function is_valid_date( $data ) {
			// Fixed: is_valid_date() needs to support all expected date formats...
			if ( false === strtotime( $data ) ) {
				
				return false;
			}
			
			return true;
		}
		
		/**
		 * (Attempt to) Fetch and sanitize the IP address of the connecting client
		 *
		 * @return string|null
		 */
		public function get_client_ip() {
			
			$ip = null;
			
			if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
				return $_SERVER['REMOTE_ADDR'];
			}
			
			$ip_keys = array(
				'HTTP_CLIENT_IP',
				'HTTP_X_FORWARDED_FOR',
				'HTTP_X_FORWARDED',
				'HTTP_X_CLUSTER_CLIENT_IP',
				'HTTP_FORWARDED_FOR',
				'HTTP_FORWARDED',
				'REMOTE_ADDR',
			);
			
			foreach ( $ip_keys as $key ) {
				
				if ( array_key_exists( $key, $_SERVER ) === true ) {
					
					foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
						
						// trim for safety measures
						$ip = trim( $ip );
						
						// attempt to validate IP
						if ( filter_var( $ip,
								FILTER_VALIDATE_IP,
								FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
						     ) !== false ) {
							
							return $ip;
						}
					}
				}
			}
			
			
			return $ip;
		}
		
		/**
		 * Are we viewing the admin screen (WP Backend)
		 *
		 * @return bool
		 */
		public static function is_admin() {
			
			$is_admin = false;
			
			if ( isset( $GLOBALS['current_screen'] ) ) {
				$is_admin = $GLOBALS['current_screen']->in_admin();
			} else if ( defined( 'WP_ADMIN' ) ) {
				$is_admin = WP_ADMIN;
			}
			
			return $is_admin;
		}
		
		/**
		 * Load and use L10N based text (if available)
		 */
		public function load_textdomain() {
			
			$this->log( "Processing load_textdomain" );
			
			if ( empty( self::$plugin_slug ) ) {
				$this->log( "Error attempting to load translation files!" );
				
				return;
			}
			
			$domain_name = self::$plugin_slug;
			
			$locale = apply_filters( "plugin_locale", get_locale(), $domain_name );
			
			$mofile        = "{$domain_name}-{$locale}.mo";
			$mofile_local  = plugin_dir_path( __FILE__ ) . "languages/" . $mofile;
			$mofile_global = WP_LANG_DIR . "/{$domain_name}/" . $mofile;
			
			load_textdomain( "{$domain_name}", $mofile_local );
			
			//Attempt to load the global translation first (if it exists)
			if ( file_exists( $mofile_global ) ) {
				load_textdomain( "{$domain_name}", $mofile_global );
			}
			
			//load local second
			load_textdomain( "{$domain_name}", $mofile_local );
			
			//load via plugin_textdomain/glotpress
			load_plugin_textdomain( "{$domain_name}", false, dirname( __FILE__ ) . "/../../languages/" );
		}
		
		/**
		 * Test whether the plugin is active on the system or in the network
		 *
		 * @param null|string $plugin_file
		 * @param null|string $function_name
		 *
		 * @return bool
		 */
		public function plugin_is_active( $plugin_file = null, $function_name = null ) {
			
			// $this->log( "Testing whether plugin file ({$plugin_file}) or function ({$function_name}) exists/indicates an active plugin" );
			if ( ! is_admin() ) {
				
				if ( ! empty( $function_name ) ) {
					$this->log( "{$function_name} is present and loaded?" );
					
					return function_exists( $function_name );
				}
			} else {
				
				$this->log( "In WordPress backend..." );
				if ( ! empty( $plugin_file ) ) {
					$this->log( "{$plugin_file} is loaded and activated?" );
					
					return ( is_plugin_active( $plugin_file ) || is_plugin_active_for_network( $plugin_file ) );
				}
				
				if ( ! empty( $function_name ) ) {
					$this->log( "{$function_name} function is present, implying the plugin is loaded and activated" );
					
					return function_exists( $function_name );
				}
			}
			
			return false;
		}
		
		/**
		 * Return last message of a specific type
		 *
		 * @param string $type
		 *
		 * @return string[]
		 */
		public function get_message( $type = 'notice' ) {
			
			$messages = new Message();
			
			return $messages->get( $type );
		}
		
		/**
		 * Add error message to the list of messages to display on the back-end
		 *
		 * @param string $message    The message to save/add
		 * @param string $type       The type of message (notice, warning, error)
		 * @param string $msg_source The source of the error message
		 *
		 * @return bool
		 */
		public function add_message( $message, $type = 'notice', $msg_source = 'default' ) {
			
			$this->msg[] = new Message( $message, $type, $msg_source );
			
			return true;
		}
		
		/**
		 * Display the error message as HTML when called
		 *
		 * @param string $source - The error source to show.
		 */
		public function display_messages( $source = 'default' ) {
			
			$message = new Message();
			$message->display( $source );
		}
		
		/**
		 * Identify the calling function (used in debug logger
		 *
		 * @return array|string
		 *
		 * @access public
		 */
		public function _who_called_me() {
			
			$trace  = debug_backtrace();
			$caller = $trace[2];
			
			if ( isset( $caller['class'] ) ) {
				$trace = "{$caller['class']}::{$caller['function']}()";
			} else {
				$trace = "Called by {$caller['function']}()";
			}
			
			return $trace;
		}
		
		/**
		 * Return the cache key for the Utilities class
		 * @return string
		 */
		public static function get_util_cache_key() {
			
			return self::$cache_key;
		}
		
		/**
		 * Return all delay values for a membership payment start
		 *
		 * @param $level
		 *
		 * @return array|bool|mixed|null
		 */
		public static function get_membership_start_delays( $level ) {
			
			$delays = array();
			self::$instance->log( "Processing start delays for {$level->id}" );
			
			if ( true === pmpro_isLevelRecurring( $level ) ) {
				
				self::$instance->log( "Level {$level->id} is a recurring payments level" );
				
				if ( null === ( $delays = Cache::get( "start_delay_{$level->id}", self::$cache_key ) ) ) {
					
					self::$instance->log( "Invalid cache... Loading from scratch" );
					
					// Calculate the trial period (may be smaller than a normal billing period
					if ( $level->cycle_number > 0 ) {
						
						self::$instance->log( "Is a recurring billing level" );
						
						$trial_cycles       = $level->trial_limit;
						$period_days        = self::convert_period( $level->cycle_period );
						$billing_cycle_days = 0;
						
						if ( null !== $period_days ) {
							$billing_cycle_days = $level->cycle_number * $period_days;
						}
						
						if ( ! empty( $trial_cycles ) ) {
							$delays['trial'] = $trial_cycles * $billing_cycle_days;
						}
						
						$delays[ $level->id ] = ( $billing_cycle_days * $level->cycle_number );
						self::$instance->log( "Days used for delay value: {$delays[$level->id]} " );
					}
					
					// We have Subscription Delays add-on for PMPro installed and active
					if ( function_exists( 'pmprosd_getDelay' ) ) {
						
						self::$instance->log( "Processing Subscription Delay values for {$level->id}" );
						
						//Get the default delay value (days)
						$date_or_num = pmprosd_getDelay( $level->id, null );
						self::$instance->log( "Received default delay value: {$date_or_num}" );
						
						if ( ! empty( $date_or_num ) ) {
							$val = ( is_numeric( $date_or_num ) ? $date_or_num : pmprosd_daysUntilDate( $date_or_num ) );
							
							if ( ! empty( $val ) ) {
								$delays['default'] = $val;
								self::$instance->log( "Configured default value {$delays[ 'default' ]}" );
							}
						} else {
							self::$instance->log( "No default value for level {$level->id} specified" );
						}
						
						// Fetch discount codes to locate delays for
						$active_codes = self::get_all_discount_codes();
						
						// Process active discount code delays
						if ( ! empty( $active_codes ) ) {
							
							foreach ( $active_codes as $code ) {
								
								// Get the delay value from the Subscription Delays plugin
								$d = pmprosd_getDelay( $level->id, $code->id );
								
								if ( ! empty( $d ) ) {
									
									self::$instance->log( "Processing {$d}" );
									$val = ( is_numeric( $d ) ? $d : pmprosd_daysUntilDate( $d ) );
									
									if ( ! empty( $val ) ) {
										$delays[ $code->code ] = $val;
										self::$instance->log( "Configured {$code->code} value {$delays[ $code->code ]}" );
									}
								}
							}
						}
					}
					
					// Update the cache.
					if ( ! empty( $delays ) ) {
						
						// Save to cache and have cached for up to 7 days
						Cache::set( "start_delay_{$level->id}", $delays, WEEK_IN_SECONDS, self::$cache_key );
					}
				}
			}
			
			return $delays;
		}
		
		/**
		 * Convert a Cycle Period (from PMPro) string to an approximate day count
		 *
		 * @param string $period
		 *
		 * @return int|null
		 */
		public static function convert_period( $period ) {
			
			$days = null;
			
			switch ( strtolower( $period ) ) {
				
				case 'day':
					$days = 1;
					break;
				
				case 'week':
					$days = 7;
					break;
				
				case 'month':
					$days = 30;
					break;
				
				case 'year':
					$days = 365;
					break;
			}
			
			return $days;
		}
		
		/**
		 * Return all PMPro discount codes from the system
		 *
		 * @return array|null|object
		 */
		public static function get_all_discount_codes() {
			
			global $wpdb;
			
			return $wpdb->get_results( "SELECT id, code FROM {$wpdb->pmpro_discount_codes}" );
		}
		
		/**
		 * Remove the start delay cache for the level
		 */
		public function clear_delay_cache( $level_id ) {
			
			$this->log( "Clearing delay cache for {$level_id}" );
			Cache::delete( "start_delay_{$level_id}", self::$cache_key );
		}
		
		/**
		 * Test whether the user is in Trial mode (i.e. the user's startdate is configured as 'after' the current date/time
		 *
		 * @param int $user_id
		 * @param int $level_id
		 *
		 * @return int|bool - Returns the Timestamp (seconds) of when the trial ends, or false if no trial was found
		 */
		public function is_in_trial( $user_id, $level_id ) {
			
			global $wpdb;
			$this->log( "Processing trial test for {$user_id} and {$level_id}" );
			
			// Get the most recent (active) membership level record for the specified user/membership level ID
			$sql = $wpdb->prepare(
				"SELECT UNIX_TIMESTAMP( mu.startdate ) AS start_date
                     FROM {$wpdb->pmpro_memberships_users} AS mu
                     WHERE mu.user_id = %d
                          AND mu.membership_id = %d
                     ORDER BY mu.id DESC
                     LIMIT 1",
				$user_id,
				$level_id
			);
			
			$start_ts = intval( $wpdb->get_var( $sql ) );
			
			$this->log( "Found start Timestamp: {$start_ts}" );
			
			// No record found for specified user, so can't be in a trial...
			if ( empty( $start_ts ) ) {
				$this->log( "No start time found for {$user_id}, {$level_id}: {$wpdb->last_error}" );
				
				return false;
			}
			
			$now = current_time( 'timestamp' );
			
			if ( true === $this->plugin_is_active( 'pmprosd_daysUntilDate' ) ) {
				
				$this->log( "The PMPro Subscription Delays add-on is active on this system" );
				
				// Is the user record in 'pre-start' mode (i.e. using Subscription Delay add-on)
				if ( ! empty( $start_ts ) && $start_ts <= $now ) {
					
					$this->log( "User ({$user_id}) at membership level ({$level_id}) is currently in 'trial' mode: {$start_ts} <= {$now}" );
					
					return $start_ts;
				}
			} else if ( true === $this->plugin_is_active( 'paid-memberships-pro/paid-memberships-pro.php', 'pmpro_getMembershipLevelForUser' ) ) {
				
				$this->log( "No trace of the 'Subscription Delays' add-on..." );
				
				$user_level = pmpro_getMembershipLevelForUser( $user_id );
				
				// Is there a trial period defined for this user?
				if ( ! empty( $user_level->cycle_number ) && ! empty( $user_level->trial_limit ) ) {
					
					$trial_duration = $user_level->cycle_number * $user_level->trial_limit;
					$start_date     = date( 'Y-m-d H:i:s', $start_ts );
					$trial_ends_ts  = strtotime( "{$start_date} + {$trial_duration} {$user_level->cycle_period}" );
					
					if ( false !== $trial_ends_ts && $trial_ends_ts >= $now ) {
						$this->log( "User {$user_id} is in their current trial period for level {$level_id}: It ends at {$trial_ends_ts} which is >= {$now} " );
						
						return $trial_ends_ts;
					} else {
						$this->log( "There was a problem converting the trial period info into a timestamp!" );
					}
				} else {
					$this->log( "No Trial period defined for user..." );
				}
				
			} else {
				$this->log( "Neither PMPro nor Subscription Delays add-on is installed and active!!" );
			}
			
			return false;
		}
		
		/**
		 * Return the correct Stripe amount formatting (based on currency setting)
		 *
		 * @param float|int $amount
		 * @param string    $currency
		 *
		 * @return float|string
		 */
		public function amount_by_currency( $amount, $currency ) {
			
			$def_currency = apply_filters( 'e20r_utilities_default_currency', 'USD' );
			
			if ( $def_currency !== $currency ) {
				$def_currency = strtoupper( $currency );
			}
			
			$decimals = 2;
			global $pmpro_currencies;
			
			if ( isset( $pmpro_currencies[ $def_currency ]['decimals'] ) ) {
				$decimals = intval( $pmpro_currencies[ $def_currency ]['decimals'] );
			}
			
			
			$divisor = intval( str_pad( '1', ( 1 + $decimals ), '0', STR_PAD_RIGHT ) );
			$this->log( "Divisor for calculation: {$divisor}" );
			
			$amount = number_format_i18n( ( $amount / $divisor ), $decimals );
			$this->log( "Using amount: {$amount} for {$currency} vs {$amount}" );
			
			return $amount;
		}
		
		/**
		 * Print a message to the WP_DEBUG logger if configured
		 *
		 * @param $msg
		 */
		public function log( $msg ) {
			
			$tid  = sprintf( "%08x", abs( crc32( $_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME'] ) ) );
			$time = date_i18n( 'H:m:s', strtotime( get_option( 'timezone_string' ) ) );
			$from = $this->_who_called_me();
			
			if ( defined( "WP_DEBUG" ) && true === WP_DEBUG ) {
				error_log( "[{$tid}]({$time}) {$from} - {$msg}" );
			}
		}
		
		/**
		 * Case insensitive search/replace function (recursive)
		 *
		 * @param string $search
		 * @param string $replacer
		 * @param string $input
		 *
		 * @return mixed
		 */
		public function nc_replace( $search, $replacer, $input ) {
			
			return preg_replace_callback( "/\b{$search}\b/i", function ( $matches ) use ( $replacer ) {
				return ctype_lower( $matches[0][0] ) ? strtolower( $replacer ) : $replacer;
			}, $input );
		}
		
		/**
		 * Process REQUEST variable: Check for presence and sanitize it before returning value or default
		 *
		 * @param string     $name    Name of the variable to return
		 * @param null|mixed $default The default value to return if the REQUEST variable doesn't exist or is empty.
		 *
		 * @return bool|float|int|null|string  Sanitized value from the front-end.
		 */
		public function get_variable( $name, $default = null ) {
			
			return isset( $_REQUEST[ $name ] ) && ! empty( $_REQUEST[ $name ] ) ? $this->_sanitize( $_REQUEST[ $name ] ) : $default;
		}
		
		/**
		 * Sanitizes the passed field/value.
		 *
		 * @param array|int|null|string|\stdClass $field The value to sanitize
		 *
		 * @return mixed     Sanitized value
		 */
		public function _sanitize( $field ) {
			
			if ( ! is_numeric( $field ) ) {
				
				if ( is_array( $field ) ) {
					
					foreach ( $field as $key => $val ) {
						$field[ $key ] = $this->_sanitize( $val );
					}
				}
				
				if ( is_object( $field ) ) {
					
					foreach ( $field as $key => $val ) {
						$field->{$key} = $this->_sanitize( $val );
					}
				}
				
				if ( ( ! is_array( $field ) ) && ctype_alpha( $field ) ||
				     ( ( ! is_array( $field ) ) && strtotime( $field ) ) ||
				     ( ( ! is_array( $field ) ) && is_string( $field ) )
				) {
					
					if ( strtolower( $field ) == 'yes' ) {
						$field = true;
					} else if ( strtolower( $field ) == 'no' ) {
						$field = false;
					} else if ( ! $this->is_html( $field ) ) {
						$field = sanitize_text_field( $field );
					} else {
						$field = wp_kses_post( $field );
					}
				}
				
			} else {
				
				if ( is_float( $field + 1 ) ) {
					
					$field = sanitize_text_field( $field );
				}
				
				if ( is_int( $field + 1 ) ) {
					
					$field = intval( $field );
				}
			}
			
			return $field;
		}
		
		/**
		 * Test whether string contains HTML
		 *
		 * @param $string
		 *
		 * @return bool
		 */
		final static function is_html( $string ) {
			return preg_match( "/<[^<]+>/", $string, $m ) != 0;
		}
		
		/**
		 * Test whether the value is an integer
		 *
		 * @param string $val
		 *
		 * @return bool|int
		 */
		final static function is_integer( $val ) {
			if ( ! is_scalar( $val ) || is_bool( $val ) ) {
				return false;
			}
			
			if ( is_float( $val + 0 ) && ( $val + 0 ) > PHP_INT_MAX ) {
				return false;
			}
			
			return is_float( $val ) ? false : preg_match( '~^((?:\+|-)?[0-9]+)$~', $val );
		}
		
		/**
		 * Test if the value is a floating point number
		 *
		 * @param string $val
		 *
		 * @return bool
		 */
		final static function is_float( $val ) {
			if ( ! is_scalar( $val ) ) {
				return false;
			}
			
			return is_float( $val + 0 );
		}
		
		/**
		 * Decode the JSON object we received
		 *
		 * @param $response
		 *
		 * @return array|mixed|object
		 *
		 * @since 2.0.0
		 * @since 2.1 - Updated to handle UTF-8 BOM character
		 */
		public function decode_response( $response ) {
			
			// UTF-8 BOM handling
			$bom  = pack( 'H*', 'EFBBBF' );
			$json = preg_replace( "/^$bom/", '', $response );
			
			if ( null !== ( $obj = json_decode( $json ) ) ) {
				return $obj;
			}
			
			return false;
		}
		
		/**
		 * Encode data to JSON
		 *
		 * @param mixed $data
		 *
		 * @return bool|string
		 *
		 * @since 2.0.0
		 */
		public function encode( $data ) {
			if ( false !== ( $json = json_encode( $data ) ) ) {
				return $json;
			}
			
			return false;
		}
		
		/**
		 * Clear the Output (browser) buffers (for erroneous error messages, etc)
		 *
		 * @return string
		 */
		public function clear_buffers() {
			
			ob_start();
			
			$buffers = ob_get_clean();
			
			return $buffers;
			
		}
		
		/**
		 * Return or print checked field for HTML Checkbox INPUT
		 *
		 * @param mixed $needle
		 * @param array $haystack
		 * @param bool  $echo
		 *
		 * @return null|string
		 */
		public function checked( $needle, $haystack, $echo = false ) {
			
			$text = null;
			
			if ( is_array( $haystack ) ) {
				if ( in_array( $needle, $haystack ) ) {
					$text = ' checked="checked" ';
				}
			}
			
			if ( is_object( $haystack ) && in_array( $needle, (array) $haystack ) ) {
				$text = ' checked="checked" ';
			}
			
			if ( ! is_array( $haystack ) && ! is_object( $haystack ) ) {
				if ( $needle === $haystack ) {
					$text = ' checked="checked" ';
				}
			}
			
			if ( true === $echo ) {
				esc_attr_e( $text );
				
				return null;
			}
			
			return $text;
		}
		
		/**
		 * Return or print selected field for HTML Select input
		 *
		 * @param mixed $needle
		 * @param mixed $haystack
		 * @param bool  $echo
		 *
		 * @return null|string
		 */
		public function selected( $needle, $haystack, $echo = false ) {
			
			$text = null;
			
			if ( is_array( $haystack ) ) {
				if ( in_array( $needle, $haystack ) ) {
					$text = ' selected="selected" ';
				}
			}
			
			if ( is_object( $haystack ) && in_array( $needle, (array) $haystack ) ) {
				$text = ' selected="selected" ';
			}
			
			if ( ! is_array( $haystack ) && ! is_object( $haystack ) ) {
				if ( $needle === $haystack ) {
					$text = ' selected="selected" ';
				}
			}
			
			if ( true === $echo ) {
				esc_attr_e( $text );
				
				return null;
			}
			
			return $text;
		}
		
		/**
		 * Generates a true random alphanumeric string of $length characters
		 *
		 * @param int    $length   Size of the string to generate
		 * @param string $keyspace The characters to use to generate the string.
		 *
		 * @return string   True random string of $keyspace characters
		 *
		 * Credit:
		 * @url http://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425
		 */
		public function random_string( $length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ) {
			
			$string  = '';
			$max_len = mb_strlen( $keyspace, '8bit' ) - 1;
			for ( $i = 0; $i < $length; ++ $i ) {
				$string .= $keyspace[ random_int( 0, $max_len ) ];
			}
			
			return $string;
		}
		
		/**
		 * Search through array values to check whether there's anything there
		 *
		 * @param array $array
		 *
		 * @return bool
		 */
		public function array_isnt_empty( $array ) {
			
			$values = array_values( $array );
			
			return ( empty( $values ) ? false : true );
		}
		
		/**
		 * Substitute [IN] for proper SQL 'IN' statement containing array of like values
		 *
		 * @param  string $sql
		 * @param  array  $values
		 * @param string  $type
		 *
		 * @return string
		 */
		public function prepare_in( $sql, $values, $type = '%d' ) {
			
			global $wpdb;
			
			$not_in_count = substr_count( $sql, '[IN]' );
			
			if ( $not_in_count > 0 ) {
				
				$args = array(
					str_replace( '[IN]',
						implode( ', ', array_fill( 0, count( $values ), ( $type == '%d' ? '%d' : '%s' ) ) ),
						str_replace( '%', '%%', $sql ) ),
				);
				
				for ( $i = 0; $i < substr_count( $sql, '[IN]' ); $i ++ ) {
					$args = array_merge( $args, $values );
				}
				
				// Sanitize the SQL variables
				$sql = call_user_func_array(
					array( $wpdb, 'prepare' ),
					array_merge( $args ) );
				
			}
			
			return $sql;
		}
		
		/**
		 * Get rid of PHP notice/warning messages from buffer
		 */
		public function safeAjax() {
			
			ini_set( 'display_errors', 0 );
			ob_start();
			$messages = ob_get_clean();
			$this->log( $messages );
		}
		
		/**
		 * Connect to the license server using TLS 1.2
		 *
		 * @param $handle - File handle for the pipe to the CURL process
		 */
		public function force_tls_12( $handle ) {
			
			// set the CURL option to use.
			curl_setopt( $handle, CURLOPT_SSLVERSION, 6 );
		}
		
		/**
		 * The current instance of the Utilities class
		 *
		 * @return Utilities|null
		 */
		public static function get_instance() {
			
			if ( is_null( self::$instance ) ) {
				self::$instance = new self;
			}
			
			return self::$instance;
		}
	}
}
