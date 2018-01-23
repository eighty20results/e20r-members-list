<?php
/**
 * Copyright (c) 2017 - Eighty / 20 Results by Wicked Strong Chicks.
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
 * @version 2.0
 *
 */

namespace E20R\Utilities\Licensing;

use E20R\Utilities\Utilities;
use E20R\Utilities\Cache;

if ( !defined('E20R_LICENSING_DEBUG' ) ) {
	define( 'E20R_LICENSING_DEBUG', false );
}
if ( ! class_exists( 'E20R\Utilities\Licensing\Licensing' ) ) {
	
	class Licensing {
		
		const CACHE_KEY = 'active_licenses';
		const CACHE_GROUP = 'e20r_licensing';
		const E20R_LICENSE_SECRET_KEY = '5687dc27b50520.33717427';
		const E20R_LICENSE_SERVER_URL = 'https://eighty20results.com';
		
		const E20R_LICENSE_MAX_DOMAINS = 2048;
		const E20R_LICENSE_REGISTERED = 1024;
		const E20R_LICENSE_DOMAIN_ACTIVE = 512;
		const E20R_LICENSE_ERROR = 256;
		
		private static $instance = null;
		
		private static $text_domain;
		
		/**
		 * Set the text domain to use, dynamically
		 * Licensing constructor.
		 */
		private function __construct() {
			
			self::$text_domain = apply_filters( 'e20r-licensing-text-domain', self::$text_domain );
		}
		
		/**
		 * Is the specified product licensed for use/updates (check against cached value, if possible)
		 * The Ccache is configured to time out every 24 hours (or so)
		 *
		 * @param string $product_stub Name of the product/component to test the license for
		 * @param bool   $force        Whether to force the plugin to connect with the license server, regardless of cache value(s)
		 *
		 * @return bool
		 */
		public static function is_licensed( $product_stub = null, $force = false ) {
			
			$utils       = Utilities::get_instance();
			$is_licensed = false;
			$is_active   = false;
			
			if ( empty( $product_stub ) ) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "No Product Stub supplied!" );
				}
				
				return false;
			}
			
			if ( E20R_LICENSING_DEBUG ) {
				
				$utils->log( "Checking license for {$product_stub}" );
			}
			
			if ( true === $force ) {
				
				$utils->log( "Forcing remote lookup of license for {$product_stub}" );
				Cache::delete( self::CACHE_KEY, self::CACHE_GROUP );
			}
			
			$excluded = apply_filters( 'e20r_licensing_excluded', array(
				'e20r_default_license',
				'example_gateway_addon',
				'new_licenses',
			) );
			
			$is_licensed = self::get_license_status_from_server( $product_stub );
			
			if ( ! in_array( $product_stub, $excluded ) && ( null === ( $license_settings = Cache::get( self::CACHE_KEY, self::CACHE_GROUP ) ) || true === $force ) ) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "License status IS NOT cached for {$product_stub}" );
				}
				
				// Get new/existing settings
				$license_settings = self::get_settings();
				
				if ( ! isset( $license_settings[ $product_stub ] ) ) {
					$license_settings[ $product_stub ] = array();
				}
				
				$license_settings[ $product_stub ] = isset( $license_settings[ $product_stub ] ) ? $license_settings[ $product_stub ] : self::default_settings( $product_stub );
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Using license settings for {$product_stub}: " . print_r( $license_settings[ $product_stub ], true ) );
				}
				// Update the local cache for the license
				Cache::set( self::CACHE_KEY, $license_settings, DAY_IN_SECONDS, self::CACHE_GROUP );
			}
			
			$is_active = ( ! empty( $license_settings[ $product_stub ]['key'] ) && ! empty( $license_settings[ $product_stub ]['status'] ) && 'active' == $license_settings[ $product_stub ]['status'] && $license_settings[ $product_stub ]['domain'] == $_SERVER['SERVER_NAME'] && true === $is_licensed );
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "License status for {$product_stub}: " . ( $is_active ? 'Active' : 'Inactive' ) );
			}
			
			return $is_active;
		}
		
		/**
		 * Is the license scheduled to expire within the specified interval(s)
		 *
		 * @param string $product
		 *
		 * @return bool
		 */
		public static function is_license_expiring( $product ) {
			
			$utils = Utilities::get_instance();
			
			$settings = self::get_settings( $product );
			
			if ( empty( $settings['expires'] ) ) {
				return false;
			}
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Expiration date for {$product}: {$settings['expires']}" );
			}
			
			$expiration_interval     = apply_filters( 'e20r_licensing_expiration_warning_intervals', 30 );
			$calculated_warning_time = strtotime( "+ {$expiration_interval} day", current_time( 'timestamp' ) );
			$diff                    = $settings['expires'] - $calculated_warning_time;
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "{$product} scheduled to expire on {$settings['expires']} vs {$calculated_warning_time}" );
			}
			
			if ( $settings['expires'] <= $calculated_warning_time && $diff > 0 ) {
				return true;
			} else if ( $diff <= 0 ) {
				return - 1;
			}
			
			return false;
		}
		
		/**
		 * Activate the license key on the remote server
		 *
		 * @param string $product
		 * @param array  $settings
		 *
		 * @return array
		 *
		 * @since 1.8.4 - BUG FIX: Didn't save the license settings
		 */
		public static function activate_license( $product, $settings ) {
			
			global $current_user;
			
			$state = null;
			$utils = Utilities::get_instance();
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Attempting to activate {$product} on remote server: " . print_r( $settings, true ) );
			}
			
			if ( empty( $settings ) ) {
				
				$settings        = self::default_settings( $product );
				$settings['key'] = $product;
			}
			
			$api_params = array(
				'slm_action'        => 'slm_activate',
				'license_key'       => $settings['key'],
				'secret_key'        => self::E20R_LICENSE_SECRET_KEY,
				'registered_domain' => $_SERVER['SERVER_NAME'],
				'item_reference'    => urlencode( $product ),
				'first_name'        => $settings['first_name'],
				'last_name'         => $settings['last_name'],
				'email'             => $settings['email'],
			);
			
			// Send query to the license manager server
			$decoded = self::send_to_license_server( $api_params );
			
			if ( false === $decoded ) {
				$msg = __( "Error transmitting to the remote licensing server", self::$text_domain );
				// $utils->add_message( $msg, 'error', 'backend' );
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( $msg );
				}
				
				return array( 'status' => 'blocked', 'settings' => null );
			}
			
			if ( isset( $decoded->result ) ) {
				
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Decoded JSON and received a status... ({$decoded->result})" );
				}
				
				switch ( $decoded->result ) {
					
					case 'success':
						$settings['status'] = 'active';
						
						if ( E20R_LICENSING_DEBUG ) {
							$utils->log( "Added {$product} to license list" );
							$utils->log( "Activated {$product} on the remote server." );
						}
						
						$state = true;
						break;
					
					case 'error':
						
						$msg = $decoded->message;
						
						if ( false !== stripos( $msg, 'maximum' ) ) {
							$state = self::E20R_LICENSE_MAX_DOMAINS;
						} else {
							$state = self::E20R_LICENSE_ERROR;
						}
						
						$settings['status'] = 'blocked';
						
						if ( isset( $decoded->error_code ) ) {
							switch ( intval( $decoded->error_code ) ) {
								
								case 40:
									// Key/domain combo is already an active license
									if ( E20R_LICENSING_DEBUG ) {
										$utils->log( "Flagging {$settings['key']} as already active for this server" );
									}
									$state = self::E20R_LICENSE_DOMAIN_ACTIVE;
									$settings['status'] = 'active';
									break;
							}
						}
						
						$utils->add_message( sprintf( __( "For %s: %s", self::$text_domain ), $settings['key'], $decoded->message ), $decoded->result, 'backend' );
						if ( E20R_LICENSING_DEBUG ) {
							$utils->log( "{$decoded->message}" );
						}
						// unset( $settings[ $product ] );
						break;
				}
				
				$settings['timestamp'] = current_time( 'timestamp' );
			}
			
			return array( 'status' => $state, 'settings' => $settings );
		}
		
		/**
		 * Deactivate the specified license (product/license key)
		 *
		 * @param string $product
		 * @param array|null $settings
		 *
		 * @return bool
		 */
		public static function deactivate_license( $product, $settings = null ) {
			
			$utils = Utilities::get_instance();
			
			if ( is_null( $settings ) ) {
				$settings = self::get_settings( $product );
			}
			
			if ( empty( $settings['key'] ) ) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "No license key, so nothing to deactivate" );
				}
				return false;
			}
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Attempting to deactivate {$product} on remote server" );
			}
			
			$api_params = array(
				'slm_action'        => 'slm_deactivate',
				'license_key'       => $settings['key'],
				'secret_key'        => self::E20R_LICENSE_SECRET_KEY,
				'registered_domain' => $_SERVER['SERVER_NAME'],
				'status'            => 'pending',
			);
			
			$decoded = self::send_to_license_server( $api_params );
			
			if ( false === $decoded ) {
				return $decoded;
			}
			
			/**
			 * Check if the result is the 'Already inactive' ( status: 80 )
			 */
			if ( 'error' === $decoded->result && (
					isset( $decoded->error_code ) &&
					80 == $decoded->error_code &&
					1 === preg_match( '/domain is already inactive/i', $decoded->message )
				) ) {
				
				// Then override the status.
				$decoded->result = 'success';
			}
			
			if ( 'success' !== $decoded->result ) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log("Error deactivating the license!");
				}
				return false;
			}
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Removing license {$product}..." );
			}
			
			if ( false === self::update_settings( $product, null ) ) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Unable to save settings (after removal) for {$product}" );
				}
			}
			
			return true;
			
		}
		
		/**
		 * Connect to license server and check status for the current product/server
		 *
		 * @param string     $product
		 * @param null|array $settings
		 * @param bool $force
		 *
		 * @return bool
		 */
		private static function get_license_status_from_server( $product, $settings = null, $force = false ) {
			
			$utils = Utilities::get_instance();
			
			// Default value for the license (it's not active)
			$license_status = false;
			global $current_user;
			
			if ( is_null( $settings ) ) {
				$settings = self::get_settings( $product );
			}
			
			if ( empty( $settings['key'] ) ) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "{$product} has no key stored. Returning false" );
				}
				return $license_status;
			}
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Local license settings for {$product}: " . print_r( $settings, true ) );
			}
			
			if ( true === $force || false === ( $license_status = (bool) Cache::get( "{$product}_status", 'e20r_licensing' ) ) ) {
				
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Connecting to license server to validate license for {$product}" );
				}
				
				$product_name = $settings['fulltext_name'];
				
				// Configure request for license check
				$api_params = array(
					'slm_action'  => 'slm_check',
					'secret_key'  => self::E20R_LICENSE_SECRET_KEY,
					'license_key' => $settings['key'],
					// 'registered_domain' => $_SERVER['SERVER_NAME']
				);
				
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Transmitting request to License server for {$product}" );
				}
				
				$decoded = self::send_to_license_server( $api_params );
				
				// License not validated
				if ( ! isset( $decoded->result ) || 'success' != $decoded->result ) {
					
					
					if ( isset( $settings['fulltext_name'] ) ) {
						$name = $settings['fulltext_name'];
					} else {
						$name = $product_name;
					}
					
					$msg = sprintf( __( "Sorry, no valid license found for: %s", self::$text_domain ), $name );
					if ( E20R_LICENSING_DEBUG ) {
						$utils->log( $msg );
					}
					$utils->add_message( $msg, 'error', 'backend' );
					
					return $license_status;
				}
				
				if ( is_array( $decoded->registered_domains ) ) {
					if ( E20R_LICENSING_DEBUG ) {
						$utils->log( "Processing license data for (count: " . count( $decoded->registered_domains ) . " domains )" );
					}
					
					foreach ( $decoded->registered_domains as $domain ) {
						
						if ( isset( $domain->registered_domain ) && $domain->registered_domain == $_SERVER['SERVER_NAME'] ) {
							
							if ( '0000-00-00' != $decoded->date_renewed ) {
								$settings['renewed'] = strtotime( $decoded->date_renewed, current_time( 'timestamp' ) );
							} else {
								$settings['renewed'] = current_time( 'timestamp' );
							}
							$settings['domain']        = $domain->registered_domain;
							$settings['fulltext_name'] = $product_name;
							$settings['expires']       = isset( $decoded->date_expiry ) ? strtotime( $decoded->date_expiry, current_time( 'timestamp' ) ) : null;
							$settings['status']        = $decoded->status;
							$settings['first_name']    = $current_user->first_name;
							$settings['last_name']     = $current_user->last_name;
							$settings['email']         = $decoded->email;
							$settings['timestamp']     = current_time( 'timestamp' );
							
							if ( E20R_LICENSING_DEBUG ) {
								$utils->log( "Saving license data for {$domain->registered_domain}: " . print_r( $settings, true ) );
							}
							if ( false === self::update_settings( $product, $settings ) ) {
								
								$msg = sprintf( __( "Unable to save license settings for %s", self::$text_domain ), $product );
								if ( E20R_LICENSING_DEBUG ) {
									$utils->log( $msg );
								}
								$utils->add_message( $msg, 'error', 'backend' );
							}
							
							$license_status = ( 'active' === $settings['status'] ? true : false );
							if ( E20R_LICENSING_DEBUG ) {
								$utils->log( "Current status for {$product} license: " . ( $license_status ? 'active' : 'inactive/deactivated/blocked' ) );
							}
						} else {
							if ( E20R_LICENSING_DEBUG ) {
								$utils->log( "Wrong domain, or domain info not found" );
							}
						}
					}
				} else {
					if ( E20R_LICENSING_DEBUG ) {
						$utils->log( "The {$product} license is on the server, but not active for this domain" );
					}
					$license_status = false;
				}
				
				if ( isset( $settings['expires'] ) && $settings['expires'] < current_time( 'timestamp' ) || ( isset( $settings['active'] ) && 'active' !== $settings['status'] ) ) {
					
					$msg = sprintf(
						__( "Your update license has expired for the %s add-on!", self::$text_domain ),
						$settings['fulltext_name']
					);
					
					if ( E20R_LICENSING_DEBUG ) {
						$utils->log( $msg );
					}
					$utils->add_message( $msg, 'error' );
					$license_status = false;
				}
				
				Cache::set( "{$product}_status", $license_status, DAY_IN_SECONDS, 'e20r_licensing' );
			}
			
			return $license_status;
		}
		
		/**
		 * Transmit Request to the Licensing server
		 *
		 * @param array $api_params
		 *
		 * @return \stdClass|false
		 */
		private static function send_to_license_server( $api_params ) {
			
			$utils = Utilities::get_instance();
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Attempting remote connection to " . self::E20R_LICENSE_SERVER_URL );
			}
			// Send query to the license manager server
			$response = wp_remote_post(
				self::E20R_LICENSE_SERVER_URL,
				array(
					'timeout'     => apply_filters( 'e20r-license-remote-server-timeout', 30 ),
					'sslverify'   => true,
					'httpversion' => '1.1',
					'decompress'  => true,
					'body' 	      => $api_params,
				)
			);
			
			// Check for error in the response
			if ( is_wp_error( $response ) ) {
				
				$msg = sprintf( __( "E20R Licensing: %s", self::$text_domain ), $response->get_error_message() );
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( $msg );
				}
				$utils->add_message( $msg, 'error' );
				
				return false;
			}
			
			$license_data = stripslashes( wp_remote_retrieve_body( $response ) );
			
			$bom          = pack( 'H*', 'EFBBBF' );
			$license_data = preg_replace( "/^$bom/", '', $license_data );
			$decoded      = json_decode( $license_data );
			
			if ( null === $decoded && json_last_error() !== JSON_ERROR_NONE ) {
				
				switch ( json_last_error() ) {
					case JSON_ERROR_DEPTH:
						$error = __( 'Maximum stack depth exceeded', self::$text_domain );
						break;
					case JSON_ERROR_STATE_MISMATCH:
						$error = __( 'Underflow or the modes mismatch', self::$text_domain );
						break;
					case JSON_ERROR_CTRL_CHAR:
						$error = __( 'Unexpected control character found', self::$text_domain );
						break;
					case JSON_ERROR_SYNTAX:
						$error = __( 'Syntax error, malformed JSON', self::$text_domain );
						break;
					case JSON_ERROR_UTF8:
						$error = __( 'Malformed UTF-8 characters, possibly incorrectly encoded', self::$text_domain );
						break;
					default:
						$error = sprintf( __( "No error, supposedly? %s", self::$text_domain ), print_r( json_last_error(), true ) );
				}
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Response from remote server: <" . $license_data . ">" );
					$utils->log( "JSON decode error: " . $error );
				}
				
				return false;
			}
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "License data received: (" . print_r( $decoded, true ) . ")" );
			}
			
			return $decoded;
		}
		
		/**
		 * Load local settings for the specified product
		 *
		 * @param string $product
		 *
		 * @return array
		 */
		private static function get_settings( $product = null ) {
			
			$utils = Utilities::get_instance();
			
			if ( is_null( $product ) ) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "No product key provided. Using default key (e20r_default_license)!" );
				}
				$product = 'e20r_default_license';
			}
			
			$defaults = self::default_settings( $product );
			$settings = get_option( 'e20r_license_settings', $defaults );
			
			if ( empty( $settings ) ) {
				$settings = $defaults;
			}
			
			if ( 'e20r_default_license' == $product || empty( $product ) ) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "No product, or default product specified, so returning all settings: {$product}" );
				}
				
				return $settings;
			}
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Requested and returning settings for {$product}" );
			}
			return isset( $settings[ $product ] ) ? $settings[ $product ] : null;
		}
		
		/**
		 * Save the license settings
		 *
		 * @param string $product
		 * @param array  $new_settings
		 *
		 * @return bool|array
		 */
		private static function update_settings( $product = null, $new_settings ) {
			
			$utils            = Utilities::get_instance();
			$license_settings = self::get_settings();
			
			if ( E20R_LICENSING_DEBUG ) {
				// $utils->log( "Settings before update: " . print_r( $license_settings, true ) );
				// $utils->log( "NEW settings for {$product}: " . print_r( $new_settings, true ) );
			}
			
			// Make sure the new settings make sense
			if ( is_array( $license_settings ) && in_array( 'fieldname', array_keys( $license_settings ) ) ) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Unexpected settings layout while processing {$product}!" );
				}
				$license_settings             = self::default_settings();
				$license_settings[ $product ] = $new_settings;
			}
			
			// Need to update the settings for a (possibly) pre-existing product
			if ( ! is_null( $product ) && ! empty( $new_settings ) && ! in_array( $product, array(
					'e20r_default_license',
					'example_gateway_addon',
				) ) && ! empty( $product )
			) {
				
				$license_settings[ $product ] = $new_settings;
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Updating license settings for {$product}" );
				}
				
			} else if ( ! is_null( $product ) && empty( $new_settings ) && ( ! in_array( $product, array(
						'e20r_default_license',
						'example_gateway_addon',
					) ) && ! empty( $product ) )
			) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Removing license settings for {$product}" );
				}
				unset( $license_settings[ $product ] );
				
			} else {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Requested save of everything" );
				}
			}
			
			if ( E20R_LICENSING_DEBUG ) {
				// $utils->log( "Saving: " . print_r( $license_settings, true ) );
			}
			
			update_option( 'e20r_license_settings', $license_settings, 'yes' );
			
			return $license_settings;
		}
		
		/**
		 * Settings array for the License(s) on this system
		 *
		 * @param string $product
		 *
		 * @return array
		 */
		private static function default_settings( $product = 'e20r_default_license' ) {
			
			return array(
				$product => array(
					'key'           => null,
					'renewed'       => null,
					'domain'        => $_SERVER['SERVER_NAME'],
					'product'       => $product,
					'fulltext_name' => '',
					'expires'       => '',
					'status'        => '',
					'first_name'    => '',
					'last_name'     => '',
					'email'         => '',
					'timestamp'     => current_time( 'timestamp' ),
				),
			);
		}
		
		/**
		 * Add the options section for the Licensing Options page
		 */
		public static function add_options_page() {
			
			// Check whether the Licensing page is already loaded or not
			if ( false === self::is_license_page_loaded( 'e20r-licensing', true ) ) {
				
				$class = self::get_instance();
				$class->load_license_settings_page();
			}
		}
		
		/**
		 * Verifies if the E20R Licenses option page is loaded by someone else
		 */
		public function load_license_settings_page() {
			
			$utils = Utilities::get_instance();
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Attempting to add options page for E20R Licenses" );
			}
			
			$handle = add_options_page(
				__( "E20R Licenses", self::$text_domain ),
				__( "E20R Licenses", self::$text_domain ),
				'manage_options',
				'e20r-licensing',
				array( $this, 'licensing_page' )
			);
		}
		
		/**
		 * Check whether the Licensing page is already loaded or not
		 *
		 * @param string $handle
		 * @param bool   $sub
		 *
		 * @return bool
		 */
		public static function is_license_page_loaded( $handle, $sub = false ) {
			
			$utils = Utilities::get_instance();
			
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "AJAX request or not in wp-admin" );
				}
				
				return false;
			}
			
			global $menu;
			global $submenu;
			
			$check_menu = $sub ? $submenu : $menu;
			
			if ( empty( $check_menu ) ) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "No menu object found??" );
				}
				
				return false;
			}
			
			$item = $check_menu['options-general.php'];
			
			if ( true === $sub ) {
				
				foreach ( $item as $subm ) {
					
					if ( $subm[2] == $handle ) {
						if ( E20R_LICENSING_DEBUG ) {
							$utils->log( "Settings submenu already loaded: " . urldecode( $subm[2] ) );
						}
						
						return true;
					}
				}
			} else {
				
				if ( $item[2] == $handle ) {
					if ( E20R_LICENSING_DEBUG ) {
						$utils->log( "Menu already loaded: " . urldecode( $item[2] ) );
					}
					
					return true;
				}
			}
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Loading licensing page..." );
			}
			
			return false;
		}
		
		/**
		 * Register all Licensing settings
		 *
		 * @since 1.5 - BUG FIX: Incorrect namespace used in register_setting(), add_sttings_section() and add_settings_field() functions
		 * @since 1.6 - BUG FIX: Used wrong label for new licenses
		 */
		static public function register_settings() {
			
			$utils        = Utilities::get_instance();
			$license_list = array();
			
			register_setting(
				"e20r_license_settings", // group, used for settings_fields()
				"e20r_license_settings",  // option name, used as key in database
				'E20R\Utilities\Licensing\Licensing::validate_settings'     // validation callback
			);
			
			add_settings_section(
				'e20r_licensing_section',
				__( "Configure Licenses", self::$text_domain ),
				'E20R\Utilities\Licensing\Licensing::show_licensing_section',
				'e20r-licensing'
			);
			
			$settings        = apply_filters( 'e20r-license-add-new-licenses', self::get_settings(), array() );
			$license_counter = 0;
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Found " . count( $settings ) . " potential licenses" );
			}
			
			foreach ( $settings as $k => $license ) {
				
				// Skip and clean up.
				if ( isset( $license['key'] ) && empty( $license['key'] ) ) {
					
					unset( $settings[ $k ] );
					update_option( 'e20r_license_settings', $settings, 'yes' );
					if ( E20R_LICENSING_DEBUG ) {
						$utils->log( "Skipping {$k} with settings (doesn't have a key yet): " . print_r( $license, true ) );
					}
					continue;
				}
				
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Generate settings fields for {$k}?" );
				}
				
				if ( $k !== 'example_gateway_addon' && $k !== 'new_licenses' && isset( $license['key'] ) && $license['key'] != 'e20r_default_license' && ! empty( $license['key'] ) ) {
					
					if ( E20R_LICENSING_DEBUG ) {
						$utils->log( "Previously activated license: {$k}: adding {$license['fulltext_name']} fields" );
						$utils->log( "Settings: " . print_r( $license, true ) );
					}
					
					if ( ! isset( $license['status']) ) {
						$license['status'] = 'inactive';
					}
					
					add_settings_field(
						"{$license['key']}",
						"{$license['fulltext_name']} (" . ucfirst( $license['status'] ) . ")",
						'E20R\Utilities\Licensing\Licensing::show_input',
						'e20r-licensing',
						'e20r_licensing_section',
						array(
							'index'         => $license_counter,
							'label_for'     => $license['key'],
							'product'       => $k,
							'option_name'   => "e20r_license_settings",
							'fulltext_name' => $license['fulltext_name'],
							'name'          => 'license_key',
							'input_type'    => 'password',
							'value'         => $license['key'],
							'email_field'   => "license_email",
							'email_value'   => ! empty( $license['email'] ) ? $license['email'] : null,
							'placeholder'   => __( "Paste the purchased key here", self::$text_domain ),
						)
					);
					
					$license_list[] = $k;
					$license_counter ++;
				}
				
				if ( 'new_licenses' === $k ) {
					
					$new_licenses = $license;
					
					foreach ( $new_licenses as $nk => $new ) {
						
						if ( E20R_LICENSING_DEBUG ) {
							$utils->log( "Processing: {$nk}" );
							$utils->log( "Processing new license field for {$new['new_product']}" );
						}
						
						// Skip if we've got this one in the list of licenses already.
						
						if ( ! in_array( $new['new_product'], $license_list ) && $nk !== 'example_gateway_addon' ) {
							if ( E20R_LICENSING_DEBUG ) {
								$utils->log( "Adding new license fields for {$new['new_product']} (one of " . count( $new_licenses ) . " unlicensed add-ons)" );
							}
							
							add_settings_field(
								"e20r_license_new_{$nk}",
								sprintf( __( "Add %s license", self::$text_domain ), $new['fulltext_name'] ),
								'E20R\Utilities\Licensing\Licensing::show_input',
								'e20r-licensing',
								'e20r_licensing_section',
								array(
									'index'         => $license_counter,
									'label_for'     => $new['new_product'],
									'fulltext_name' => $new['fulltext_name'],
									'option_name'   => "e20r_license_settings",
									'new_product'   => $new['new_product'],
									'name'          => "new_license",
									'input_type'    => 'text',
									'value'         => null,
									'email_field'   => "new_email",
									'email_value'   => null,
									'placeholder'   => $new['placeholder'],
								)
							);
							
							$license_counter ++;
							if ( E20R_LICENSING_DEBUG ) {
								$utils->log( "New license field(s) added for {$nk}" );
							}
						}
					}
				}
			}
		}
		
		/**
		 * Show the licensing section on the options page
		 */
		public static function show_licensing_section() {
			
			$utils = Utilities::get_instance();
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Loading section HTML for License Settings" );
			}
			
			$pricing_page = apply_filters( 'e20r-license-pricing-page-url', 'https://eighty20results.com/shop/' );
			?>
            <p class="e20r-licensing-section"><?php _e( "This add-on is distributed under version 2 of the GNU Public License (GPLv2). One of the things the GPLv2 license grants is the right to use this software on your site, free of charge.", self::$text_domain ); ?></p>
            <p class="e20r-licensing-section">
                <a href="<?php echo esc_url_raw( $pricing_page ); ?>"
                   target="_blank"><?php _e( "Purchase Licenses/Add-ons &raquo;", self::$text_domain ); ?></a>
            </p>
            <table class="form-table">
                <tr>
                    <th style="width: 200px; min-width: 200px;"><?php _e( "Name", self::$text_domain ); ?></th>
                    <th style="min-width: 350px;"><?php _e( "Key", self::$text_domain ); ?></th>
                    <th style="min-width: 200px;"><?php _e( "Email", self::$text_domain ); ?></th>
                    <th><?php _e( "Deactivate", self::$text_domain ); ?></th>
                </tr>
            </table>
			<?php
		}
		
		/**
		 * Show input row for License page
		 *
		 * @param $args
		 *
		 * @since 1.6 - BUG FIX: Used incorrect product label for new licenses
		 */
		public static function show_input( $args ) {
			
			global $current_user;
			$utils = Utilities::get_instance();
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Loading input HTML for: " . print_r( $args, true ) );
			}
			if ( isset( $args['product'] ) ) {
				
				$product  = $args['product'];
				$var_name = "{$args['option_name']}[product][{$args['index']}]";
				
			} else if ( isset( $args['new_product'] ) ) {
				
				$product             = $args['new_product'];
				$var_name            = "{$args['option_name']}[new_product][{$args['index']}]";
				$args['email_value'] = $current_user->user_email;
			}
			
			printf( '<input type="hidden" name="%1$s" value="%2$s" />', "{$args['option_name']}[fieldname][{$args['index']}]", $args['value'] );
			printf( '<input type="hidden" name="%1$s" value="%2$s" />', "{$args['option_name']}[fulltext_name][{$args['index']}]", $args['fulltext_name'] );
			printf( '<input type="hidden" name="%1$s" value="%2$s" />', $var_name, $product );
			printf(
				'<input name="%1$s[%2$s][%7$d]" type="%3$s" id="%4$s" value="%5$s" placeholder="%6$s" class="regular_text" style="min-width: 350px; max-width: 350px;">',
				$args['option_name'],
				$args['name'],
				$args['input_type'],
				$args['label_for'],
				$args['value'],
				$args['placeholder'],
				$args['index']
			); ?>
            </td>
            <td>
				<?php
				printf(
					'<input name="%1$s[%2$s][%6$d]" type="email" id=%3$s_email value="%4$s" placeholder="%5$s" class="email_address" style="width: 200px;">',
					$args['option_name'],
					$args['email_field'],
					$args['label_for'],
					$args['email_value'],
					__( "Email used to buy license", "e20rlicense" ),
					$args['index']
				); ?>
            </td>
            <td>
			<?php if ( $args['name'] != 'new_key' ) { ?>
				<?php
				printf(
					'<input type="checkbox" name="%1$s[delete][%3$d]" class="clear_license" style="float: left;" value="%2$s">',
					$args['option_name'],
					$args['value'],
					$args['index']
				);
			} ?>
            </td><?php
		}
		
		/**
		 * The page content for the E20R Licensing section
		 *
		 * @since 1.6.1 - BUG FIX: Would sometimes show the wrong license status on the licensing page
		 */
		public static function licensing_page() {
			
			$utils = Utilities::get_instance();
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Testing access for Licensing page" );
			}
			
			if ( ! function_exists( "current_user_can" ) || ( ! current_user_can( "manage_options" ) && ! current_user_can( "e20r_license_admin" ) ) ) {
				wp_die( __( "You are not permitted to perform this action.", self::$text_domain ) );
			}
			
			$utils = Utilities::get_instance();
			?>
			<?php $utils->display_messages(); ?>
            <br/>
            <h2><?php echo $GLOBALS['title']; ?></h2>
            <form action="options.php" method="POST">
				<?php
				settings_fields( "e20r_license_settings" );
				do_settings_sections( 'e20r-licensing' );
				submit_button();
				?>
            </form>
			<?php
			
			$settings            = apply_filters( 'e20r-license-add-new-licenses', self::get_settings(), array() );
			$support_account_url = apply_filters( 'e20r-license-support-account-url', sprintf( 'https://eighty20results.com/login/?redirect_to=%s', home_url( '/account/' ) ) );
			
			foreach ( $settings as $prod => $license ) {
				
				if ( in_array( $prod, array( 'e20r_default_license', 'new_licenses', 'example_gateway_addon' ) ) ) {
					continue;
				}
				
				/**
				 * @since 1.6.1 - BUG FIX: Would sometimes show the wrong license status on the licensing page
				 */
				$license_valid = self::is_licensed( $prod, true ) && ( isset( $license['status'] ) && 'active' === $license['status'] );
				
				?>

                <div class="wrap"><?php
					if ( false === $license_valid && ( isset( $license['expires'] ) && $license['expires'] <= current_time( 'timestamp' ) || empty( $license['expires'] ) ) ) {
						?>
                        <div class="notice notice-error inline">
                        <p>
                            <strong><?php printf( __( 'Your <em>%s</em> license is either not configured, invalid or has expired.', self::$text_domain ), $license['fulltext_name'] ); ?></strong>
							<?php printf( __( 'Visit your Eighty / 20 Results <a href="%s" target="_blank">Support Account</a> page to confirm that your account is active and to locate your license key.', self::$text_domain ), $support_account_url ); ?>
                        </p>
                        </div><?php
					}
					
					if ( $license_valid ) {
						?>
                        <div class="notice notice-info inline">
                        <p>
                            <strong><?php _e( 'Thank you!', self::$text_domain ); ?></strong>
							<?php printf( __( "A valid %s license key is being used on this site.", self::$text_domain ), $license['fulltext_name'] ); ?>
                        </p>
                        </div><?php
						
					} ?>
                </div> <!-- end wrap -->
				<?php
			}
			
		}
		
		/**
		 * Prepare license settings for save operation
		 *
		 * @param array $input
		 *
		 * @return array
		 */
		public static function validate_settings( $input ) {
			
			global $current_user;
			$utils = Utilities::get_instance();
			
			if ( empty( $input['new_product'] ) && empty( $input['product'] ) && empty( $input['delete'] ) ) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Not being called by the E20R License settings page, so returning: " . print_r( $input, true ) );
				}
				
				return $input;
			}
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Validation input (Add License on Purchase): " . print_r( $input, true ) );
			}
			
			$license_settings = self::get_settings();
			
			// Save new license keys & activate the license
			if ( isset( $input['new_product'] ) && true === $utils->array_isnt_empty( $input['new_product'] ) ) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Processing a possible license activation" );
				}
				
				foreach ( $input['new_product'] as $nk => $product ) {
					
					if ( ! empty( $input['new_license'][ $nk ] ) ) {
						if ( E20R_LICENSING_DEBUG ) {
							$utils->log( "Processing license activation for {$input['new_license'][$nk]} " );
						}
						
						$license_key   = isset( $input['new_license'][ $nk ] ) ? $input['new_license'][ $nk ] : null;
						$license_email = isset( $input['new_email'][ $nk ] ) ? $input['new_email'][ $nk ] : null;
						$product       = isset( $input['new_product'][ $nk ] ) ? $input['new_product'][ $nk ] : null;
						
						$license_settings[ $product ]['first_name']    = $current_user->first_name;
						$license_settings[ $product ]['last_name']     = $current_user->last_name;
						$license_settings[ $product ]['fulltext_name'] = $input['fulltext_name'][ $nk ];
						
						if ( ! empty( $license_email ) && ! empty( $license_key ) ) {
							
							$license_settings[ $product ]['email'] = $license_email;
							$license_settings[ $product ]['key']   = $license_key;
							if ( E20R_LICENSING_DEBUG ) {
								$utils->log( "Attempting remote activation for {$product} " );
							}
							
							$result = self::activate_license( $product, $license_settings[ $product ] );
							
							if ( E20R_LICENSING_DEBUG ) {
								$utils->log( "Status from activation {$result['status']} vs " . Licensing::E20R_LICENSE_DOMAIN_ACTIVE . " => " . print_r( $result, true ) );
							}
							
							if ( Licensing::E20R_LICENSE_DOMAIN_ACTIVE === intval( $result['status'] ) ) {
								
								if ( E20R_LICENSING_DEBUG ) {
									$utils->log( "This license & server combination is already active on the licensing server" );
								}
								
								if ( true === self::deactivate_license( $product, $result['settings'] ) ) {
									if ( E20R_LICENSING_DEBUG ) {
										$utils->log( "Was able to deactivate this license/host combination" );
									}
									
									$result = self::activate_license( $product, $license_settings[ $product ] );
									
								}
							}
							
							if ( E20R_LICENSING_DEBUG ) {
								$utils->log( "Loading updated settings from server" );
							}
							
							if ( true === self::get_license_status_from_server( $product, $license_settings[ $product ], true ) ) {
								$result['settings'] = self::get_settings( $product );
							}
							
							if ( $result['settings']['status'] !== 'active' ) {
								if ( E20R_LICENSING_DEBUG ) {
									$utils->log( "Error: Unable to activate license for {$product}!!!" );
								}
							} else {
								if ( E20R_LICENSING_DEBUG ) {
									$utils->log( "Updating license for {$product} to: " . print_r( $result['settings'], true ) );
								}
								
								$license_settings[ $product ] = $result['settings'];
								
								if ( E20R_LICENSING_DEBUG ) {
									$utils->log( "Need to save license settings for {$product}" );
								}
								
								if ( false === ( $license_settings = self::update_settings( $product, $license_settings[ $product ] ) ) ) {
									if ( E20R_LICENSING_DEBUG ) {
										$utils->log( "Unable to save the {$product} settings!" );
									}
								}
							}
						}
					} else {
						if ( E20R_LICENSING_DEBUG ) {
							$utils->log( "No new license key specified for {$product}" );
						}
					}
				}
			}
			
			// Process licenses to deactivate/delete
			if ( isset( $input['delete'] ) && true === $utils->array_isnt_empty( $input['delete'] ) ) {
				
				foreach ( $input['delete'] as $dk => $l ) {
					
					$lk = array_search( $l, $input['license_key'] );
					
					$utils->log( "License to deactivate: {$input['product'][$lk]}" );
					$product = $input['product'][ $lk ];
					
					$result = self::deactivate_license( $product );
					
					if ( false !== $result ) {
						
						$utils->log( "Successfully deactivated {$input['product'][ $lk ]} on remote server" );
						
						unset( $input['license_key'][ $lk ] );
						unset( $input['license_email'][ $lk ] );
						unset( $input['fieldname'][ $lk ] );
						unset( $input['fulltext_name'][ $lk ] );
						unset( $license_settings[ $product ] );
						unset( $input['product'][ $lk ] );
					}
				}
				
				// Save cleared license updates
				if ( false === self::update_settings( null, $license_settings ) ) {
					$utils->log( "Unable to save the settings!" );
				}
			}
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Returning validated settings" . print_r( $license_settings, true ) );
			}
			
			foreach ( $input as $license => $settings ) {
				
				if ( isset( $license_settings[ $license ] ) && in_array( 'domain', array_keys( $settings ) ) ) {
					if ( E20R_LICENSING_DEBUG ) {
						$utils->log( "Grabbing data from input and assigning it to license" );
					}
					
					$license_settings[ $license ] = $input[ $license ];
				}
			}
			
			return $license_settings;
		}
		
		/**
		 * Get the license page URL for the local admin/options page
		 *
		 * @param string $stub
		 *
		 * @return string
		 */
		public static function get_license_page_url( $stub ) {
			
			$license_page_url = esc_url( add_query_arg(
				array(
					'page'         => 'e20r-licensing',
					'license_stub' => $stub,
				),
				admin_url( 'options-general.php' )
			) );
			
			return $license_page_url;
		}
		
		public static function get_instance() {
			
			if ( null === self::$instance ) {
				self::$instance = new self;
			}
			
			return self::$instance;
		}
	}
	
}
