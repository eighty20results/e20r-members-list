<?php
/**
 *  Copyright (c) 2019-2020. - Eighty / 20 Results by Wicked Strong Chicks.
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

use E20R\Utilities\Licensing\Licensing;
use E20R\Utilities\Utilities;

if ( ! class_exists( '\E20R\Utilities\Licensing\License_Page' ) ) {
	class License_Page {

		/**
		 * This class (singleton)
		 *
		 * @var null|License_Page
		 */
		private static $instance = null;

		/**
		 * License_Page constructor.
		 */
		private function __construct() {
		}

		/**
		 * Get or instantiate and get the current class instance
		 * @return License_Page|null
		 */
		public static function get_instance() {

			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
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
			$button_text  = apply_filters( 'e20r-license-save-btn-text', __( 'Activate/Deactivate & Save license(s)', 'e20r-licensing-utility' ) );
			?>
			<p class="e20r-licensing-section"><?php _e(
					"This add-on is distributed under version 2 of the GNU Public License (GPLv2). One of the things the GPLv2 license grants is the right to use this software on your site, free of charge.",
					'e20r-licensing-utility'
				); ?>
			</p>
			<p class="e20r-licensing-section"><?php printf(
					__(
						"To verify and activate the license, add the license key and email address to the appropriate field(s), then click the '%s' button.",
						'e20r-licensing-utility'
					),
					$button_text
				); ?>
			</p>
			<p class="e20r-licensing-section"><?php printf(
					__( "To deactivate the license and clear the license settings from this system, check the check-box in the \"Deactivate\" column and then click the '%s)' button.", 'e20r-licensing-utility' ),
					$button_text
				); ?>
			</p>
			<p class="e20r-licensing-section">
				<a href="<?php echo esc_url_raw( $pricing_page ); ?>"
				   target="_blank"><?php _e( "Purchase Licenses/Add-ons &raquo;", 'e20r-licensing-utility' ); ?></a>
			</p>
			<div class="form-table">
				<div class="e20r-license-settings-row">
					<div class="e20r-license-settings-column e20r-license-settings-header e20r-license-name-column">
						<?php _e( "Name", 'e20r-licensing-utility' ); ?>
					</div>
					<div class="e20r-license-settings-column e20r-license-settings-header e20r-license-key-column">
						<?php _e( "Key", 'e20r-licensing-utility' ); ?>
					</div>
					<div class="e20r-license-settings-column e20r-license-settings-header e20r-license-email-column">
						<?php _e( "Email", 'e20r-licensing-utility' ); ?>
					</div>
					<div
						class="e20r-license-settings-column e20r-license-settings-header e20r-license-deactivate-column">
						<?php _e( "Deactivate", 'e20r-licensing-utility' ); ?>
					</div>
				</div>
			</div>
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

			$is_active       = isset( $args['is_active'] ) && 1 === (int) $args['is_active'];
			$product_sku     = isset( $args['product_sku'] ) ? $args['product_sku'] : '';
			$status_color    = $is_active ? 'e20r-license-active' : 'e20r-license-inactive';
			$product         = __( 'Unknown', 'e20r-licensing-utility' );
			$var_name        = "{$args['option_name']}[product][0]";
			$is_subscription = ( isset( $args['has_subscription'] ) && ! empty( $args['has_subscription'] ) && 1 == $args['has_subscription'] );

			if ( isset( $args['product'] ) ) {

				$product  = $args['product'];
				$var_name = "{$args['option_name']}[product][{$args['index']}]";

			} else if ( isset( $args['new_product'] ) ) {

				$product             = $args['new_product'];
				$var_name            = "{$args['option_name']}[new_product][{$args['index']}]";
				$args['email_value'] = $current_user->user_email;
			} ?>
			<div class="e20r-license-data-row">
				<div class="e20r-license-settings-column e20r-license-key-column"><?php
					printf( '<input type="hidden" name="%1$s" value="%2$s" />', "{$args['option_name']}[fieldname][{$args['index']}]", $args['value'] );
					printf( '<input type="hidden" name="%1$s" value="%2$s" />', "{$args['option_name']}[fulltext_name][{$args['index']}]", $args['fulltext_name'] );
					printf( '<input type="hidden" name="%1$s" value="%2$s" />', $var_name, $product );
					printf( '<input type="hidden" name="%1$s" value="%2$s" />', "{$args['option_name']}[product_sku][{$args['index']}]", $product_sku );
					printf(
						'<input name="%1$s[%2$s][%3$d]" type="%4$s" id="%5$s" value="%6$s" placeholder="%7$s" class="regular_text %8$s" />',
						$args['option_name'],
						$args['name'],
						$args['index'],
						$args['input_type'],
						$args['label_for'],
						$args['value'],
						$args['placeholder'],
						$status_color
					); ?>
				</div>
				<div class="e20r-license-settings-column e20r-license-email-column">
					<?php
					printf(
						'<input name="%1$s[%2$s][%3$d]" type="email" id=%4$s_email value="%5$s" placeholder="%6$s" class="email_address %7$s" />',
						$args['option_name'],
						$args['email_field'],
						$args['index'],
						$args['label_for'],
						$args['email_value'],
						__( "Email used to buy license", "e20rlicense" ),
						$status_color
					); ?>
				</div>
				<div class="e20r-license-settings-column e20r-license-deactivate-column">
					<?php if ( $args['name'] != 'new_key' ) { ?>
						<?php
						printf(
							'<input type="checkbox" name="%1$s[delete][%2$d]" class="clear_license" value="%3$s" />',
							$args['option_name'],
							$args['index'],
							$args['value']
						);
					} ?>
				</div>
				<div class="e20r-license-settings-column e20r-license-check-license-column">
					<?php if ( $args['name'] != 'new_key' ) { ?>
						<?php
						printf(
							'<input type="button" name="%1$s[check_license][%2$d]" class="e20r-check-license button button-secondary" value="%3$s" />',
							$args['option_name'],
							$args['index'],
							__( 'Verify license', 'e20r-licensing' )
						);
					} ?>
				</div>
				<div class="e20r-license-settings-row">
					<p class="e20r-license-settings-status">
						<?php

						$has_expiration  = ( ! $is_subscription && isset( $args['expiration_ts'] ) && ! empty( $args['expiration_ts'] ) );
						$expiration_date = '';

						if ( $is_active ) {
							$expiration_message = __( 'This license does not expire', 'e20r-licensing-utility' );
						} else {
							$expiration_message = __( 'This license is not activated', 'e20r-licensing-utility' );
						}

						if ( $has_expiration || $is_subscription ) {
							$expiration_date = __(
								sprintf(
									'on or before: %s',
									date_i18n(
										get_option( 'date_format' ),
										$args['expiration_ts']
									)
								),
								'e20r-licensing-utility'
							);
						}

						$body_msg = $is_subscription && ! $has_expiration ?
							__( 'will renew automatically (unless cancelled)', 'e20r-licensing-utility' ) :
							__( 'needs to be renewed manually', 'e20r-licensing-utility' );

						if ( ! $is_subscription && ! $has_expiration ) {
							$body_msg = __( 'does not need to be renewed', 'e20r-licensing-utility' );
						}

						if ( $is_subscription || ( ! $is_subscription && $has_expiration ) ) {
							$expiration_message = sprintf(
								__( 'This license %1$s %2$s', 'e20r-licensing-utility' ),
								$body_msg,
								$expiration_date
							);
						}
						$utils->log( "License expiration info: {$expiration_message}" );
						printf( $expiration_message );
						?>
					</p>
				</div>
			</div>
			<?php
		}

		/**
		 * The page content for the E20R Licensing section
		 *
		 * @since 1.6.1 - BUG FIX: Would sometimes show the wrong license status on the licensing page
		 */
		public function licensing_page() {

			$utils = Utilities::get_instance();

			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Testing access for Licensing page" );
			}

			if ( ! function_exists( "current_user_can" ) ||
			     ( ! current_user_can( "manage_options" ) &&
			       ! current_user_can( "e20r_license_admin" ) )
			) {
				wp_die( __( "You are not permitted to perform this action.", 'e20r-licensing-utility' ) );
			}

			$utils       = Utilities::get_instance();
			$button_text = apply_filters( 'e20r-license-save-btn-text', __( 'Activate/Deactivate & Save license(s)', 'e20r-licensing-utility' ) );
			?>
			<?php $utils->display_messages(); ?>
			<br/>
			<h2><?php echo $GLOBALS['title']; ?></h2>
			<form action="options.php" method="POST">
				<?php
				settings_fields( "e20r_license_settings" );
				do_settings_sections( 'e20r-licensing' );
				?>
			</>
			<?php
			submit_button( $button_text );
			?>
			</form>
			<?php

			$settings            = apply_filters( 'e20r-license-add-new-licenses', License_Settings::get_settings(), array() );
			$support_account_url = apply_filters( 'e20r-license-support-account-url', sprintf(
					'%1$s?redirect_to=%2$s',
					E20R_LICENSE_SERVER_URL . "/wp-login.php",
					E20R_LICENSE_SERVER_URL . '/account/'
				)
			);

			if ( E20R_LICENSING_DEBUG ) {
				$utils->log( "Have " . count( $settings ) . " new license(s) to add info for" );
			}

			foreach ( $settings as $product_sku => $license ) {

				if ( in_array( $product_sku, array(
					'e20r_default_license',
					'new_licenses',
					'example_gateway_addon',
				) ) ) {
					continue;
				}
				$utils->log( "Checking license status for {$product_sku}" );

				$license_valid = Licensing::is_licensed( $product_sku, false ) && ( isset( $license['status'] ) && 'active' === $license['status'] );
				?>

				<div class="wrap"><?php
					$license_expired = false;
					if ( isset( $license['expires'] ) ) {
						$utils->log( "Have old licensing config, so..." );
						$license_expired =
							! empty( $license['expires'] ) && $license['expires'] <= current_time( 'timestamp' );
					}

					if ( isset( $license['expire'] ) ) {
						$utils->log( "Have new licensing config, so..." );
						$license_expired =
							! empty( $license['expire'] ) && $license['expire'] <= current_time( 'timestamp' );
					}

					if ( false === $license_valid && true === $license_expired ) {
						?>
						<div class="notice notice-error inline">
						<p>
							<strong><?php printf(
									__( 'Your <em>%s</em> license is either not configured, invalid or has expired.', 'e20r-licensing-utility' ), $license['fulltext_name'] ); ?></strong>
							<?php printf( __( 'Visit your Eighty / 20 Results <a href="%s" target="_blank">Support Account</a> page to confirm that your account is active and to locate your license key.', 'e20r-licensing-utility' ), $support_account_url ); ?>
						</p>
						</div><?php
					}

					if ( $license_valid ) {
						?>
						<div class="notice notice-info inline">
						<p>
							<strong><?php _e( 'Thank you!', 'e20r-licensing-utility' ); ?></strong>
							<?php printf( __( "A valid %s license key is being used on this site.", 'e20r-licensing-utility' ), $license['fulltext_name'] ); ?>
						</p>
						</div><?php

					} ?>
				</div> <!-- end wrap -->
				<?php
			}

		}
	}
}
