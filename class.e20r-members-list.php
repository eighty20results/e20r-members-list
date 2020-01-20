<?php
/*
Plugin Name: Better Members List for Paid Memberships Pro
Plugin URI: https://wordpress.org/plugins/e20r-members-list
Description: Extensible, sortable & bulk action capable members listing + export to CSV tool for Paid Memberships Pro.
Version: 5.7.5
Author: Thomas Sjolshagen @ Eighty / 20 Results by Wicked Strong Chicks, LLC <thomas@eighty20results.com>
Author URI: https://eighty20results.com/thomas-sjolshagen/
Text Domain: e20r-members-list
Domain Path: /languages
License:

	Copyright 2016 - 2020 (c) Eighty / 20 Results by Wicked Strong Chicks, LLC (thomas@eighty20results.com)

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
	define( 'E20R_MEMBERSLIST_VER', '5.7.5' );
}

if ( ! class_exists( '\\E20R\Members_List\\Controller\\E20R_Members_List' ) ) {
	/**
	 * Class E20R_Members_List
	 * @package E20R\Members_List\Controller
	 */
	class E20R_Members_List {
		
		const plugin_slug = 'e20r-members-list';
		
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
		 */
		public static function get_instance() {
			
			if ( is_null( self::$instance ) ) {
				self::$instance = new self;
			}
			
			return self::$instance;
		}
		
		/**
		 * Class auto-loader for the Enhanced Members List plugin
		 *
		 * @param string $class_name Name of the class to auto-load
		 *
		 * @since  1.0
		 * @access public static
		 */
		public static function autoLoader( $class_name ) {
			
			if ( false === stripos( $class_name, 'e20r' ) ) {
				return;
			}
			
			$parts     = explode( '\\', $class_name );
			$c_name    = strtolower( preg_replace( '/_/', '-', $parts[ ( count( $parts ) - 1 ) ] ) );
			$base_path = plugin_dir_path( __FILE__ ) . 'classes/';
			
			if ( file_exists( plugin_dir_path( __FILE__ ) . 'class/' ) ) {
				$base_path = plugin_dir_path( __FILE__ ) . 'class/';
			}
			
			$filename = "class.{$c_name}.php";
			$iterator = new \RecursiveDirectoryIterator( $base_path, \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveIteratorIterator::SELF_FIRST | \RecursiveIteratorIterator::CATCH_GET_CHILD | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS );
			
			/**
			 * Loate class member files, recursively
			 */
			$filter = new \RecursiveCallbackFilterIterator( $iterator, function ( $current, $key, $iterator ) use ( $filename ) {
				
				$file_name = $current->getFilename();
				
				// Skip hidden files and directories.
				if ( $file_name[0] == '.' || $file_name == '..' ) {
					return false;
				}
				
				if ( $current->isDir() ) {
					// Only recurse into intended subdirectories.
					return $file_name() === $filename;
				} else {
					// Only consume files of interest.
					return strpos( $file_name, $filename ) === 0;
				}
			} );
			
			foreach ( new \ RecursiveIteratorIterator( $iterator ) as $f_filename => $f_file ) {
				
				$class_path = $f_file->getPath() . "/" . $f_file->getFilename();
				
				if ( $f_file->isFile() && false !== strpos( $class_path, $filename ) ) {
					require_once( $class_path );
				}
			}
		}
		
		/**
		 * Initialize the Enhanced Members List functionality
		 *
		 * @since v3.3 - ENHANCEMENT: Load translation/I18N
		 */
		public function load_hooks() {
			add_action( 'init', array( Members_List_Page::get_instance(), 'load_hooks' ), - 1 );
			add_action( 'init', array( $this, 'loadTextDomain' ), 1 );
		}
		
		/**
		 * Load translation (I18N) file(s) if applicable
		 *
		 * @since v3.3 - ENHANCEMENT: Added Translations if possible/applicable
		 */
		public function loadTextDomain() {
			
			$locale = apply_filters( "plugin_locale", get_locale(), self::plugin_slug );
			$mo_file = self::plugin_slug . "-{$locale}.mo";
			
			// Path(s) to local and global (WP)
			$mo_file_local  = dirname( __FILE__ ) . "/languages/{$mo_file}";
			$mo_file_global = WP_LANG_DIR . "/e20r-members-list/{$mo_file}";
			
			// Start with the global file
			if ( file_exists( $mo_file_global ) ) {
				
				load_textdomain(
					E20R_Members_List::plugin_slug,
					$mo_file_global
				);
			}
			
			// Load from local next (if applicable)
			load_textdomain(
				E20R_Members_List::plugin_slug,
				$mo_file_local
			);
			
			// Load with plugin_textdomain or GlotPress
			load_plugin_textdomain(
				E20R_Members_List::plugin_slug,
				false,
				dirname( __FILE__ ) . "/languages/"
			);
		}
	}
	
}
try {
	spl_autoload_register( 'E20R\Members_List\Controller\E20R_Members_List::autoLoader' );
} catch( \Exception $exception ) {
	error_log("Unable to register autoloader: " . $exception->getMessage(),E_USER_ERROR );
	return false;
}

add_action( 'plugins_loaded', array( E20R_Members_List::get_instance(), 'load_hooks' ) );

\E20R\Utilities\Utilities::configureUpdateServerV4( 'e20r-members-list', plugin_dir_path( __FILE__ ) . 'class.e20r-members-list.php' );
