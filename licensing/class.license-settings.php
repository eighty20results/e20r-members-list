<?php
/**
 *  Copyright (c) 2017-2019. - Eighty / 20 Results by Wicked Strong Chicks.
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

namespace E20R\Utilities\Licensing;


use E20R\Utilities\Utilities;

if ( ! class_exists( '\E20R\Utilities\Licensing\License_Settings' ) ) {
	class License_Settings {

		/**
		 * Current instance of the License_Settings class
		 *
		 * @var License_Settings|null
		 */
		private static $instance = null;

		/**
		 * License_Settings constructor.
		 */
		private function __construct() {

			$utils = Utilities::get_instance();

			if ( ! defined( 'E20R_LICENSE_SERVER_URL' ) ||
			     ( defined( 'E20R_LICENSE_SERVER_URL' ) && ! E20R_LICENSE_SERVER_URL )
			) {
				$utils->log( "Error: Haven't added the 'E20R_LICENSE_SERVER_URL' constant to the wp-config file!" );
				$utils->add_message(
					__(
						'Error: The E20R_LICENSE_SERVER_URL definition is missing! Please configure it in the wp-config.php file.',
						Utilities::$plugin_slug
					),
					'error',
					'backend'
				);

				return null;
			}
		}

		/**
		 * Register all Licensing settings
		 *
		 * @since 1.5 - BUG FIX: Incorrect namespace used in register_setting(), add_sttings_section() and
		 *        add_settings_field() functions
		 * @since 1.6 - BUG FIX: Used wrong label for new licenses
		 */
		static public function register_settings() {

			$utils        = Utilities::get_instance();
			$license_list = array();

			register_setting(
				"e20r_license_settings", // group, used for settings_fields()
				"e20r_license_settings",  // option name, used as key in database
				'E20R\Utilities\Licensing\License_Settings::validate_settings'     // validation callback
			);

			add_settings_section(
				'e20r_licensing_section',
				__( "Configure Licenses", 'e20r-licensing-utility' ),
				'E20R\Utilities\Licensing\License_Page::show_licensing_section',
				'e20r-licensing'
			);

			$settings        = apply_filters( 'e20r-license-add-new-licenses', self::get_settings(), array() );
			$license_counter = 0;

			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Found " . count( $settings ) . " potential licenses" );
			}

			foreach ( $settings as $product_sku => $license ) {

				$is_licensed = false;
				$is_active   = false;

				// Skip and clean up.
				if ( isset( $license['key'] ) && empty( $license['key'] ) ) {

					unset( $settings[ $product_sku ] );
					update_option( 'e20r_license_settings', $settings, 'yes' );

					if ( E20R_LICENSING_DEBUG ) {
						$utils->log( "Skipping {$product_sku} with settings (doesn't have a product SKU): " . print_r( $license, true ) );
					}
					continue;
				}

				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Loading settings fields for '{$product_sku}'?" );
				}

				if ( ! in_array( $product_sku, array(
						'example_gateway_addon',
						'new_licenses',
					) ) && isset( $license['key'] ) && $license['key'] != 'e20r_default_license' && ! empty( $license['key'] ) ) {

					if ( E20R_LICENSING_DEBUG ) {
						$utils->log( "Previously activated license: {$product_sku}: adding {$license['fulltext_name']} fields" );
						$utils->log( "Existing settings for {$product_sku}: " . print_r( $license, true ) );
					}

					if ( empty( $license['status'] ) ) {
						$license['status'] = 'inactive';
					}

					if ( 'active' === $license['status'] ) {
						$is_licensed = true;
						$key         = ( ! Licensing::is_new_version() && isset( $license['license_key'] ) ?
							$license['license_key'] :
							isset( $license['product_sku'] ) ? $license['product_sku'] : null

						);

						$is_active = Licensing::is_active(
							$key,
							$license,
							$is_licensed
						);
						$utils->log( "The {$key} license is " . ( $is_active ? 'Active' : 'Inactive' ) );
					}

					$status_class = 'e20r-license-inactive';

					if ( 'active' == $license['status'] ) {
						$status_class = 'e20r-license-active';
					}

					$license_name = sprintf( '<span class="%2$s">%1$s (%3$s)</span>',
						$license['fulltext_name'],
						$status_class,
						ucfirst( $license['status'] )
					);

					$expiration_ts = 0;

					if ( Licensing::is_new_version() ) {

						if ( ! empty( $license['expire'] ) ) {
							$expiration_ts = (int) $license['expire'];
						} else {
							$utils->add_message(
								sprintf(
									__(
										'Error: No expiration info found for %s. Using default value (expired)',
										Utilities::$plugin_slug
									),
									$license_name
								),
								'warning',
								'backend'
							);
						}
					} else {
						if ( ! empty( $license['expires'] ) ) {
							$expiration_ts = (int) strtotime( $license['expires'] );
						} else {
							$utils->add_message(
								sprintf(
									__(
										'Warning: No expiration info found for %s. Using default value (expired)',
										Utilities::$plugin_slug
									),
									$license_name
								),
								'warning',
								'backend'
							);
						}
					}


					add_settings_field(
						"{$license['key']}",
						$license_name,
						'E20R\Utilities\Licensing\License_Page::show_input',
						'e20r-licensing',
						'e20r_licensing_section',
						array(
							'index'            => $license_counter,
							'label_for'        => isset( $license['key'] ) ?
								$license['key'] :
								__( 'Unknown', 'e20r-licensing-utility' ),
							'product'          => $product_sku,
							'option_name'      => "e20r_license_settings",
							'fulltext_name'    => isset( $license['fulltext_name'] ) ?
								$license['fulltext_name'] :
								__( 'Unknown', 'e20r-licensing-utility' ),
							'name'             => 'license_key',
							'input_type'       => 'password',
							'is_active'        => $is_active,
							'expiration_ts'    => $expiration_ts,
							'has_subscription' => ( isset( $license['subscription_status'] ) && 'active' === $license['subscription_status'] ),
							'value'            => Licensing::is_new_version() && isset( $license['the_key'] ) ?
								$license['the_key'] :
								isset( $license['key'] ) ? $license['key'] : null,
							'email_field'      => "license_email",
							'product_sku'      => Licensing::is_new_version() && isset( $license['product_sku'] ) ?
								$license['product_sku'] :
								null,
							'email_value'      => isset( $license['email'] ) ? $license['email'] : null,
							'placeholder'      => __( "Paste the purchased key here", 'e20r-licensing-utility' ),
						)
					);

					$license_list[] = $product_sku;
					$license_counter ++;
				}
			}


			$new_licenses = isset( $settings['new_licenses'] ) ? $settings['new_licenses'] : array();

			foreach ( $new_licenses as $new_product_sku => $new ) {

				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Processing new license fields for new sku: {$new['new_product']}" );
				}

				// Skip if we've got this one in the list of licenses already.

				if ( ! in_array( $new['new_product'], $license_list ) && $new_product_sku !== 'example_gateway_addon' ) {
					if ( E20R_LICENSING_DEBUG ) {
						$utils->log( "Adding  license fields for new sku {$new['new_product']} (one of " . count( $new_licenses ) . " unlicensed add-ons)" );
					}

					add_settings_field(
						"e20r_license_new_{$new_product_sku}",
						sprintf( __( "Add %s license", 'e20r-licensing-utility' ), $new['fulltext_name'] ),
						'E20R\Utilities\Licensing\License_Page::show_input',
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
							'is_active'     => $is_active,
							'email_field'   => "new_email",
							'product_sku'   => $new['product_sku'],
							'email_value'   => null,
							'placeholder'   => $new['placeholder'],
							'classes'       => sprintf( 'e20r-licensing-new-column e20r-licensing-column-%1$d', $license_counter ),
						)
					);

					$license_counter ++;
					if ( E20R_LICENSING_DEBUG ) {
						$utils->log( "New license field(s) added for sku: {$new_product_sku}" );
					}
				}
			}

		}

		/**
		 * Load local settings for the specified product
		 *
		 * @param string $product_sku
		 *
		 * @return array
		 */
		public static function get_settings( $product_sku = null ) {

			$utils = Utilities::get_instance();

			if ( is_null( $product_sku ) ) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "No product key provided. Using default key (e20r_default_license)!" );
				}
				$product_sku = 'e20r_default_license';
			}

			// $product_sku  = strtolower( $product_sku );
			$defaults = self::default_settings( $product_sku );
			$settings = get_option( 'e20r_license_settings', $defaults );

			if ( empty( $settings ) ) {
				$settings = $defaults;
			}

			if ( 'e20r_default_license' == $product_sku || empty( $product_sku ) ) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "No product, or default product specified, so returning all settings: {$product_sku}" );
				}

				return $settings;
			}

			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Requested and returning settings for {$product_sku}" );
			}

			return isset( $settings[ $product_sku ] ) ? $settings[ $product_sku ] : null;
		}

		/**
		 * Settings array for the License(s) on this system
		 *
		 * @param string $product_sku
		 *
		 * @return array
		 */
		public static function default_settings( $product_sku = 'e20r_default_license' ) {

			if ( ! Licensing::is_new_version() ) {
				$defaults = array(
					$product_sku => array(
						'key'           => null,
						'renewed'       => null,
						'domain'        => $_SERVER['SERVER_NAME'],
						'product'       => $product_sku,
						'fulltext_name' => '',
						'expires'       => '',
						'status'        => '',
						'first_name'    => '',
						'last_name'     => '',
						'email'         => '',
						'timestamp'     => current_time( 'timestamp' ),
					),
				);
			} else {
				$defaults = array(
					$product_sku => array(
						"expire"           => 0, // Timestamp
						"activation_id"    => null,
						"expire_date"      => '',
						"timezone"         => "UTC",
						"the_key"          => "",
						"product_sku"      => $product_sku,
						"url"              => "",
						"has_expired"      => true,
						"status"           => "cancelled",
						"allow_offline"    => false,
						"offline_interval" => "days",
						"offline_value"    => 0,
						'fulltext_name'    => null,
					),
				);
			}

			return $defaults;
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

			/*
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Validation input (Add License on Purchase): " . print_r( $input, true ) );
			}
			*/
			$license_settings = self::get_settings();

			// Save new license keys & activate the license
			if ( isset( $input['new_product'] ) && true === $utils->array_isnt_empty( $input['new_product'] ) ) {

				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "New product? " . print_r( $input['new_product'], true ) );
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

						$license_settings[ $product ]['first_name']    = isset( $current_user->first_name ) ? $current_user->first_name : null;
						$license_settings[ $product ]['last_name']     = isset( $current_user->last_name ) ? $current_user->last_name : null;
						$license_settings[ $product ]['fulltext_name'] = $input['fulltext_name'][ $nk ];
						$license_settings[ $product ]['product_sku']   = $input['product_sku'][ $nk ];

						if ( ! empty( $license_email ) && ! empty( $license_key ) ) {

							if ( E20R_LICENSING_DEBUG ) {
								$utils->log( "Have a license key and email, so activate the new license" );
							}

							$license_settings[ $product ]['email'] = $license_email;
							$license_settings[ $product ]['key']   = $license_key;

							if ( E20R_LICENSING_DEBUG ) {
								$utils->log( "Attempting remote activation for {$product} " );
							}

							$result = Licensing::activate_license( $product, $license_settings[ $product ] );

							if ( E20R_LICENSING_DEBUG ) {
								$utils->log( "Status from activation {$result['status']} vs " . Licensing::E20R_LICENSE_DOMAIN_ACTIVE . " => " . print_r( $result, true ) );
							}

							if ( Licensing::E20R_LICENSE_DOMAIN_ACTIVE === intval( $result['status'] ) ) {

								if ( E20R_LICENSING_DEBUG ) {
									$utils->log( "This license & server combination is already active on the licensing server" );
								}

								if ( true === Licensing::deactivate_license( $product, $result['settings'] ) ) {

									if ( E20R_LICENSING_DEBUG ) {
										$utils->log( "Was able to deactivate this license/host combination" );
									}

									$result = Licensing::activate_license( $product, $license_settings[ $product ] );

								}
							}

							if ( E20R_LICENSING_DEBUG ) {
								$utils->log( "Loading updated settings from server" );
							}

							if ( true === License_Server::get_license_status_from_server( $product, $license_settings[ $product ], true ) ) {
								$result['settings'] = self::merge_settings( $product, $license_settings[ $product ] );
							}

							if ( isset( $result['settings']['status'] ) && $result['settings']['status'] !== 'active' ) {
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
							$utils->log( "No new license key specified for {$product}, nothing to save" );
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

					$result = Licensing::deactivate_license( $product );

					if ( false !== $result ) {

						$utils->log( "Successfully deactivated {$input['product'][ $lk ]} on remote server" );

						unset( $input['license_key'][ $lk ] );
						unset( $input['license_email'][ $lk ] );
						unset( $input['fieldname'][ $lk ] );
						unset( $input['fulltext_name'][ $lk ] );
						unset( $license_settings[ $product ] );
						unset( $input['product'][ $lk ] );
						unset( $input['product_sku'][ $lk ] );
					}
				}

				// Save cleared license updates
				if ( false === self::update_settings( null, $license_settings ) ) {
					$utils->log( "Unable to save the settings!" );
				}
			}

			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Returning validated settings: " . print_r( $license_settings, true ) );
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
		 * Merge existing (or default) settings for the product with the new settings
		 *
		 * @param string $product_sku
		 * @param array  $new_settings
		 *
		 * @return array
		 */
		public static function merge_settings( $product_sku, $new_settings ) {

			$utils        = Utilities::get_instance();
			$old_settings = self::get_settings( $product_sku );

			/*
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Previously saved settings for {$product_sku}: " . print_r( $old_settings, true ) );
				$utils->log( "New - requested - settings: " . print_r( $new_settings, true ) );
			}
			*/

			if ( empty( $old_settings ) ) {
				$old_settings = self::default_settings();
			}

			foreach ( $new_settings as $key => $value ) {
				$old_settings[ $key ] = $value;
			}

			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Updated settings (after merge...) " . print_r( $old_settings, true ) );
			}

			return $old_settings;
		}

		/**
		 * Save the license settings
		 *
		 * @param string $product_sku
		 * @param array  $new_settings
		 *
		 * @return bool|array
		 */
		public static function update_settings( $product_sku = null, $new_settings ) {

			$utils            = Utilities::get_instance();
			$license_settings = self::get_settings();

			/*
			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Settings before update: " . print_r( $license_settings, true ) );
				$utils->log( "NEW settings for {$product_sku}: " . print_r( $new_settings, true ) );
			}
			*/

			// Make sure the new settings make sense
			if ( is_array( $license_settings ) && in_array( 'fieldname', array_keys( $license_settings ) ) ) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Unexpected settings layout while processing {$product_sku}!" );
				}
				$license_settings                 = self::default_settings();
				$license_settings[ $product_sku ] = $new_settings;
			}

			// Need to update the settings for a (possibly) pre-existing product
			if ( ! is_null( $product_sku ) && ! empty( $new_settings ) && ! in_array( $product_sku, array(
					'e20r_default_license',
					'example_gateway_addon',
				) ) && ! empty( $product_sku )
			) {

				$license_settings[ $product_sku ] = $new_settings;
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Updating license settings for {$product_sku}" );
				}

			} else if ( ! is_null( $product_sku ) && empty( $new_settings ) && ( ! in_array( $product_sku, array(
						'e20r_default_license',
						'example_gateway_addon',
					) ) && ! empty( $product_sku ) )
			) {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Removing license settings for {$product_sku}" );
				}
				unset( $license_settings[ $product_sku ] );

			} else {
				if ( E20R_LICENSING_DEBUG ) {
					$utils->log( "Requested save of everything" );
				}
			}

			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Saving: " . print_r( $license_settings, true ) );
			}

			update_option( 'e20r_license_settings', $license_settings, 'yes' );

			return $license_settings;
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
					$utils->log( "No menu object found for {$handle}??" );
				}

				return false;
			}

			$item = isset( $check_menu['options-general.php'] ) ? $check_menu['options-general.php'] : array();

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
		 * Get or instantiate and get the Licensing class instance
		 *
		 * @return License_Settings|null
		 */
		public static function get_instance() {

			if ( null === self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
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
				__( "E20R Licenses", 'e20r-licensing-utility' ),
				__( "E20R Licenses", 'e20r-licensing-utility' ),
				'manage_options',
				'e20r-licensing',
				array( License_Page::get_instance(), 'licensing_page' )
			);
		}
	}
}
