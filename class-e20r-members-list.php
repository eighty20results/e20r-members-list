<?php
/*
Plugin Name: Better Members List for Paid Memberships Pro
Plugin URI: https://wordpress.org/plugins/e20r-members-list
Description: Extensible, sortable & bulk action capable members listing + export to CSV tool for Paid Memberships Pro.
Version: 8.1
Author: Thomas Sjolshagen @ Eighty / 20 Results by Wicked Strong Chicks, LLC <thomas@eighty20results.com>
Author URI: https://eighty20results.com/thomas-sjolshagen/
Text Domain: e20r-members-list
Domain Path: /languages
License:

	Copyright 2016 - 2021 (c) Eighty / 20 Results by Wicked Strong Chicks, LLC (thomas@eighty20results.com)

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

*/

namespace E20R\Members_List\Controller;

use E20R\Members_List\Admin\Members_List_Page;

if ( ! defined( 'E20R_MEMBERSLIST_VER' ) ) {
	define( 'E20R_MEMBERSLIST_VER', '8.1' );
}

if ( ! class_exists( '\\E20R\\Members_List\\Controller\\E20R_Members_List' ) ) {
	/**
	 * Class E20R_Members_List
	 * @package E20R\Members_List\Controller
	 */
	class E20R_Members_List {

		/**
		 * Instance of the Member List controller
		 *
		 * @var null|E20R_Members_List
		 */
		private static $instance = null;

		/**
		 * E20R_Members_List constructor.
		 */
		private function __construct() {
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
		 * Class auto-loader for the Enhanced Members List plugin
		 *
		 * @param string $class_name Name of the class to auto-load
		 *
		 * @return false|bool
		 * @since  1.0
		 * @access public static
		 *
		 * @test E20R_Members_ListTest::test_auto_loader_success
		 * @test E20R_Members_ListTest::test_auto_loader_error_returns
		 */
		public static function auto_loader( $class_name ) {

			if ( false === stripos( $class_name, 'e20r' ) ) {
				return false;
			}

			$parts     = explode( '\\', $class_name );
			$c_name    = strtolower( preg_replace( '/_/', '-', $parts[ ( count( $parts ) - 1 ) ] ) );
			$base_path = plugin_dir_path( __FILE__ ) . 'classes/';

			if ( file_exists( plugin_dir_path( __FILE__ ) . 'class/' ) ) {
				$base_path = plugin_dir_path( __FILE__ ) . 'class/';
			}

			if ( file_exists( plugin_dir_path( __FILE__ ) . 'src/' ) ) {
				$base_path = plugin_dir_path( __FILE__ ) . 'src/';
			}

			$filename = "class-{$c_name}.php";

			try {
				$iterator = new \RecursiveDirectoryIterator(
					$base_path,
					\RecursiveDirectoryIterator::SKIP_DOTS |
					\RecursiveIteratorIterator::SELF_FIRST |
					\RecursiveIteratorIterator::CATCH_GET_CHILD |
					\RecursiveDirectoryIterator::FOLLOW_SYMLINKS
				);
			} catch ( \Exception $ri_except ) {
				error_log( "Error instantiating iterator for ${class_name}: " . $ri_except->getMessage() );
				return false;
			}
			/**
			 * Locate the class files for the plugin, recursively
			 */
			try {
				$filter = new \RecursiveCallbackFilterIterator(
					$iterator,
					function ( $current, $key, $iterator ) use ( $filename ) {
						$file_name = $current->getFilename();

						// Skip hidden files and directories.
						if ( '.' === $file_name[0] || '..' === $file_name ) {
							return false;
						}

						if ( $current->isDir() ) {
							// Only recurse into intended subdirectories.
							return $file_name() === $filename;
						} else {
							// Only consume files of interest.
							return strpos( $file_name, $filename ) === 0;
						}
					}
				);
			} catch ( \Exception $fh_except ) {
				error_log( "Error locating ${class_name}: " . $fh_except->getMessage() );
				return false;
			}

			try {
				foreach ( new \RecursiveIteratorIterator( $iterator ) as $f_filename => $f_file ) {

					$class_path = sprintf( '%1$s/%2$s', $f_file->getPath(), $f_file->getFilename() );

					if ( $f_file->isFile() && false !== strpos( $class_path, $filename ) ) {
						require_once $class_path;
						return true;
					}
				}
			} catch ( \Exception $e ) {
				error_log( "Error loading ${class_name}: " . $e->getMessage() );
				return false;
			}

			return false;
		}

		/**
		 * Initialize the Enhanced Members List functionality
		 *
		 * @since v3.3 - ENHANCEMENT: Load translation/I18N
		 *
		 * @test E20R_Members_ListTest::test_load_hooks()
		 */
		public function load_hooks() {
			add_action( 'init', array( Members_List_Page::get_instance(), 'load_hooks' ), - 1 );
			add_action( 'init', array( $this, 'load_text_domain' ), 1 );
		}

		/**
		 * Load translation (I18N) file(s) if applicable
		 *
		 * @since v3.3 - ENHANCEMENT: Added Translations if possible/applicable
		 */
		public function load_text_domain() {

			$locale  = apply_filters( 'plugin_locale', get_locale(), 'e20r-members-list' );
			$mo_file = "e20r-members-list-{$locale}.mo";

			// Path(s) to local and global (WP)
			$mo_file_local  = dirname( __FILE__ ) . "/languages/{$mo_file}";
			$mo_file_global = WP_LANG_DIR . "/e20r-members-list/{$mo_file}";

			// Start with the global file
			if ( file_exists( $mo_file_global ) ) {

				load_textdomain(
					'e20r-members-list',
					$mo_file_global
				);
			}

			// Load from local next (if applicable)
			load_textdomain(
				'e20r-members-list',
				$mo_file_local
			);

			// Load with plugin_textdomain or GlotPress
			load_plugin_textdomain(
				'e20r-members-list',
				false,
				dirname( __FILE__ ) . '/languages/'
			);
		}
	}

}

// BUG FIX: Fatal error when e20r-Utilities module is present
if ( ! file_exists( WP_PLUGIN_DIR . '/00-e20r-utilities/' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'src/utilities/class-utility-loader.php';
}

try {
	spl_autoload_register( 'E20R\Members_List\Controller\E20R_Members_List::auto_loader' );
} catch ( \Exception $exception ) {
	error_log( 'Unable to register auto_loader: ' . $exception->getMessage(), E_USER_ERROR );
	return false;
}

add_action( 'plugins_loaded', array( E20R_Members_List::get_instance(), 'load_hooks' ) );

if ( class_exists( '\E20R\Utilities\Utilities' ) && ( file_exists( WP_PLUGIN_DIR . '/00-e20r-utilities/' ) || file_exists( plugin_dir_path( __FILE__ ) . 'src/utilities/class-loader.php' ) ) ) {
	\E20R\Utilities\Utilities::configureUpdateServerV4( 'e20r-members-list', plugin_dir_path( __FILE__ ) . 'class-e20r-members-list.php' );
}
