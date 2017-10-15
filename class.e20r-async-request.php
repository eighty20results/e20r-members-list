<?php
/**
 * Copyright 2017 Eighty / 20 Results by Wicked Strong Chicks, LLC (thomas@eighty20results.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * Thanks to @A5hleyRich at https://github.com/A5hleyRich/wp-background-processing
 */

namespace E20R\Utilities;

/**
 * WP Async Request
 *
 * @package WP-Background-Processing
 *
 * @credit https://github.com/A5hleyRich/wp-background-processing
 * @since   1.9.6 - ENHANCEMENT: Added fixes and updates from EWWW Image Optimizer code
 */
use E20R\Utilities\Utilities;

/**
 * Abstract E20R_Async_Request class.
 *
 * @abstract
 */
abstract class E20R_Async_Request {
	/**
	 * Prefix
	 *
	 * (default value: 'wp')
	 *
	 * @var string
	 * @access protected
	 */
	protected $prefix = 'e20r';
	/**
	 * Action
	 *
	 * (default value: 'async_request')
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'async_request';
	/**
	 * Identifier
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $identifier;
	/**
	 * Data
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access protected
	 */
	protected $data = array();
	
	protected $query_args;
	
	protected $query_url;
	
	protected $post_args;
	
	/**
	 * Initiate new async request
	 */
	public function __construct() {
		$this->identifier = $this->prefix . '_' . $this->action;
		add_action( 'wp_ajax_' . $this->identifier, array( $this, 'maybe_handle' ) );
		add_action( 'wp_ajax_nopriv_' . $this->identifier, array( $this, 'maybe_handle' ) );
	}
	
	/**
	 * Set data used during the request
	 *
	 * @param array $data Data.
	 *
	 * @return $this
	 */
	public function data( $data ) {
		$this->data = $data;
		
		return $this;
	}
	
	/**
	 * Dispatch the async request
	 *
	 * @return array|\WP_Error
	 */
	public function dispatch() {
		$url  = esc_url( add_query_arg( $this->get_query_args(), $this->get_query_url() ) );
		$args = $this->get_post_args();
		
		return wp_remote_post( esc_url_raw( $url ), $args );
	}
	
	/**
	 * Get query args
	 *
	 * @return array
	 */
	protected function get_query_args() {
		if ( property_exists( $this, 'query_args' ) ) {
			return $this->query_args;
		}
		
		return array(
			'action' => $this->identifier,
			'nonce'  => wp_create_nonce( $this->identifier ),
		);
	}
	
	/**
	 * Get query URL
	 *
	 * @return string
	 */
	protected function get_query_url() {
		if ( property_exists( $this, 'query_url' ) ) {
			return $this->query_url;
		}
		
		return admin_url( 'admin-ajax.php' );
	}
	
	/**
	 * Get post args
	 *
	 * @return array
	 */
	protected function get_post_args() {
		if ( property_exists( $this, 'post_args' ) ) {
			return $this->post_args;
		}
		
		return array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'body'      => $this->data,
			'cookies'   => $_COOKIE,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
		);
	}
	
	/**
	 * Maybe handle
	 *
	 * Check for correct nonce and pass to handler.
	 */
	public function maybe_handle() {
		
		$utils = Utilities::get_instance();
		
		// Don't lock up other requests while processing
		session_write_close();
		check_ajax_referer( $this->identifier, 'nonce' );
		
		$this->handle();
		
		$utils->log( "Terminating for single request" );
		wp_die();
	}
	
	/**
	 * Handle
	 *
	 * Override this method to perform any actions required
	 * during the async request.
	 */
	abstract protected function handle();
}
