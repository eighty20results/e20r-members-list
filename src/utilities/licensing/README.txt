= E20R Licensing Utility module =

This module is intended to simplify support for licensed WordPress plugins.

== Prerequisites ==

1. Include the Eighty/20 Results (E20R) "Utilities" github.com module in your software development project
1. Register an auto-loader for the E20R Utilities module (see below)
1. Set the E20R_LICENSE_SERVER_URL configuration variable in wp-config.php to match your WooCommerce website home_url() value
1. Add the `Licensing::load_hooks()` method. e.g: `add_action( 'plugins_loaded', array( Licensing::get_instance(), 'load_hooks' ), 12 );`
1. Have WooCommerce installed and configure on your website (where you wish to sell licenses)
1. Have the WooCommerce [License Keys for WooCommerce](https://wordpress.org/plugins/woo-license-keys/) plugin installed and configured on your website
1. Optionally purchase and install the [WooCommerce License Keys (Extended)](https://www.10quality.com/product/woocommerce-license-keys/) plugin
1. Create a "License" or "Simple Subscription" product, using the same SKU value as you used to define the 'key_prefix', 'stub' and 'product_sku' in the `License_Client::add_new_license_info()` method
1. Add a client class for the licensing module by extending it from the E20R Licensing utlitity module base `License_Client` class. For example: `class My_License_Client extends \E20R\Utilities\Licensing\License_Client {}` (see below)
1. Adjust any E20R Licensing utilities module filters to match your needs (see "Filters & Hooks" section below)


=== Installation of the E20R Utilities module ===

To install the E20R Licensing utility module, you'll need to add it as a sub-directory of your plugin.

The easiest way to go about this _and_ ensure you stay up to date with bug fixes etc, is to add the E20R Utilities project on github as a Git sub-module in your own plugin development project/directory:

`
$ cd <path-to-project>
$ git submodule add -b master git@github.com:eighty20results/Utilities Utilities
$ git submodule update --remote
`

=== Using the E20R Licensing utility module ===

You need to add and register a PHP (class) auto loader function in your project.

The following example presumes that all of your PHP class files the `class-<classname>.php` format:

Examples:
	class My_Custom_Class -> class-My_Custom_Class.php
	class MyCustomClass -> class-MyCustomClass.php
	class mycustomclass -> class-mycustomclass.php
`
namespace My_Custom_Plugin;

class MyCustomClass {

	/**
	 * Class auto-loader the MyCustomClass plugin
	 *
	 * @param string $class_name Name of the class to auto-load
	 *
	 * @since  1.0
	 * @access public static
	 *
	 */
	public function autoLoader( $class_name ) {

		$pattern       = preg_quote( 'e20r\\utilities' );
		$has_utilities = ( 1 === preg_match( "/{$pattern}/i", $class_name ) );

		if ( false === stripos( $class_name, 'e20r' ) ) {
			return;
		}

		$parts = explode( '\\', $class_name );
		//$c_name    = strtolower( preg_replace( '/_/', '-', $parts[ ( count( $parts ) - 1 ) ] ) );
		$base_path = plugin_dir_path( __FILE__ ) . 'inc/';

		if ( $has_utilities ) {
			$c_name   = preg_replace( '/_/', '-', $parts[ ( count( $parts ) - 1 ) ] );
			$filename = strtolower( "class.{$c_name}.php" );
		} else {
			$c_name   = $parts[ ( count( $parts ) - 1 ) ];
			$filename = "class-{$c_name}.php";
		}

		$iterator = new \RecursiveDirectoryIterator( $base_path, \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveIteratorIterator::SELF_FIRST | \RecursiveIteratorIterator::CATCH_GET_CHILD | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS );

		/**
		 * Locate class files, recursively
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
}

try {
	spl_autoload_register( '\\MyCustomClass::autoLoader' ) );
} catch ( \Exception $exception ) {
	error_log( "Error: Unable to register autoloader -> " . $exception->getMessage() );
	exit();
}
`

=== Include the E20R Licenses option on the WP Admin 'Settings' menu tab ===

This can either be done directly in the admin_init action hook, or it can be handled in your own plugin if/when it
loads its own Settings API page in the 'admin_init' action hook.

`add_action( 'admin_menu', '\\E20R\\Utilities\\Licensing\\Licensing::add_settings_page', 10 );`

Alternatively, loading it in a separate Settings API options page function for your own plugin, but make sure to
use the [WordPress Settings API](https://developer.wordpress.org/plugins/settings/settings-api/):

`
function add_license_settings_page() {

	[ ... Add your own settings page registration code ...]

	Licensing::add_settings_page();
}

add_action( 'admin_menu', 'add_license_settings_page', 10 );
`


Using the WordPress Settings API, we also need to register the S E20R Licensing settings:

Within a `admin_init` action handler:

add_action( 'admin_init', 'register', 10 );

`
function register() {

	[... do your own Settings API 'register_[...]()' functions ...]

	// Added to trigger registration of settings for the E20R Licensing utility module
	License_Settings::register_settings();
}
`
Alternatively, the Settings API compliant registration of the Licensing settings can be triggered directly in its own
`admin_init` action hook:

`add_action( 'admin_init', '\\E20R\Utilities\\Licensing\\License_Settings::register_settings', 10 );`

=== Creating and registering your own license product ===

To register your own License product SKU and inform the E20R Licensing utility library of the required license information, you'll need to extend the `License_Client` class and add the implementation for the required abstract class members.

These class members are:

`public function load_hooks()`
`public function check_licenses()`

Example 'load_hooks' client method:
`
	/**
	 * Load action hooks & filters for Client License handler
	 */
	public function load_hooks() {

		if ( is_admin() ) {
			add_filter( 'e20r-license-add-new-licenses', array( $this, 'add_new_license_info', ), 10, 2 );
			add_action( 'admin_init', array( $this, 'check_licenses' ) );
		}
	}
`

Example 'check_licenses' client method:
`
	/**
	 * Load a custom license warning on init
	 */
	public function check_licenses() {

		$utils = Utilities::get_instance();

		switch ( Licensing::is_license_expiring( 'my_license_sku' ) ) {

			case true:
				$utils->add_message(
					sprintf(
						__(
							'The license for \'%1$s\' will renew soon. As this is an automatic payment, you will not have to do anything. To change %2$syour license%3$s, please go to %4$syour account page%5$s',
							't10n-slug'
						),
						__(
							'My Custom Plugin',
							't10n-slug'
						),
						'<a href="https://mywebserver.com/shop/licenses/" target="_blank">',
						'</a>',
						'<a href="https://mywebserver.com/account/" target="_blank">',
						'</a>'
					),
					'info',
					'backend'
				);
				break;
			case - 1:
				$utils->add_message(
					sprintf(
						__(
							'Your \'%1$s\' license has expired. To continue to get updates and support for this plugin, you will need to %2$srenew and install your license%3$s.',
							't10n-slug'
						),
						__(
							'My Custom Plugin',
							't10n-slug'
						),
						'<a href="https://mywebserver.com/shop/licenses/" target="_blank">', '</a>'
					),
					'error',
					'backend'
				);
				break;
		}
	}
`
You should, probably, also override the `License_Client::add_new_license_info()` member function.

See the "Example Client Licensing class in your own plugin" section below for a more comprehensive example (full class).

=== Using the license management in your code ===

To check if the plugin is licensed, you can add a simple `if/then` statement:


`
if ( ! Licensing::is_licensed( 'MY_LICENSE_SKU' ) ) {
	die( "The license for this plugin has not been activated yet, or has expired!" );
}
`

The result of the check is cached for a day (24 hours), so as a result the `Licensing::is_licensed()` method will only connect with the upstream license purchase website once every 24 hours (or so).

=== Example Client Licensing class in your own plugin: ===

The following is an example of a License registration class (a version of this is required in order to use licensing in your own project):

`
namespace My_Custom_Plugin;

use E20R\Utilities\Licensing\License_Client;
use E20R\Utilities\Licensing\Licensing;
use E20R\Utilities\Utilities;

class My_License extends License_Client {

	/**
	 * Current/only instance of this class (singleton pattern)
	 *
	 * @var My_License|null
	 */
	private static $instance = null;

	/**
	 * Constructor for the My_License example class
	 */
	private function __construct() {
	}

	/**
	 * Return, or create, instance of My_License class
	 *
	 * @return My_License|null
	 */
	public static function getInstance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load action hooks & filters for Client License handler
	 */
	public function load_hooks() {

		if ( is_admin() ) {
			add_filter( 'e20r-license-add-new-licenses', array( $this, 'add_new_license_info', ), 10, 2 );
			add_action( 'admin_init', array( $this, 'check_licenses' ) );
		}
	}

	/**
	 * Configure settings for the my example client license (must match upstream license info)
	 *
	 * @param array $license_settings
	 * @param array $plugin_settings
	 *
	 * @return array
	 */
	public function add_new_license_info( $license_settings, $plugin_settings = array() ) {

		$utils = Utilities::get_instance();

		if ( ! is_array( $plugin_settings ) ) {
			$plugin_settings = array();
		}

		$utils->log( "Load settings for my example client license class" );
		$plugin_settings['e20r_pmpec'] = array(
			'key_prefix'  => 'my_license_sku',
			'stub'        => 'my_license_sku',
			'product_sku' => 'MY_LICENSE_SKU', // The Woocommerce Product SKU string to use when identifying the license
			'label'       => __( 'My Custom Client License', 't10n-slug' ),
		);

		$license_settings = parent::add_new_license_info( $license_settings, $plugin_settings['my_license'] );

		return $license_settings;
	}

	/**
	 * Load a custom license warning on init
	 */
	public function check_licenses() {

		$utils = Utilities::get_instance();

		switch ( Licensing::is_license_expiring( 'MY_LICENSE_SKU' ) ) {

			case true:
				$utils->add_message( sprintf( __( 'The license for \'%s\' will renew soon. As this is an automatic payment, you will not have to do anything. To change %syour license%s, please go to %syour account page%s' ), __( 'My Custom License', 't10n-slug' ), '<a href="https://mywebserver.com/shop/licenses/" target="_blank">', '</a>', '<a href="https://mywebserver.com/account/" target="_blank">', '</a>' ), 'info', 'backend' );
				break;
			case - 1:
				$utils->add_message( sprintf( __( 'Your \'%s\' license has expired. To continue to get updates and support for this plugin, you will need to %srenew and install your license%s.' ), 'My Custom License', '<a href="https://mywebserver.com/shop/licenses/" target="_blank">', '</a>' ), 'error', 'backend' );
				break;
		}
	}

	/**
	 * Hide/deactivate the __clone() magic method
	 */
	private function __clone() {
	}
}
`

=== Filters and Hooks ===

Filters:
	`e20r-license-support-account-url` - Change the URL used for your WooCommerce store 'My Account' page (see the WooCommerce settings/shortcode documentation). The default URL appends `/account/` to the URL specified in the wp-config.php 'E20R_LICENSING_SERVER' URL.
	`e20r-license-remote-server-timeout` - The wp_remote_*() timeout value used. Default value is ""
