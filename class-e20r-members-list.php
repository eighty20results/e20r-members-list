<?php
/**
Plugin Name: Better Members List for Paid Memberships Pro
Plugin URI: https://wordpress.org/plugins/e20r-members-list
Description: Extensible, sortable & bulk action capable members listing + export to CSV tool for Paid Memberships Pro.
Version: 8.5
Author: Thomas Sjolshagen @ Eighty / 20 Results by Wicked Strong Chicks, LLC <thomas@eighty20results.com>
Author URI: https://eighty20results.com/thomas-sjolshagen/
Text Domain: e20r-members-list
Domain Path: /languages
License:

	Copyright 2016 - 2022 (c) Eighty / 20 Results by Wicked Strong Chicks, LLC (thomas@eighty20results.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package E20R\Members_List\E20R_Members_List
 */

namespace E20R\Members_List;

use E20R\Exceptions\InvalidSettingsKey;
use E20R\Members_List\Admin\Exceptions\MissingUtilitiesModule;
use E20R\Members_List\Admin\Pages\Members_List_Page;
use E20R\Metrics\Exceptions\InvalidPluginInfo;
use E20R\Metrics\Exceptions\MissingDependencies;
use E20R\Metrics\MixpanelConnector;
use E20R\Utilities\Cache;
use E20R\Utilities\Utilities;
use E20R\Utilities\Message;
use function \add_action;
use function \apply_filters;

if ( ! defined( 'ABSPATH' ) && ! defined( 'PLUGIN_PHPUNIT' ) ) {
	die( 'WordPress not loaded. Naughty, naughty!' );
}

require_once __DIR__ . '/inc/autoload.php';
require_once __DIR__ . '/ActivateUtilitiesPlugin.php';

if ( ! defined( 'E20R_MEMBERSLIST_VER' ) ) {
	define( 'E20R_MEMBERSLIST_VER', '8.5' );
}

