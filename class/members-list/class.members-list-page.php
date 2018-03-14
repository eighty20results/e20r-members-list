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

namespace E20R\Members_List\Admin;

define( 'E20R_MEMBERSLIST_VER', '2.0' );

use E20R\Utilities\Utilities;

class Members_List_Page {
	
	/**
	 * @var
	 */
	private static $instance;
	
	/**
	 * @var Members_List $member_list
	 */
	public $member_list;
	
	/**
	 * @var     Utilities $utils - Utilities class
	 */
	private $utils;
	
	/**
	 * Members_List_Page constructor. Loads required menu(s) & screen options
	 */
	private function __construct() {
	}
	
	/**
	 * Creates or returns an instance of the Members_List_Page class.
	 *
	 * @return  Members_List_Page A single instance of this class.
	 */
	public static function get_instance() {
		
		if ( null === self::$instance ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Removes the old Member List page & appends a new one to the "Memberships" admin bar node
	 */
	public static function admin_bar_menu() {
		
		global $wp_admin_bar;
		
		if ( ! is_admin_bar_showing() || ( ! is_super_admin() && ( ! current_user_can( 'manage_options' ) ) && ! current_user_can( 'pmpro_memberslist' ) && ! current_user_can( 'e20r_memberslist' ) ) ) {
			if ( ! is_null( self::$instance ) ) {
				self::$instance->utils->log( "Unable to change admin bar (wrong capabilities for user)" );
			}
			
			return;
		}
		
		$wp_admin_bar->remove_menu( 'pmpro-members-list' );
		$wp_admin_bar->remove_node( 'pmpro-members-list' );
		
		//Add the (new) Members List page to the admin_bar menu
		$wp_admin_bar->add_menu( array(
			'id'     => 'e20r-members-list',
			'title'  => __( 'Members List', 'pmpro' ),
			'href'   => add_query_arg(
				'page',
				'e20r-memberslist',
				get_admin_url( get_current_blog_id(), 'admin.php' )
			),
			'parent' => 'paid-memberships-pro',
		) );
	}
	
	/**
	 * Screen Option option(s) for the Members List page.
	 *
	 * @param mixed  $value
	 * @param string $option
	 *
	 * @return mixed
	 */
	public static function set_screen( $status, $option, $value ) {
		
		self::$instance->utils->log( "Saving screen option (page: {$option})? {$value} vs {$status}" );
		
		if ( 'per_page' == $option ) {
			return $value;
		}
		
		return $status;
	}
	
	public function load_hooks() {
		
		$this->utils = Utilities::get_instance();
		
		// Filters
		add_filter( 'set-screen-option', array( $this, 'set_screen' ), 10, 3 );
		add_filter( 'set_url_scheme', array( $this, 'add_to_pagination' ), 10, 3 );
		// Actions
		add_action( 'admin_menu', array( $this, 'plugin_menu' ), 9999 );
		add_action( 'admin_init', 'E20R\Members_List\Admin\Export_Members::clear_temp_files' );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 9999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts_styles' ) );
	}
	
	/**
	 * Load the custom CSS and JavaScript for the Members List
	 */
	public function load_scripts_styles( $hook_suffix ) {
		
		if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || false == DOING_AJAX ) && 1 === preg_match( '/e20r-memberslist/', $hook_suffix ) ) {
			
			wp_enqueue_style( 'jquery-ui', '//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			
			wp_enqueue_style( 'e20r-memberslist-page', plugins_url( "/css/e20r-memberslist-page.css", __FILE__ ), array( 'pmpro_admin' ), E20R_MEMBERSLIST_VER );
			
			wp_register_script( 'e20r-memberslist-page', plugins_url( "/js/e20r-memberslist-page.js", __FILE__ ), array( 'jquery' ), E20R_MEMBERSLIST_VER, true );
			
			wp_localize_script( 'e20r-memberslist-page', 'e20rml',
				array(
					'locale' => str_replace( '_', '-', get_locale() ),
					'url'    => add_query_arg( 'page', 'e20r-memberslist', admin_url( 'admin.php' ) ),
					'lang'   => array(
						'save_btn_text'    => __( 'Save Updates', 'e20r-members-list' ),
						'clearing_enddate' => __( "This action will clear the current membership end date/expiration date!", 'e20r-members-list' ),
					),
				)
			);
			
			wp_enqueue_script( 'e20r-memberslist-page' );
		}
	}
	
	/**
	 * (re)Load the Members List menu
	 */
	public function plugin_menu() {
		
		// Unhook old members list functionality
		if ( false == ( $page = remove_submenu_page( 'pmpro-membershiplevels', 'pmpro-memberslist' ) ) ) {
			
			$this->utils->log( "Unable to remove the default membership levels page!" );
			pmpro_setMessage( __( 'Error: Attempting to reassign member list menu', 'pmpro' ), 'error' );
		}
		
		// Load the (new) WP_Table_List based Members List
		$hook = add_submenu_page(
			'pmpro-membershiplevels',
			__( 'Members List', 'pmpro' ),
			__( 'Members List', 'pmpro' ),
			'manage_options',
			'e20r-memberslist',
			array( $this, 'memberslist_settings_page' )
		);
		
		// Add 'Screeen Options' when this page loads.
		if ( false !== $hook ) {
			
			add_action( "load-{$hook}", array( $this, 'screen_option' ), 9999 );
		} else {
			
			$this->utils->log( "Unable to load the replacement Members List page!" );
			pmpro_setMessage( __( "Unable to load Members List menu entry", "e20r-members-list" ), "error" );
		}
	}
	
	/**
	 * Add parameters to limit/include records to any members list page URI
	 *
	 * @param string $url
	 * @param string $scheme
	 * @param string $original_scheme
	 *
	 * @return string
	 */
	public function add_to_pagination( $url, $scheme, $original_scheme ) {
		
		$page = $this->utils->get_variable( 'page', '' );
		
		if ( 1 === preg_match( "/{$_SERVER['HTTP_HOST']}\/wp-admin\/admin.php\?page=e20r-memberslist/i", $url ) ) {
			
			$arg_list = array();
			
			$level = $this->utils->get_variable( 'level', '' );
			$find  = $this->utils->get_variable( 'find', '' );
			
			if ( ! empty( $level ) ) {
				$arg_list['level'] = $level;
			}
			
			if ( ! empty( $find ) ) {
				$arg_list['find'] = $find;
			}
			
			/**
			 * @filter e20r_memberslist_pagination_args - Add filtering to the URI (to preserve it for pagination ,etc)
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
		
		$options = 'per_page';
		
		$args = array(
			'label'   => _x( "Members per page", "members per page (screen options)", "e20r-members-list" ),
			'default' => 15,
			'option'  => $options,
		);
		
		add_screen_option( $options, $args );
		
		$this->member_list = new Members_List();
	}
	
	/**
	 * Load the e20rMembersList page content
	 */
	public function memberslist_settings_page() {
		
		global $pmpro_msg;
		global $pmpro_msgt;
		
		// TODO: Fix this to match required request variables.
		$search = $this->utils->get_variable( 'find', '' );
		$level  = $this->utils->get_variable( 'level', '' );
		
		echo pmpro_loadTemplate( 'admin_header', 'local', 'adminpages' );
		
		$search_array = apply_filters( 'e20r_memberslist_exportcsv_search_args', array(
				'action' => 'memberslist_csv',
				's'      => esc_attr( $search ),
				'l'      => esc_attr( $level ),
			)
		);
		
		$csv_url = add_query_arg(
			$search_array,
			get_admin_url( get_current_blog_id(), 'admin-ajax.php' )
		);
		
		$e20r_error_msgs   = $this->utils->get_message( 'error' );
		$e20r_warning_msgs = $this->utils->get_variable( 'warning' );
		$e20r_info_msgs    = $this->utils->get_variable( 'info' );
		
		if ( ! empty( $pmpro_msg ) ) { ?>

            <div id="pmpro_message" class="pmpro_message <?php esc_attr_e( $pmpro_msgt ); ?>">
				<?php esc_attr_e( $pmpro_msg ); ?>
            </div>
			<?php
		} else if ( ! empty( $e20r_error_msgs ) || ! empty( $e20r_warning_msgs ) || ! empty( $e20r_info_msgs ) ) {
			$this->utils->display_messages( 'backend' );
		}
		?>
        <div class="wrap e20r-pmpro-memberslist-page">
            <h1>
				<?php _e( "Members List", "pmpro" ); ?>
                <a href="<?php echo esc_url_raw( $csv_url ); ?>" class="page-title-action e20r-memberslist-export"
                   target="_blank"><?php _e( 'Export to CSV', 'pmpro' ); ?></a>
            </h1>
            <hr class="e20r-memberslist-hr"/>
            <h2 class="screen-reader-text"><?php _e( "Filter list of members", "e20r-members-list" ); ?></h2>
            <form method="post" id="posts-filter">
                <div class="e20r-search-arguments">
                    <p class="search-box float-left">
                        <input class="button" type="submit" value="<?php _e( 'Update List', 'pmpro' ); ?>"/>
                    </p>
                    <ul class="subsubsub">
                        <li>
							<?php _e( 'Show', 'e20r-members-list' ); ?>
                            <select name="level" id="e20r-pmpro-memberslist-levels">
                                <option value="active" <?php selected( '', $level ); ?>><?php _e( 'Active Members', 'pmpro' ); ?></option>
                                <option value="all" <?php selected( $level, 'all' ); ?>><?php _e( 'All Members', 'pmpro' ); ?></option>
								<?php
								
								$list = pmpro_getAllLevels( true, true );
								
								foreach ( $list as $item ) {
									?>
                                    <option value="<?php esc_attr_e( $item->id ); ?>" <?php selected( $level, $item->id ); ?>><?php esc_attr_e( $item->name ); ?></option>
									<?php
								}
								?>
                                <option value="cancelled" <?php selected( $level, 'cancelled' ); ?>><?php _e( 'Cancelled Members', 'pmpro' ); ?></option>
                                <option value="expired" <?php selected( $level, "expired" ); ?>><?php _e( 'Expired Members', 'pmpro' ); ?></option>
                                <option value="oldmembers" <?php selected( $level, "oldmembers" ); ?>><?php _e( 'Old Members', 'pmpro' ); ?></option>
                            </select>
                        </li>
						<?php do_action( 'e20r_memberslist_addl_search_options', $search, $level ); ?>
                    </ul>
                    <p class="search-box float-right">
                        <label class="hidden" for="post-search-input"><?php _e( 'Search', 'pmpro' ); ?>:</label>
                        <input type="hidden" name="page" value="e20r-memberslist"/>
                        <input id="post-search-input" type="text" value="<?php esc_attr_e( $search ); ?>" name="find"/>
                        <input class="button" type="submit" value="<?php _e( 'Search Members', 'pmpro' ); ?>"/>
                    </p>
                </div>
                <h2 class="screen-reader-text"><?php _e( 'Member list', 'e20r-members-list' ); ?></h2>
                <hr class="e20r-memberslist-hr"/>
				<?php
				$this->member_list->prepare_items();
				$this->member_list->display();
				?>
            </form>
        </div>
		
		<?php
		echo pmpro_loadTemplate( 'admin_footer', 'local', 'adminpages' );
	}
	
}