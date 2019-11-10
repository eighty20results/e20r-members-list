<?php
/**
 * Copyright (c) 2017-2019 - Eighty / 20 Results by Wicked Strong Chicks.
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
 * @version 3.0
 *
 */

namespace E20R\Utilities\Licensing;

use E20R\Utilities\Licensing\License_Page;
use E20R\Utilities\Utilities;
use E20R\Utilities\Cache;

if ( ! class_exists( '\E20R\Utilities\Licensing\Licensing' ) ) {
	
	if ( ! defined( 'E20R_LICENSING_DEBUG' ) ) {
		define( 'E20R_LICENSING_DEBUG', false );
	}
	if ( ! defined( 'E20R_LICENSING_VERSION' ) ) {
		define( 'E20R_LICENSING_VERSION', '3.0' );
	}
	
	if ( ! defined( 'E20R_LICENSE_SERVER_URL' ) ) {
		define( 'E20R_LICENSE_SERVER_URL', 'https://eighty20results.com/' );
	}
	
	/**
	 * Class Licensing
	 * @package E20R\Utilities\Licensing
	 */
	class Licensing {
		
		/**
		 * License cache keys
		 */
		const CACHE_KEY = 'active_licenses';
		const CACHE_GROUP = 'e20r_licensing';
		
		/**
		 * License status constants
		 */
		const E20R_LICENSE_MAX_DOMAINS = 2048;
		const E20R_LICENSE_REGISTERED = 1024;
		const E20R_LICENSE_DOMAIN_ACTIVE = 512;
		const E20R_LICENSE_ERROR = 256;
		
		/**
		 * Current instance of this class (singleton)
		 *
		 * @var null|Licensing
		 */
		private static $instance = null;
		
		/**
		 * I18N domain name (translation)
		 *
		 * @var string|null
		 */
		private static $text_domain;
		
		/**
		 * New or old Licensing plugin in store
		 *
		 * @var bool
		 */
		private static $new_version = false;
		
		/**
		 * Use SSL certificate validation when checking license
		 *
		 * @var bool
		 */
		private static $ssl_verify = true;
		
		/**
		 * Configure the Licensing class (actions and settings)
		 * Licensing constructor.
		 */
		private function __construct() {
			
			$utils = Utilities::get_instance();
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Loading the Licensing class..." );
			}
			
			if ( isset( $_SERVER['HTTP_HOST'] ) && $_SERVER['HTTP_HOST'] === 'eighty20results.com' ) {
				$utils->log( "Running on own server. Deactivating SSL Verification" );
				self::$ssl_verify = false;
			}
		}
		
		/**
		 * Load action hooks for the E20R Licensing utilities module
		 */
		public function load_hooks() {
			add_action( 'admin_enqueue_scripts', array( self::get_instance(), 'enqueue' ), 10 );
		}
		
		/**
		 * Load License page specific scripts and style(s)
		 */
		public function enqueue() {
			
			wp_enqueue_style(
				'e20r-utilities-licensing',
				plugins_url( 'css/e20r-utilities-licensing.css', __FILE__ )
			);
		}
		
		/**
		 * Return the value of ssl_verify
		 *
		 * @return bool
		 */
		public static function get_ssl_verify() {
			
			if ( empty( self::$instance ) ) {
				self::get_instance();
			}
			
			return self::$ssl_verify;
		}
		
		/**
		 * Return the value of new_version
		 *
		 * @return bool
		 */
		public static function is_new_version() {
			if ( empty( self::$instance ) ) {
				self::get_instance();
			}
			
			return self::$new_version;
		}
		
		/**
		 * Return the text domain for I18N (translation)
		 *
		 * @return string
		 */
		public static function get_text_domain() {
			if ( empty( self::$instance ) ) {
				self::get_instance();
			}
			
			return self::$text_domain;
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
			
			if ( self::is_local_server() ) {
				$utils->log( "Running on the license server so skipping check (treating as licensed)" );
				
				return true;
			}
			
			if ( empty( self::$instance ) ) {
				self::get_instance();
			}
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Checking license for {$product_stub}" );
			}
			
			if ( true === $force || E20R_LICENSING_DEBUG ) {
				
				$utils->log( "Forcing remote lookup of license for {$product_stub}" );
				Cache::delete( self::CACHE_KEY, self::CACHE_GROUP );
			}
			
			$excluded = apply_filters( 'e20r_licensing_excluded', array(
				'e20r_default_license',
				'example_gateway_addon',
				'new_licenses',
			) );
			
			$is_licensed = License_Server::get_license_status_from_server( $product_stub );
			
			if ( ! in_array( $product_stub, $excluded ) &&
			     ( null === ( $license_settings = Cache::get( self::CACHE_KEY, self::CACHE_GROUP ) ) ||
			       ( true === $force || true === E20R_LICENSING_DEBUG ) )
			) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "License status IS NOT cached for {$product_stub}" );
				}
				
				// Get new/existing settings
				$license_settings = License_Settings::get_settings();
				
				if ( ! isset( $license_settings[ $product_stub ] ) ) {
					$license_settings[ $product_stub ] = array();
				}
				
				$license_settings[ $product_stub ] = isset( $license_settings[ $product_stub ] ) ?
					$license_settings[ $product_stub ] :
					License_Settings::default_settings( $product_stub );
				
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Using license settings for {$product_stub}: " . print_r( $license_settings[ $product_stub ], true ) );
				}
				// Update the local cache for the license
				Cache::set( self::CACHE_KEY, $license_settings, DAY_IN_SECONDS, self::CACHE_GROUP );
			}
			
			return self::is_active( $product_stub, $license_settings, $is_licensed );
		}
		
		/**
		 * Check if a licensed product has an active license
		 *
		 * @param string $product_stub
		 * @param array  $license_settings
		 * @param bool   $is_licensed
		 *
		 * @return bool
		 */
		public static function is_active( $product_stub, $license_settings, $is_licensed = false ) {
			
			$utils     = Utilities::get_instance();
			$is_active = false;
			
			if ( empty( self::$instance ) ) {
				self::get_instance();
			}
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "New or old licensing plugin? " . ( self::is_new_version() ? 'New' : 'Old' ) );
			}
			
			if ( isset( $license_settings[ $product_stub ] ) ) {
				$license_settings = $license_settings[ $product_stub ];
			}
			
			if ( true === self::is_new_version() ) {
				
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Status of license under new licensing plugin... Is licensed? " . ( $is_licensed ? 'True' : 'False' ) );
					// $utils->log( "License settings received for {$product_stub}: " . print_r( $license_settings, true ) );
				}
				
				$is_active = ( ! empty( $license_settings['the_key'] ) &&
				               ! empty( $license_settings['status'] ) &&
				               'active' === $license_settings['status'] &&
				               true === $is_licensed
				);
			} else if ( false === self::is_new_version() ) {
				
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Status of license under old licensing plugin... Is licensed? " . ( $is_licensed ? 'True' : 'False' ) );
				}
				
				$is_active = ( ! empty( $license_settings['key'] ) &&
				               ! empty( $license_settings['status'] ) &&
				               'active' == $license_settings['status'] &&
				               $license_settings['domain'] == $_SERVER['SERVER_NAME'] &&
				               true === $is_licensed
				);
			} else {
				$utils->log( "Neither old nor new licensing plugin selected!!!" );
				
				return false;
			}
			
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
			
			if ( empty( self::$instance ) ) {
				self::get_instance();
			}
			
			$settings = License_Settings::get_settings( $product );
			$expires  = null;
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Received settings for expiration check: " . print_r( $settings, true ) );
			}
			
			if ( self::is_new_version() ) {
				$expires = (int) $settings['expire'];
			} else {
				$expires = (int) $settings['expires'];
			}
			
			if ( empty( $expires ) ) {
				
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "NOTICE: The {$product} license does not expire" );
				}
				
				return false;
			}
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Expiration date for {$product}: " .
				             ( ! empty( $expires ) ? date( 'Y-m-d H:i:s', $expires ) : 'Never' )
				);
			}
			
			$expiration_interval     = apply_filters( 'e20r_licensing_expiration_warning_intervals', 30 );
			$calculated_warning_time = strtotime( "+ {$expiration_interval} day", current_time( 'timestamp' ) );
			$diff                    = $expires - current_time( 'timestamp' );
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "{$product} scheduled to expire on {$expires} vs {$calculated_warning_time}" );
			}
			
			if ( $expires <= $calculated_warning_time && $expires >= current_time( 'timestamp' ) && $diff > 0 ) {
				return true;
			} else if ( $expires <= current_time( 'timestamp' ) && $diff <= 0 ) {
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
			
			if ( empty( self::$instance ) ) {
				self::get_instance();
			}
			
			$state = null;
			$utils = Utilities::get_instance();
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Attempting to activate {$product} on remote server using: " . print_r( $settings, true ) );
			}
			
			if ( E20R_LICENSING_DEBUG && self::is_new_version() ) {
				$utils->log( "Using new license server plugin for activation..." );
			}
			
			if ( empty( $settings ) ) {
				
				$settings        = License_Settings::default_settings( $product );
				$settings['key'] = $product;
			}
			
			if ( ! self::is_new_version() ) {
				$api_params = array(
					'slm_action'        => 'slm_activate',
					'license_key'       => $settings['key'],
					'secret_key'        => License_Server::E20R_LICENSE_SECRET_KEY,
					'registered_domain' => isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : 'Unknown',
					'item_reference'    => urlencode( $product ),
					'first_name'        => $settings['first_name'],
					'last_name'         => $settings['last_name'],
					'email'             => $settings['email'],
				);
			} else {
				$api_params = array(
					'action'      => 'license_key_activate',
					'store_code'  => License_Server::E20R_LICENSE_STORE_CODE,
					'sku'         => $settings['product_sku'],
					'license_key' => $settings['key'],
					'domain'      => isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : 'Unknown',
				);
			}
			
			// Send query to the license manager server
			$decoded = License_Server::send_to_license_server( $api_params );
			
			if ( false === $decoded ) {
				$msg = __( "Error transmitting to the remote licensing server", self::$text_domain );
				// $utils->add_message( $msg, 'error', 'backend' );
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( $msg );
				}
				
				return array( 'status' => 'blocked', 'settings' => null );
			}
			
			if ( ! self::is_new_version() ) {
				
				$utils->log( "Using old licensing infrastructure" );
				
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
										$state              = self::E20R_LICENSE_DOMAIN_ACTIVE;
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
			
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Processing from new licensing server..." );
			}
			
			if ( true == $decoded->error ) {
				
				$utils->log( "New licensing server returned error..." );
				
				$state    = self::E20R_LICENSE_ERROR;
				$settings = License_Settings::default_settings( $product );
				
				$msg = __( 'Activation error: %s -> %s', 'e20r-utilities-licensing' );
				
				foreach ( (array) $decoded->errors as $error_key => $error_message ) {
					$utils->add_message(
						sprintf( $msg, $error_key, array_pop( $error_message ) ),
						'error',
						'backend'
					);
				}
			}
			
			if ( isset( $decoded->status ) && 200 === (int) $decoded->status ) {
				
				// $settings + $decoded->data == Merged settings
				
				if ( isset( $settings[ $product ] ) && ! empty( $settings[ $product ] ) ) {
					$existing_settings = $settings[ $product ];
				} else if ( ! isset( $settings[ $product ] ) && ! empty( $settings ) ) {
					$existing_settings = $settings;
				} else {
					$existing_settings = License_Settings::default_settings( $product );
				}
				
				$new_settings = (array) $decoded->data;
				
				foreach ( $new_settings as $key => $value ) {
					$existing_settings[ $key ] = $value;
				}
				
				$utils->add_message( $decoded->message, 'notice', 'backend' );
				
				$settings = $existing_settings;
				$state    = self::E20R_LICENSE_DOMAIN_ACTIVE;
			}
			
			return array( 'status' => $state, 'settings' => $settings );
		}
		
		/**
		 * Deactivate the specified license (product/license key)
		 *
		 * @param string     $product
		 * @param array|null $settings
		 *
		 * @return bool
		 */
		public static function deactivate_license( $product, $settings = null ) {
			
			$utils = Utilities::get_instance();
			
			if ( empty( self::$instance ) ) {
				self::get_instance();
			}
			
			if ( is_null( $settings ) ) {
				$settings = License_Settings::get_settings( $product );
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
			
			if ( E20R_LICENSING_DEBUG && self::is_new_version() ) {
				$utils->log( "Using new license server plugin for deactivation..." );
			}
			
			if ( ! self::is_new_version() ) {
				$api_params = array(
					'slm_action'        => 'slm_deactivate',
					'license_key'       => $settings['key'],
					'secret_key'        => License_Server::E20R_LICENSE_SECRET_KEY,
					'registered_domain' => $_SERVER['SERVER_NAME'],
					'status'            => 'pending',
				);
			} else {
				
				if ( ! isset( $settings['activation_id'] ) ) {
					$utils->log( "Unable to deactivate since activation_id data is missing!" );
					$utils->log( "Just clear the license..." );
					
					if ( false === License_Settings::update_settings( $product, null ) ) {
						if ( E20R_LICENSING_DEBUG ) {
							$utils->log( "Unable to save settings (after removal) for {$product}" );
						}
					}
					
					return true;
				}
				
				$api_params = array(
					'action'        => 'license_key_deactivate',
					'store_code'    => License_Server::E20R_LICENSE_STORE_CODE,
					'sku'           => $settings['product_sku'],
					'license_key'   => $settings['key'],
					'domain'        => isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : 'Unknown',
					'activation_id' => $settings['activation_id'],
				);
			}
			
			if ( self::is_new_version() ) {
				$utils->log( "Sending to server: " . print_r( $api_params, true ) );
			}
			
			$decoded = License_Server::send_to_license_server( $api_params );
			
			if ( self::is_new_version() ) {
				$utils->log( "Decoded: " . print_r( $decoded, true ) );
			}
			
			if ( false === $decoded ) {
				return $decoded;
			}
			
			if ( ! self::is_new_version() ) {
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
						$utils->log( "Error deactivating the license!" );
					}
					
					return false;
				}
				
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Removing license {$product}..." );
				}
				
				if ( false === License_Settings::update_settings( $product, null ) ) {
					if ( E20R_LICENSING_DEBUG ) {
						$utils->log( "Unable to save settings (after removal) for {$product}" );
					}
				}
				
				return true;
			} else if ( isset( $decoded->status ) ) {
				
				if ( isset( $decoded->status ) && 500 === (int) $decoded->status ) {
					
					$error_message = __( 'Deactivation error: %s', 'e20r-utilities-licensing' );
					
					if ( isset( $decoded->errors ) ) {
						
						$utils->log( "Decoding error messages from the License server..." );
						$error_string = __( '%s -> %s', 'e20r-utilities-licensing' );
						
						foreach ( (array) $decoded->errors as $error_key => $error_info ) {
							$info = array_pop( $error_info );
							$utils->log( sprintf( $error_message,
									sprintf( $error_string, $error_key, $info )
								)
							);
						}
						
					}
				}
				
				/*
				if ( isset( $decoded->status ) && 200 === (int) $decoded->status ) {
					$License_info = isset( $decoded->data ) ?
						$decoded->data :
						License_Settings::default_settings( $product );
				}
				*/
				
				return true;
			}
			
			return false;
			
		}
		
		/**
		 * Get the license page URL for the local admin/options page
		 *
		 * @param string $stub
		 *
		 * @return string
		 */
		public static function get_license_page_url( $stub ) {
			
			if ( empty( self::$instance ) ) {
				self::get_instance();
			}
			
			$license_page_url = esc_url( add_query_arg(
				array(
					'page'         => 'e20r-licensing',
					'license_stub' => $stub,
				),
				admin_url( 'options-general.php' )
			) );
			
			return $license_page_url;
		}
		
		/**
		 * Get or instantiate and get the Licensing class instance
		 *
		 * @return Licensing|null
		 */
		public static function get_instance() {
			
			if ( null === self::$instance ) {
				self::$instance = new self;
				$utils          = Utilities::get_instance();
				
				self::$text_domain = apply_filters( 'e20r-licensing-text-domain', self::$text_domain );
				
				// Determine whether we're using the new or old Licensing version
				self::$new_version = (
					defined( 'E20R_LICENSING_VERSION' ) &&
					version_compare( E20R_LICENSING_VERSION, '3.0', 'ge' )
				);
				
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Using new or old version of licensing code..? " . ( self::is_new_version() ? 'New' : 'Old' ) );
				}
				
				self::$ssl_verify = self::is_local_server();
				
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Do we verify the SSL certificate (no if local = home_url())? " . ( self::get_ssl_verify() ? 'Yes' : 'No' ) );
				}
			}
			
			return self::$instance;
		}
		
		/**
		 * Is the current server the same as the licensing server
		 * @return bool
		 */
		public static function is_local_server() {
			return defined( 'E20R_LICENSE_SERVER_URL' ) &&
			       strpos( home_url(), E20R_LICENSE_SERVER_URL ) === 0;
		}
	}
}