if ( ! class_exists( '\\E20R\\Members_List\\E20R_Members_List' ) ) {
	/**
	 * Class E20R_Members_List
	 */
	class E20R_Members_List {

		/**
		 * Instance of the Member List controller
		 *
		 * @var null|E20R_Members_List
		 */
		private static $instance = null;

		/**
		 * The E20R Utilities Module class instance
		 *
		 * @var Utilities|null $utils
		 */
		private $utils = null;


		/**
		 * The Member List Page class instance
		 *
		 * @var Members_List_Page|null $page
		 */
		private $page = null;

		/**
		 * The class managing metrics for Mixpanel
		 *
		 * @var null|MixpanelConnector $metrics
		 */
		private $metrics = null;

		/**
		 * E20R_Members_List constructor.
		 *
		 * @param null|Members_List_Page $ml_page The Members List Page (view) class to use to display the wp-admin page
		 * @param null|Utilities         $utils The E20R Utilities Module class instance
		 * @param null|MixpanelConnector $mixpanel The MixpanelConnector class instance
		 */
		public function __construct( $ml_page = null, $utils = null, $mixpanel = null ) {
			self::$instance = $this;

			if ( empty( $utils ) ) {
				$message = new Message();
				$utils   = new Utilities( $message );
			}

			$this->utils = $utils;

			if ( empty( $ml_page ) ) {
				$ml_page = new Members_List_Page( $this->utils );
			}

			$this->page = $ml_page;

			// Add the usage metrics (Mixpanel) class unless it's supplied
			if ( empty( $mixpanel ) ) {
				$mixpanel = new MixpanelConnector( 'a14f11781866c2117ab6487792e4ebfd', array( 'host' => 'api-eu.mixpanel.com' ), null, $this->utils );
			}

			$this->metrics = $mixpanel;
		}

		/**
		 * Return the content of the property we're processing
		 *
		 * @param string $property The E20R_Members_List() class property to return the value of.
		 *
		 * @return mixed
		 * @throws InvalidSettingsKey Raised when an invalid class property is specified for the get() method
		 */
		public function get( $property = 'instance' ) {

			if ( ! property_exists( $this, $property ) ) {
				throw new InvalidSettingsKey(
					esc_attr__( 'The specified E20R_Members_List() class property does not exist!', 'e20r-members-list' )
				);
			}

			return $this->{$property};
		}
		/**
		 * Get or instantiate and get the current class
		 *
		 * @return E20R_Members_List|null
		 *
		 * @test E20R_Members_ListTest::test_get_instance()
		 */
		public static function get_instance() {

			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Initialize the Enhanced Members List functionality
		 *
		 * @param null|Utilities $utils The E20R Utilities Module class instance
		 *
		 * @since v3.3 - ENHANCEMENT: Load translation/I18N
		 *
		 * @test E20R_Members_ListTest::test_load_hooks()
		 *
		 * @throws MissingUtilitiesModule Raised if the Utilities module is missing/not loaded on the site.
		 */
		public function load_hooks( $utils = null ) {

			if ( ! method_exists( '\\E20R\\Utilities\\Utilities', 'get_instance' ) ) {
				$msg = esc_attr__( 'The E20R Utilities Module is missing/inactive!', 'e20r-members-list' );
				throw new MissingUtilitiesModule( $msg );
			}

			if ( empty( $utils ) ) {
				$message = new Message();
				$utils   = new Utilities( $message );
			}

			$this->utils = $utils;
			$this->utils->log( 'Loading hooks for the E20R_Members_List class' );
			add_action( 'init', array( $this, 'load_text_domain' ), 1 );
			add_action( 'wp_loaded', array( $this->page, 'load_hooks' ), 10 );

			// Set URIs in plugin listing to plugin support
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

			// All sorts of events trigger an attempted cache clearing
			add_action( 'pmpro_after_change_membership_level', array( $this, 'attempt_clear_cache' ), 99999 );
			add_action( 'deleted_user', array( $this, 'attempt_clear_cache' ), 99999 );
			add_action( 'profile_update', array( $this, 'attempt_clear_cache' ), 99999 );
			add_action( 'edit_user_profile_update', array( $this, 'attempt_clear_cache' ), 99999 );
		}

		/**
		 * Attempt to clear cached Members List information
		 *
		 * @return void
		 */
		public function attempt_clear_cache() {
			if ( false === $this->clear_cache() ) {
				$message = esc_attr__(
					'Could not clear all of the cached members list data. Check error logs for more information.',
					'e20r-members-list'
				);
				$this->utils->add_message( $message, 'warning', 'backend' );
			}
		}

		/**
		 * Clear the cache for the Better Members List plugin (used by save user/update member, etc actions)
		 *
		 * @return bool
		 */
		public function clear_cache() {
			try {
				// Delete the total # of records cache
				if ( false === Cache::delete( null, $this->page->get( 'total_count_cache_group' ) ) ) {
					return false;
				}
			} catch ( InvalidSettingsKey $e ) {
				$this->utils->log( 'Error clearing the "total_count_cache_group" cache' );
				return false;
			}

			try {
				// Delete the search/query result cache
				if ( false === Cache::delete( null, $this->page->get( 'result_cache_group' ) ) ) {
					return false;
				}
			} catch ( InvalidSettingsKey $e ) {
				$this->utils->log( 'Error clearing the "result_cache_group" cache' );
				return false;
			}

			// We successfully deleted everything
			return true;
		}

		/**
		 * Load translation (I18N) file(s) if applicable
		 *
		 * @since v3.3 - ENHANCEMENT: Added Translations if possible/applicable
		 */
		public function load_text_domain() {

			$locale  = apply_filters( 'plugin_locale', get_locale(), 'e20r-members-list' );
			$mo_file = "e20r-members-list-{$locale}.mo";

			// Path(s) to local and global (WP).
			$mo_file_local  = dirname( __FILE__ ) . "/languages/{$mo_file}";
			$mo_file_global = WP_LANG_DIR . "/e20r-members-list/{$mo_file}";

			// Start with the global file.
			if ( file_exists( $mo_file_global ) ) {

				load_textdomain(
					'e20r-members-list',
					$mo_file_global
				);
			}

			// Load from local next (if applicable).
			load_textdomain(
				'e20r-members-list',
				$mo_file_local
			);

			// Load with plugin_textdomain or GlotPress.
			load_plugin_textdomain(
				'e20r-members-list',
				false,
				dirname( __FILE__ ) . '/languages/'
			);
		}

		/**
		 * Register with MixPanel when activating the plugin
		 */
		public function installed() {
			// Install/Uninstall events for the plugin
			$mp_events = array(
				'e20r-members-list_activated'   => true,
				'e20r-members-list_deactivated' => true,
			);

			try {
				$this->metrics->get()->registerAllOnce( $mp_events );
			} catch ( InvalidSettingsKey $exception ) {
				$this->utils->log( $exception->getMessage() );
			}

			try {
				$this->metrics->increment_activations( 'e20r-members-list' );
			} catch ( MissingDependencies | InvalidPluginInfo $e ) {
				$this->utils->log( $e->getMessage() );
				$this->utils->add_message( $e->getMessage(), 'error', 'backend' );
			}
		}

		/**
		 * Various actions when deactivating the plugin
		 */
		public function uninstalled() {
			try {
				$this->metrics->decrement_activations( 'e20r-members-list' );
			} catch ( MissingDependencies | InvalidPluginInfo $e ) {
				$this->utils->log( $e->getMessage() );
				$this->utils->add_message( $e->getMessage(), 'error', 'backend' );
			}
		}

		/**
		 * Add links to support & docs for the plugin
		 *
		 * @param array  $links Links for the wp-admin/plugins.php page
		 * @param string $file File name for the plugin we're processing
		 *
		 * @return array
		 */
		public function plugin_row_meta( $links, $file ) {

			if ( false !== stripos( $file, 'class-e20r-members-list.php' ) ) {
				// Add (new) 'E20R Better Members List for PMPro' links to plugin listing
				$new_links = array(
					'donate'        => sprintf(
						'<a href="%1$s" title="%2$s">%3$s</a>',
						esc_url_raw( 'https://www.paypal.me/eighty20results' ),
						esc_attr__(
							'Donate to support updates, maintenance and tech support for this plugin',
							'e20r-members-list'
						),
						esc_attr__( 'Donate', 'e20r-members-list' )
					),
					'documentation' => sprintf(
						'<a href="%1$s" title="%2$s">%3$s</a>',
						esc_url_raw( 'https://wordpress.org/plugins/e20r-members-list/' ),
						esc_attr__( 'View the documentation', 'e20r-members-list' ),
						esc_attr__( 'Docs', 'e20r-members-list' )
					),
					'filters'       => sprintf(
						'<a href="%1$s" title="%2$s">%3$s</a>',
						esc_url_raw( plugin_dir_url( __FILE__ ) . '../docs/FILTERS.md' ),
						esc_attr__( 'View the Filter documentation', 'e20r-members-list' ),
						esc_attr__( 'Filters', 'e20r-members-list' )
					),
					'actions'       => sprintf(
						'<a href="%1$s" title="%2$s">%3$s</a>',
						esc_url_raw( plugin_dir_url( __FILE__ ) . '../docs/ACTIONS.md' ),
						esc_attr__( 'View the Actions documentation', 'e20r-members-list' ),
						esc_attr__( 'Actions', 'e20r-members-list' )
					),
					'help'          => sprintf(
						'<a href="%1$s" title="%2$s">%3$s</a>',
						esc_url_raw( 'https://wordpress.org/support/plugin/e20r-members-list' ),
						esc_attr__( 'Visit the support forum', 'e20r-members-list' ),
						esc_attr__( 'Support', 'e20r-members-list' )
					),
					'issues'        => sprintf(
						'<a href="%1$s" title="%2$s" target="_blank">%3$s</a>',
						esc_url_raw( 'https://github.com/eighty20results/e20r-members-list/issues' ),
						esc_attr__( 'Report issues with this plugin', 'e20r-members-list' ),
						esc_attr__( 'Report Issues', 'e20r-members-list' )
					),
				);

				$links = array_merge( $links, $new_links );
			}

			return $links;
		}
	}
}

/**
 * Load the required E20R Utilities Module functionality
 */
require_once __DIR__ . '/ActivateUtilitiesPlugin.php';

if ( function_exists( 'apply_filters' ) && ! apply_filters( 'e20r_utilities_module_installed', false ) ) {

	$required_plugin = 'Better Members List for Paid Memberships Pro';

	if ( false === \E20R\Utilities\ActivateUtilitiesPlugin::attempt_activation() ) {
		add_action(
			'admin_notices',
			function () use ( $required_plugin ) {
				\E20R\Utilities\ActivateUtilitiesPlugin::plugin_not_installed( $required_plugin );
			}
		);

		return false;
	}
}

if ( function_exists( 'add_action' ) ) {
	$ml_class = new E20R_Members_List();
	add_action( 'plugins_loaded', array( $ml_class, 'load_hooks' ) );
	register_activation_hook( __FILE__, array( $ml_class, 'installed' ) );
	register_deactivation_hook( __FILE__, array( $ml_class, 'uninstalled' ) );
}
