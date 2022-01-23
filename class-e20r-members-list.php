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
use E20R\Utilities\Utilities;
use E20R\Utilities\Message;
use function add_action;

if ( ! defined( 'ABSPATH' ) ) {
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
				$mixpanel = new MixpanelConnector( 'a14f11781866c2117ab6487792e4ebfd' );
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
	}

}

/**
 * Load the required E20R Utilities Module functionality
 */
require_once __DIR__ . '/ActivateUtilitiesPlugin.php';

if ( ! apply_filters( 'e20r_utilities_module_installed', false ) ) {

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
