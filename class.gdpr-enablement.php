<?php
/**
 * Copyright (c) 2018 - Eighty / 20 Results by Wicked Strong Chicks.
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

namespace E20R\Utilities;


class GDPR_Enablement {
	
	private static $instance = null;
	
	/**
	 * GDPR_Enablement constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * The current instance of the Utilities class
	 *
	 * @return GDPR_Enablement|null
	 */
	public static function get_instance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	public static function add_consent_optin() {
	
	
	}
	
	/**
	 * Build the Eighty/20 Results by Wicked Strong Chicks, LLC data privacy policy
	 *
	 * @return mixed
	 */
	public function e20r_data_privacy_policy() {
		
		if ( false === $this->wp_has_gdpr_support() ) {
			return false;
		}
		
		$privacy_policy = file_get_contents( plugin_dir_path( __FILE__ ) . 'policies/e20r-privacy-policy.html' );
		$data_list      = '';
		$plugin_list    = '';
		$why_text       = '';
		
		/**
		 * @filter e20r-utilities-collected-data-labels Identifying data collected by a plugin/add-on
		 */
		$collected_data = apply_filters( 'e20r-utilities-collected-data-labels', array() );
		
		/**
		 * @filter e20r-utilities-collected-data-plugins Plugin name collecting/saving data above/beyond what WordPress collects
		 */
		$plugins = apply_filters( 'e20r-utilities-collected-data-plugins', array() );
		
		/**
		 * @filter e20r-utilities-why-plugins-collected-data - Plain language explanation of what the collected data will be used for.
		 */
		$why_paragraphs = apply_filters( 'e20r-utilities-why-plugins-collected-data', array() );
		
		/**
		 * @filter e20r-utilities-collected-data-3rdparty-platform-text 3rd party site name(s) wrapped in a href/link to the 3rd party site where data is being transmitted
		 */
		$caveats = apply_filters( 'e20r-utilities-collected-data-3rdparty-platform-text', array() );
		
		if ( empty( $collected_data ) ) {
			$collected_data = array( 'default' => array( __( 'No extra data collected', Utilities::$plugin_slug ) ) );
		}
		
		// Process the list of data being collected for the user/member
		foreach ( $collected_data as $plugin_slug => $data_label ) {
			$data_list .= sprintf( '<li>%s</li>', esc_attr( $data_label ) );
		}
		
		if ( empty( $plugins ) ) {
			$plugins = array( 'default' => array( __( 'No plugin developed by Eighty/20 Results by Wicked Strong Chicks was found', Utilities::$plugin_slug ) ) );
		}
		
		// Process the list of plugins installed/active
		foreach ( $plugins as $plugin_slug => $plugin_names ) {
			
			foreach( $plugin_names as $plugin_name ) {
				
				$plugin_list .= sprintf( '<li>%s</li>', esc_attr( $plugin_name ) );
			}
		}
		
		// Add links/names of 3rd party sites where data may be transmitted
		if ( empty( $caveats ) ) {
			$caveat_text = null;
		} else {
			
			$caveat_text = sprintf( '<h3>%s</h3><p>%s</p>',
				__( "3rd party services", Utilities::$plugin_slug ),
				__( 'The following 3rd party sites may receive information about the user/member as part of the registration process:' )
			);
			
			$caveat_text .= '<ul>';
			foreach ( $caveats as $plugin_slug => $thirdparty_sites ) {
				
				foreach( $thirdparty_sites as $thirdparty_site ) {
					$caveat_text .= sprintf( '<li>%s</li>', $thirdparty_site );
				}
			}
			
			$caveat_text .= '</ul>';
		}
		
		if ( empty( $why_paragraphs ) ) {
			$why_text = null;
		} else {
			
			foreach ( $why_paragraphs as $plugin_slug => $paragraphs ) {
				foreach( $paragraphs as $paragraph ) {
					$why_text .= sprintf( '<p>%s</p>', $paragraph );
				}
			}
		}
		
		// Add the list of data collected to the policy content
		$privacy_policy = str_replace( '!!collected_data_list!!', $data_list, $privacy_policy );
		$privacy_policy = str_replace( '!!plugin_list!!', $plugin_list, $privacy_policy );
		$privacy_policy = str_replace( '!!caveat_text!!', $caveat_text, $privacy_policy );
		$privacy_policy = str_replace( '!!why_collected!!', $why_text, $privacy_policy );
		
		wp_add_privacy_policy_content( __( 'Eighty / 20 Results by Wicked Strong Chicks, LLC', Utilities::$plugin_slug ), $privacy_policy );
	}
	
	/**
	 * 'Right to be erased' GDPR handler for (all) E20R Plugins
	 *
	 * @param string $email_address Email address of the person to be erased
	 * @param int    $page          For batch processing
	 *
	 * @return array
	 */
	public function erase_personal_data( $email_address, $page = 1 ) {
		
		$default_status = array(
			'num_items_removed'  => 0,
			'num_items_retained' => 0,
			'messages'           => array(),
			'done'               => false,
		);
		
		$erase_status = apply_filters( 'e20r-utilities-erase-personal-data', $default_status, $email_address, $page );
		
		return $erase_status;
	}
	
	/**
	 * 'Right to see my data' GDPR export handler/trigger for (all) E20R Plugins
	 *
	 * @param string $email_address Email address of the person to be erased
	 * @param int    $page          For batch processing
	 *
	 * @return array
	 */
	public function export_personal_data( $email_address, $page = 1 ) {
		
		$user           = get_user_by( 'email', $email_address );
		$data_to_export = apply_filters( 'e20r-utilities-export-personal-data', array(), $user, $page );
		
		return $data_to_export;
	}
	
	/**
	 * Action hook for the WordPress GDPR data exporter functionality
	 *
	 * @param array $exporters
	 *
	 * @return array
	 */
	public function personal_data_exporters( $exporters ) {
		
		$exporters[] = array(
			'exporter_friendly_name' => __( 'E20R Plugin Data', Utilities::$plugin_slug ),
			'callback'               => array( $this, 'export_personal_data' ),
		);
		
		return $exporters;
	}
	
	/**
	 * Action hook for the WordPress GDPR data eraser functionality
	 *
	 * @param array $erasers
	 *
	 * @return array
	 */
	public function personal_data_erasers( $erasers ) {
		
		$erasers[] = array(
			'eraser_friendly_name' => __( 'E20R Plugin Data', Utilities::$plugin_slug ),
			'callback'             => array( $this, 'erase_personal_data' ),
		);
		
		return $erasers;
	}
	
	/**
	 * Load GDPR enablement for the E20R Plugin(s)
	 */
	public function load_hooks() {
		
		add_action( 'admin_init', array( $this, 'e20r_data_privacy_policy' ), 10 );
		
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'personal_data_exporters' ), 10, 1 );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'personal_data_erasers' ), 10, 1 );
	}
	
	/**
	 * Does the current version of WordPress include GDPR support?
	 *
	 * @return bool
	 */
	private function wp_has_gdpr_support() {
		
		$utils = Utilities::get_instance();
		
		$has_gdpr = function_exists( 'wp_add_privacy_policy_content' );
		
		$utils->log("Site has Data Privacy functionality: " . ( $has_gdpr ? 'Yes' : 'No'));
		return $has_gdpr;
	}
}
