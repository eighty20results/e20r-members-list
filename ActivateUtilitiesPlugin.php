<?php
/**
 * Copyright 2021 - 2022 - Thomas Sjolshagen (https://eighty20results.com/thomas-sjolshagen)
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
 * @package E20R\Utilities\ActivateUtilitiesPlugin
 */

namespace E20R\Utilities;

use WP_Error;
use function add_action;
use function is_wp_error;

if ( ! defined( 'ABSPATH' ) && ! defined( 'PLUGIN_PHPUNIT' ) ) {
	die( 'WordPress not loaded. Naughty, naughty!' );
}

if ( ! class_exists( 'E20R\Utilities\ActivateUtilitiesPlugin' ) ) {
	/**
	 * Class ActivateUtilitiesPlugin
	 */
	class ActivateUtilitiesPlugin {

		/**
		 * Name of plugin to attempt to activate
		 *
		 * @var string
		 */
		private static $plugin_name = 'E20R Utilities Module';

		/**
		 * Path to loader file for the plugin we're attempting to activate
		 *
		 * @var string
		 */
		private static $plugin_slug = '00-e20r-utilities/class-loader.php';

		/**
		 * Is the utilities plugin active?
		 *
		 * @param string $plugin The path to the plugin we're trying to activate (00-e20r-utilities/class-loader.php).
		 *
		 * @return bool
		 */
		private static function is_active( $plugin ) {

			if ( empty( $plugin ) ) {
				$plugin = self::$plugin_slug;
			}

			$plugin      = trim( $plugin );
			$plugin_list = get_option( 'active_plugins', array() );
			return in_array( $plugin, $plugin_list, true );
		}

		/**
		 * Activate the plugin (manually)
		 *
		 * @param string $plugin The plugin activation path.
		 * @param string $redirect The redirect location (if applicable).
		 * @param bool   $network_wide Is this a WordPress Network Plugin activation.
		 *
		 * @returns null|WP_Error
		 */
		private static function activate_plugin( $plugin = null, $redirect = '', $network_wide = false ) {

			$plugin = plugin_basename( trim( $plugin ) );

			if ( self::is_active( $plugin ) ) {
				return true;
			}

			if ( is_multisite() && ( $network_wide || is_network_only_plugin( $plugin ) ) ) {
					$network_wide = true;
					$current      = get_site_option( 'active_sitewide_plugins', array() );
			} else {
					$current = get_option( 'active_plugins', array() );
			}

			if ( ! in_array( $plugin, $current, true ) ) {
				if ( ! empty( $redirect ) ) {
					wp_safe_redirect(
						add_query_arg(
							'_error_nonce',
							wp_create_nonce( 'plugin-activation-error_' . $plugin ),
							$redirect
						)
					); // we'll override this later if the plugin can be included without fatal error.
				}

				ob_start();
				include_once plugin_dir_path( __DIR__ ) . "/{$plugin}";
				do_action( 'activate_plugin', trim( $plugin ) );

				if ( $network_wide ) {
					$current[ $plugin ] = time();
					update_site_option( 'active_sitewide_plugins', $current );
				} else {
					$current[] = $plugin;
					sort( $current );
					update_option( 'active_plugins', $current );
				}

				do_action( 'activate_' . trim( $plugin ) );
				do_action( 'activated_plugin', trim( $plugin ) );

				if ( ob_get_length() > 0 ) {
						$output = ob_get_clean();
						return new WP_Error(
							'unexpected_output',
							__( 'The plugin generated unexpected output.' ),
							$output
						);
				}
					ob_end_clean();
			}

			return self::is_active( $plugin );
		}

		/**
		 * Error message to show when the E20R Utilities Module plugin is not installed and active
		 *
		 * @param string $dependent_plugin_name The plugin we're dependent on (shown in error message).
		 */
		public static function plugin_not_installed( $dependent_plugin_name ) {

			printf(
				'<div class="notice notice-error"><p>%1$s</p></div>',
				sprintf(
					'Please download and install the <strong>%1$s</strong> plugin. It is required for the %2$s plugin to function.',
					sprintf(
						'<a href="%1$s">%2$s</a>',
						'https://eighty20results.com/product/e20r-utilities-module-for-other-plugins/',
						self::$plugin_name // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					),
					$dependent_plugin_name // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				)
			);
		}

		/**
		 * Attempt to activate the E20R Utilities Module plugin when the dependent plugin is activated
		 *
		 * @param string|null $path The path to the plugin.
		 *
		 * @return bool
		 */
		public static function attempt_activation( $path = null ) {

			if ( empty( $path ) ) {
				$path = trailingslashit( plugin_dir_path( __DIR__ ) ) . self::$plugin_slug;
			}

			$path = trim( $path );

			if ( ! file_exists( $path ) ) {
				add_action(
					'admin_notices',
					function() use ( $path ) {
						printf(
							'<div class="notice notice-error"><p>%1$s</p></div>',
							sprintf(
								'The <strong>%1$s</strong> plugin was not found at %2$s. Please <a href="%3$s" target="_blank">download and install it</a>!',
								self::$plugin_name, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								$path, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,
								'https://eighty20results.com/product/e20r-utilities-module-for-other-plugins/'
							)
						);
					}
				);
				return false;
			}

			if ( ! self::is_active( self::$plugin_slug ) ) {

				$result = self::activate_plugin( self::$plugin_slug );

				if ( ! is_wp_error( $result ) ) {
					add_action(
						'admin_notices',
						function () {
							printf(
								'<div class="notice notice-success"><p>%s</p></div>',
								sprintf(
									'The <strong>%s</strong> plugin is required & was auto-activated.',
									self::$plugin_name // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								)
							);
						}
					);

					return true;
				} else {
					add_action(
						'admin_notices',
						function () {
							printf(
								'<div class="notice notice-error"><p>%s</p></div>',
								sprintf(
									'The <strong>%s</strong> plugin can\'t be auto-activated. Please install it!',
									self::$plugin_name // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								)
							);
						}
					);
					return false;
				}
			}

			if ( self::is_active( self::$plugin_slug ) ) {
				return true;
			}

			return false;
		}
	}
}

if ( function_exists( '\add_action' ) ) {
	add_action( 'admin_init', '\E20R\Utilities\ActivateUtilitiesPlugin::attempt_activation', 9999, 1 );
}
