<?php
/*
Plugin Name: Enhanced Members List for Paid Memberships Pro
Plugin URI: https://eighty20results.com/paid-memberships-pro/do-it-for-me/
Description: Extensible, sortable & bulk action capable members listing tool for Paid Memberships Pro
Version: 2.7
Author: Eighty / 20 Results by Wicked Strong Chicks, LLC <thomas@eighty20results.com>
Author URI: https://eighty20results.com/thomas-sjolshagen/
Text Domain: e20r-members-list
Domain Path: /languages
License:

	Copyright 2016-2018 - Eighty / 20 Results by Wicked Strong Chicks, LLC (thomas@eighty20results.com)

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

class E20R_Members_List {
	
	/**
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
	 */
	public function load_hooks() {
		add_action( 'http_api_curl', array( $this, 'forceTLS12' ) );
		add_action( 'init', array( Members_List_Page::get_instance(), 'load_hooks' ), - 1 );
	}
	
	/**
	 * Connect to the license server using TLS 1.2
	 *
	 * @param $handle - File handle for the pipe to the CURL process
	 */
	public function forceTLS12( $handle ) {
		// set the CURL option to use.
		curl_setopt( $handle, CURLOPT_SSLVERSION, 6 );
	}
}

spl_autoload_register( 'E20R\Members_List\Controller\E20R_Members_List::autoLoader' );

add_action( 'plugins_loaded', array( E20R_Members_List::get_instance(), 'load_hooks' ) );

/**
 * One-click update handler & checker
 */
if ( ! class_exists( '\Puc_v4_Factory' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'plugin-updates/plugin-update-checker.php' );
}

$eml_updates = \Puc_v4_Factory::buildUpdateChecker(
	'https://eighty20results.com/protected-content/e20r-members-list/metadata.json',
	__FILE__,
	'e20r-members-list'
);