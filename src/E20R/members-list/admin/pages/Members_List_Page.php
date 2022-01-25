<?php
/**
 * Copyright (c) 2018 - 2022 - Eighty / 20 Results by Wicked Strong Chicks.
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
 *
 * @package E20R\Members_List\Admin\Pages\Members_List_Page
 */

namespace E20R\Members_List\Admin\Pages;

use E20R\Exceptions\InvalidSettingsKey;
use E20R\Members_List\Members_List;
use E20R\Utilities\Message;
use E20R\Utilities\Utilities;

if ( ! defined( 'ABSPATH' ) && defined( 'PLUGIN_PHPUNIT' ) ) {
	die( 'WordPress not loaded. Naughty, naughty!' );
}

if ( ! class_exists( '\\E20R\\Members_List\\Admin\\Members_List_Page' ) ) {

	/**
	 * Generates the page for the Members_List wp-admin interface.
	 */
	class Members_List_Page {

		/**
		 * Instance of the Members_List_Page class (Singleton)
		 *
		 * @var null|Members_List_Page $instance
		 */
		private static $instance = null;

		/**
		 * Holds the list of members (Members_List class)
		 *
		 * @var Members_List $member_list
		 */
		public $member_list;

		/**
		 * Holds the standard E20R Utilities Module class
		 *
		 * @var null|Utilities $utils - Utilities class
		 */
		private $utils;

		/**
		 * The Members List class instance
		 *
		 * @var Members_List|null $list
		 */
		private $list = null;

		/**
		 * The search string being used
		 *
		 * @var null|string $search
		 */
		private $search = null;

		/**
		 * The membership level we're processing for
		 *
		 * @var null|int $level
		 */
		private $level = null;

		/**
		 * A comma-separated list of level IDs
		 *
		 * @var null|string $level_ids
		 */
		private $level_ids = null;
		/**
		 * The page number (for pagination).
		 *
		 * @var null|int $page_number
		 */
		private $page_number = null;

		/**
		 * The timeout value for returned data of a given ML query. Default: 5 minutes
		 *
		 * @var int $cache_timeout
		 */
		private $cache_timeout = 5;

		/**
		 * The cache group name for query results
		 *
		 * @var string $result_cache_group
		 */
		private $result_cache_group = 'mlu';

		/**
		 * The cache group for the 'total records returned for the query' results
		 *
		 * @var string $total_count_cache_group
		 */
		private $total_count_cache_group = 'mlt';

		/**
		 * Members_List_Page constructor. Loads required menu(s) & screen options
		 *
		 * @param null|Utilities $utils An instance of the Utilities class
		 */
		public function __construct( $utils = null ) {
			if ( empty( $utils ) ) {
				$message = new Message();
				$utils   = new Utilities( $message );
			}

			$this->utils    = $utils;
			self::$instance = $this;
		}

		/**
		 * Creates or returns an instance of the Members_List_Page class.
		 *
		 * @return  Members_List_Page A single instance of this class.
		 */
		public static function get_instance() {

			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Return parameter values for the class
		 *
		 * @param string $param The class parameter we're trying to get the value of
		 *
		 * @return mixed
		 * @throws InvalidSettingsKey Raised if the parameter doesn't exist in this class
		 */
		public function get( $param = 'members_list' ) {
			if ( ! property_exists( $this, $param ) ) {
				throw new InvalidSettingsKey( esc_attr__( 'Error: The requested parameter does not exist!', 'e20r-members-list' ) );
			}

			return $this->{$param};
		}
		/**
		 * Removes the old Member List page & appends a new one to the "Memberships" admin bar node
		 */
		public static function admin_bar_menu() {

			// Set this to true if PMPro isn't active (the constand doesn't exist) so we can exit quickly
			$is_pmpro_v2 = self::get_instance()->is_pmpro_v2();

			// Exit if PMPro is inactive _or_ we're running a version of PMPro prior to v2.0
			if ( true === $is_pmpro_v2 || null === $is_pmpro_v2 ) {
				return;
			}

			global $wp_admin_bar;

			if (
					! is_admin_bar_showing() || (
							! is_super_admin() && ! current_user_can( 'manage_options' ) &&
							! current_user_can( 'pmpro_memberslist' ) &&
							! current_user_can( 'e20r_memberslist' )
					)
			) {
				if ( ! is_null( self::$instance ) ) {
					self::$instance->utils->log( 'Unable to change admin bar (wrong capabilities for user)' );
				}

				return;
			}

			$wp_admin_bar->remove_menu( 'pmpro-members-list' );
			$wp_admin_bar->remove_node( 'pmpro-members-list' );

			// Add the (new) Members List page to the admin_bar menu.
			$wp_admin_bar->add_menu(
				array(
					'id'     => 'e20r-members-list',
					'title'  => esc_attr__( 'Members List', 'pmpro' ),
					'href'   => add_query_arg(
						'page',
						'pmpro-memberslist',
						get_admin_url( get_current_blog_id(), 'admin.php' )
					),
					'parent' => 'paid-memberships-pro',
				)
			);
		}

		/**
		 * Screen Option option(s) for the Members List page.
		 *
		 * @param mixed  $status The screen status.
		 * @param string $option The screen parameter.
		 * @param mixed  $value The value we expect to return.
		 *
		 * @return mixed
		 */
		public static function set_screen( $status, $option, $value ) {

			self::$instance->utils->log( "Saving screen option (page: {$option})? {$value} vs {$status}" );

			if ( 'per_page' === $option ) {
				return $value;
			}

			return $status;
		}

		/**
		 * Load Action and Filter hooks for the Members List page
		 *
		 * @param null|Members_List $list The Members_List Class instance used by this page.
		 */
		public function load_hooks( $list = null ) {
			$this->utils->log( 'Loading the members list page handler(s)' );

			// Filters for Members_List_Page rendering
			add_filter( 'set-screen-option', array( $this, 'set_screen' ), 10, 3 );
			add_filter( 'set_url_scheme', array( $this, 'add_to_pagination' ), 10, 3 );

			// Actions for Members_List_Page rendering
			add_action( 'admin_menu', array( $this, 'plugin_menu' ), 9999 );
			add_action( 'admin_init', 'E20R\\Members_List\\Admin\\Export\\Export_Members::clear_temp_files', 10 );
			// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			// add_action( 'admin_bar_menu', array( $this->page, 'admin_bar_menu' ), 9999 );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts_styles' ) );
			add_action( 'wp_ajax_e20rml_export_records', array( $this, 'ajax_handler_export' ) );
			add_action( 'wp_ajax_nopriv_e20rml_export_records', array( $this, 'nopriv_export_ajax_handler' ) );
			return true;
		}

		/**
		 * AJAX handler when user is not privileged (denied operation)
		 *
		 * @return void
		 */
		public function nopriv_export_ajax_handler() {
			wp_die( esc_attr__( 'Error: Operation attempted by unprivileged user!', 'e20r-members-list' ) );
		}

		/**
		 * AJAX handler for the export operation
		 *
		 * @return void
		 */
		public function ajax_handler_export() {
			wp_verify_nonce( $this->utils->get_variable( '_wpnonce', null ) );
			$this->utils->log( 'Running export operation' );
			$this->member_list = new Members_List( $this->utils, $this );
			$this->member_list->process_bulk_action();
		}

		/**
		 * Load the custom CSS and JavaScript for the Members List
		 *
		 * @param string $hook_suffix The suffix for the hook
		 */
		public function load_scripts_styles( $hook_suffix ) {

			if (
					is_admin() && ( ! defined( 'DOING_AJAX' ) || false === DOING_AJAX ) &&
					1 === preg_match( '/(pmpro|e20r)-memberslist/', $hook_suffix )
			) {

				wp_enqueue_style(
					'jquery-ui',
					'//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css',
					array(),
					'1.12.1'
				);
				wp_enqueue_script( 'jquery-ui-datepicker' );

				wp_enqueue_style(
					'e20r-memberslist-page',
					plugins_url( '/css/e20r-memberslist-page.css', E20R_ML_BASE_DIR ),
					array( 'pmpro_admin' ),
					E20R_MEMBERSLIST_VER
				);

				wp_register_script(
					'e20r-memberslist-page',
					plugins_url( '/js/e20r-memberslist-page.js', E20R_ML_BASE_DIR ),
					array( 'jquery' ),
					E20R_MEMBERSLIST_VER,
					true
				);

				wp_localize_script(
					'e20r-memberslist-page',
					'e20rml',
					array(
						'locale'   => str_replace( '_', '-', get_locale() ),
						'url'      => add_query_arg( 'page', 'pmpro-memberslist', admin_url( 'admin.php' ) ),
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'lang'     => array(
							'save_btn_text'    =>
									esc_attr__( 'Save Updates', 'e20r-members-list' ),
							'clearing_enddate' =>
									esc_attr__(
										'This action will clear the current membership end date/expiration date!',
										'e20r-members-list'
									),
						),
					)
				);

				wp_enqueue_script( 'e20r-memberslist-page' );
			}
		}

		/**
		 * Tests whether Paid Memberships Pro v2.x or later is installed
		 *
		 * @return bool|null
		 */
		private function is_pmpro_v2() {
			return defined( 'PMPRO_VERSION' ) ?
					version_compare( PMPRO_VERSION, '2.0', 'ge' ) :
					null;
		}

		/**
		 * Point Members List menu handler(s) to this plugin
		 */
		public function plugin_menu() {

			$this->utils->log( 'Headers sent? ' . ( headers_sent() ? 'Yes' : 'No' ) );

			$pmpro_menu_slug = 'pmpro-membershiplevels';

			if ( null === $this->is_pmpro_v2() ) {
				return;
			}

			if ( true === $this->is_pmpro_v2() ) {
				$pmpro_menu_slug = 'pmpro-dashboard';
			}

			$this->utils->log( "Remove the default members list page.. (under: {$pmpro_menu_slug})" );

			// "Just" replace the action that loads the PMPro Members List
			$hookname = get_plugin_page_hookname( 'pmpro-memberslist', $pmpro_menu_slug );
			$this->utils->log( "Found hook name: {$hookname}. Sent yet? " . ( headers_sent() ? 'Yes' : 'No' ) );
			remove_action( $hookname, 'pmpro_memberslist', 10 );
			add_action( $hookname, array( $this, 'memberslist_settings_page' ), 11 );
			add_action( 'load-memberships_page_pmpro-memberslist', array( $this, 'screen_option' ), 9999 );
		}

		/**
		 * Add parameters to limit/include records to any members list page URI
		 *
		 * @param string $url The URL to apply the pagination info to.
		 * @param string $scheme The HTTP/HTTPS scheme.
		 * @param string $original_scheme The original Scheme.
		 *
		 * @return string
		 */
		public function add_to_pagination( $url, $scheme, $original_scheme ) {

			$this->page_number = $this->utils->get_variable( 'page', '' );

			if ( isset( $_SERVER['HTTP_HOST'] ) && 1 === preg_match(
				sprintf(
					'/%1$s\/wp-admin\/admin.php\?page=pmpro-memberslist/i',
					sanitize_key( $_SERVER['HTTP_HOST'] )
				),
				$url
			)
			) {

				$arg_list = array();

				if ( null === $this->level ) {
					$this->level = $this->utils->get_variable( 'level', '' );
				}

				if ( null === $this->search ) {
					$this->search = $this->utils->get_variable( 'find', '' );
				}

				if ( ! empty( $this->level ) ) {
					$arg_list['level'] = $this->level;
				}

				if ( ! empty( $this->search ) ) {
					$arg_list['find'] = $this->search;
				}

				/**
				 * Add filtering to the URI (to preserve it for pagination ,etc)
				 *
				 * @filter e20r_memberslist_pagination_args
				 *
				 * @param array $arg_list List of arguments to add to the pagination links
				 */
				$arg_list = apply_filters( 'e20r_memberslist_pagination_args', $arg_list );

				// Encode the new URI variables
				foreach ( $arg_list as $a_key => $value ) {
					$arg_list[ $a_key ] = urlencode_deep( urldecode_deep( $value ) );
				}

				$url = add_query_arg( $arg_list, $url );
			}

			return $url;
		}

		/**
		 * Configure options to use for "Screen Options" on Members_List_Page page.
		 */
		public function screen_option() {

			if ( empty( $this->utils ) ) {
				$message     = new Message();
				$this->utils = new Utilities( $message );
			}

			// Add the 'members per page' option to the 'Screen' drop-down
			$args = array(
				'label'   => esc_attr_x( 'Members per page', 'members per page (screen options)', 'e20r-members-list' ),
				'default' => 15,
				'option'  => 'per_page',
			);

			add_screen_option( 'per_page', $args );

			$this->member_list = new Members_List( $this->utils, $this );
		}

		/**
		 * Load the e20rMembersList page content
		 *
		 * @return string
		 */
		public function memberslist_settings_page() {

			$this->utils->log( 'Have we sent content? ' . ( headers_sent() ? 'yes' : 'no' ) );

			if ( ! function_exists( 'pmpro_loadTemplate' ) ) {
				$this->utils->log( 'Fatal: Paid Memberships Pro is not loaded on site!' );
				return '';
			}

			global $pmpro_msg;
			global $pmpro_msgt;

			$this->search    = $this->utils->get_variable( 'find', '' );
			$this->level     = $this->utils->get_variable( 'level', '' );
			$this->level_ids = $this->utils->get_variable( 'level_ids', '' );

			// phpcs:ignore
			echo pmpro_loadTemplate( 'admin_header', 'local', 'adminpages' );

			$search_array = apply_filters(
				'e20r_memberslist_exportcsv_search_args',
				array(
					'action'         => 'memberslist_csv',
					'find'           => esc_attr( $this->search ),
					'level'          => esc_attr( $this->level ),
					'showDebugTrace' => 'true',
				)
			);

			$csv_url = add_query_arg(
				$search_array,
				get_admin_url( get_current_blog_id(), 'admin-ajax.php' )
			);

			$e20r_error_msgs   = $this->utils->get_message( 'error' );
			$e20r_warning_msgs = $this->utils->get_message( 'warning' );
			$e20r_info_msgs    = $this->utils->get_message( 'info' );

			$top_list = array(
				'active' => esc_attr__( 'Active Members', 'e20r-members-list' ),
				'all'    => esc_attr__( 'All Members', 'e20r-members-list' ),
			);

			$bottom_list = array(
				'cancelled'  => esc_attr__( 'Cancelled Members', 'e20r-members-list' ),
				'expired'    => esc_attr__( 'Expired Members', 'e20r-members-list' ),
				'oldmembers' => esc_attr__( 'Old Members', 'e20r-members-list' ),
			);

			$level_list = array();

			$list = function_exists( 'pmpro_getAllLevels' ) ?
					pmpro_getAllLevels( true, true ) :
					array();

			foreach ( $list as $item ) {
				$level_list[ $item->id ] = $item->name;
			}

			$option_list = $top_list + $level_list + $bottom_list;

			if ( ! empty( $pmpro_msg ) ) { ?>

				<div id="pmpro_message" class="pmpro_message <?php esc_html_e( $pmpro_msgt ); // phpcs:ignore ?>">
					<?php esc_html_e( $pmpro_msg ); // phpcs:ignore ?>
				</div>
				<?php
			} elseif (
					! empty( $e20r_error_msgs ) ||
					! empty( $e20r_warning_msgs ) ||
					! empty( $e20r_info_msgs )
			) {
				$this->utils->display_messages( 'backend' );
			}
			?>
			<div id="overlay">
				<div class="cv-spinner">
					<span class="spinner"></span>
				</div>
			</div>
			<div class="wrap e20r-pmpro-memberslist-page">
				<h1>
					<?php esc_attr_e( 'Members List', 'e20r-members-list' ); ?>
					<a href="<?php echo esc_url_raw( $csv_url ); ?>" class="page-title-action e20r-memberslist-export" target="_blank">
						<?php esc_attr_e( 'Export to CSV', 'e20r-members-list' ); ?>
					</a>
					<?php
					if ( ! empty( $this->search ) ) {
						printf(
							'<span class="e20r-pmpro-memberslist-search-info">%1$s</span>',
							sprintf(
									// translators: %1$s search string
								esc_attr__( 'Search results for "%1$s" in %2$s', 'e20r-members-list' ),
								esc_attr( $this->search ),
								esc_attr( $option_list[ $this->level ] )
							)
						);
					}
					?>
				</h1>
				<hr class="e20r-memberslist-hr"/>
				<h2 class="screen-reader-text">
					<?php esc_attr_e( 'Filter list of members', 'e20r-members-list' ); ?>
				</h2>
				<form method="post" id="posts-filter">
					<div class="e20r-search-arguments">
						<p class="search-box float-left">
							<?php
							$label      = esc_attr__( 'Update List', 'e20r-members-list' );
							$button_def = 'button';

							// phpcs:ignore
							if ( isset( $_REQUEST['find'] ) && ! empty( $_REQUEST['find'] ) ) {

								$label       = esc_attr__( 'Clear Search', 'e20r-members-list' );
								$button_def .= ' button-primary';
							}
							?>

							<input id="e20r-update-list" class="<?php esc_attr_e( $button_def ); // phpcs:ignore ?>" type="submit" value="<?php esc_attr_e( $label ); ?>"/>
						</p>
						<ul class="subsubsub">
							<li>
								<label for="e20r-pmpro-memberslist-levels"><?php esc_attr_e( 'Show', 'e20r-members-list' ); ?></label>
								<select name="level" id="e20r-pmpro-memberslist-levels">
									<?php foreach ( $option_list as $option_id => $option_name ) { ?>
										<option value="<?php esc_attr_e( $option_id ); // phpcs:ignore ?>" <?php selected( $this->level, $option_id ); ?>>
											<?php esc_attr_e( $option_name ); // phpcs:ignore ?>
										</option>
										<?php
									}
									?>
								</select>
							</li>
							<?php do_action( 'e20r_memberslist_addl_search_options', $this->search, $this->level ); ?>
						</ul>
						<p class="search-box float-right">
							<label class="hidden" for="post-search-input">
								<?php esc_attr_e( 'Search', 'e20r-members-list' ); ?>:
							</label>
							<input type="hidden" name="page" value="e20r-memberslist"/>
							<input id="post-search-input" type="text" value="<?php esc_attr_e( $this->search ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText ?>" name="find"/>
							<input class="button" type="submit" id="e20r-memberslist-search-data" value="<?php esc_attr_e( 'Search Members', 'e20r-members-list' ); ?>"/>
						</p>
					</div>
					<h2 class="screen-reader-text">
					<?php
					esc_attr_e( 'Member list', 'e20r-members-list' );
					?>
					</h2>
					<hr class="e20r-memberslist-hr"/>
					<?php
					$this->member_list->prepare_items();
					$this->member_list->display();
					?>
				</form>
			</div>

			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo pmpro_loadTemplate( 'admin_footer', 'local', 'adminpages' );
				return '';
		}
	}
}
