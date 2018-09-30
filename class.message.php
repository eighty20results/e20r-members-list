<?php
/**
 *
 * Copyright (c) 2018. - Eighty / 20 Results by Wicked Strong Chicks.
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
 */

namespace E20R\Utilities;

class Message {
	
	/**
	 * Constant indicating the WP Front-end page(s)
	 */
	const FRONTEND_LOCATION = 1000;
	
	/**
	 * Constant indicating the WP back-end pages (wp-admin dashboard, etc)
	 */
	const BACKEND_LOCATION = 1;
	
	/**
	 * Constant indicating the default location (wp-admin, I presume)
	 */
	const DEFAULT_LOCATION = 0;
	
	/**
	 * List of front or backend messages
	 *
	 * @var string[]
	 */
	private $msg = array();
	
	/**
	 * List of front/backend message types (valid: 'error', 'warning', 'info'
	 *
	 * @var string[]
	 */
	private $msgt = array();
	
	/**
	 * List of location to display the messages/message types
	 *
	 * @var int[]
	 */
	private $location = array();
	
	/**
	 * Message constructor.
	 *
	 * @param string $message
	 * @param string $type
	 * @param string $location
	 */
	public function __construct( $message = null, $type = 'notice', $location = 'backend' ) {
		
		// Not adding a message
		if ( empty( $message ) ) {
			return;
		}
		
		$utils     = Utilities::get_instance();
		$cache_key = $utils->get_util_cache_key();
		
		
		if ( null !== ( $tmp = Cache::get( 'err_info', $cache_key ) ) ) {
			
			$utils->log( "Loading cached messages (" . count( $tmp['msg'] ) . ")" );
			
			$this->msg      = $tmp['msg'];
			$this->msgt     = $tmp['msgt'];
			$this->location = $tmp['location'];
		}
		
		switch ( trim( $location ) ) {
			case 'backend':
				$location = self::BACKEND_LOCATION;
				break;
			
			case 'frontend':
				$location = self::FRONTEND_LOCATION;
				break;
			
			default:
				$location = self::DEFAULT_LOCATION;
		}
		
		$msg_found = array();
		
		// Look for duplicate messages
		foreach ( $this->msg as $key => $msg ) {
			
			if ( ! empty( $message ) && ! empty( $msg ) && false !== strpos( $message, $msg ) ) {
				$msg_found[] = $key;
			}
		}
		
		// No duplicates found, so add the new one
		if ( empty( $msg_found ) ) {
			// Save new message
			$utils->log( "Adding a message to the admin errors: {$message}" );
			
			$this->msg[]      = $message;
			$this->msgt[]     = $type;
			$this->location[] = $location;
		} else {
			
			// Potentially clean up duplicate messages
			$total = count( $msg_found );
			
			// Remove extra instances of the message
			for ( $i = 1; ( $total - 1 ) >= $i; $i ++ ) {
				$utils->log( "Removing duplicate message" );
				unset( $this->msg[ $i ] );
			}
		}
		
		// Update the cached values
		if ( ! empty ( $this->msg ) ) {
			$this->updateCache();
		}
	}
	
	/**
	 * Update the cached error/warning/notice messages
	 */
	private function updateCache() {
		
		$utils     = Utilities::get_instance();
		$cache_key = $utils->get_util_cache_key();
		
		$values = array(
			'msg'        => $this->msg,
			'msgt'       => $this->msgt,
			'msg_source' => $this->location,
		);
		
		$utils->log("Caching the error/info/warning messages");
		Cache::set( 'err_info', $values, DAY_IN_SECONDS, $cache_key );
	}
	
	/**
	 * Display the error/warning/notice messages in the appropriate destination
	 *
	 * @param $destination
	 */
	public function display( $destination ) {
		
		$utils     = Utilities::get_instance();
		$cache_key = $utils->get_util_cache_key();
		
		// Load from cache if there are no messages found
		if ( empty( $this->msg ) ) {
			
			$msgs = Cache::get( 'err_info', $cache_key );
			
			if ( ! empty( $msgs ) ) {
				$this->msg      = $msgs['msg'];
				$this->msgt     = $msgs['msgt'];
				$this->location = $msgs['location'];
			}
		}
		
		if ( ! empty( $this->msg ) && ! empty( $this->msgt ) ) {
			
			$found_keys = $this->extractByDestination( $destination );
			
			$utils->log( "Have a total of " . count( $this->msg ) . " admin message(s) to display" );
			
			foreach ( $found_keys as $key ) {
				
				if ( ! empty( $this->msg[ $key ] ) ) {
					
					switch ( $this->location[ $key ] ) {
						
						case self::FRONTEND_LOCATION:
							$this->displayFrontend( $this->msg[ $key ], $this->msgt[ $key ] );
							break;
						
						case self::BACKEND_LOCATION:
							
							$this->displayBackend( $this->msg[ $key ], $this->msgt[ $key ] );
							break;
						
						default:
							
							global $msg;
							global $msgt;
							
							$msg  = $this->msg[ $key ];
							$msgt = $this->msgt[ $key ];
					}
					
					unset( $this->msg[ $key ] );
					unset( $this->msgt[ $key ] );
					unset( $this->location[ $key ] );
				}
			}
		}
		
		if ( ! empty( $this->msg ) ) {
			
			$this->updateCache();
		} else {
			Cache::delete( 'err_info', $cache_key );
		}
	}
	
	/**
	 * Return list of message keys that match the specified destination
	 *
	 * @param $destination
	 *
	 * @return array
	 */
	private function extractByDestination( $destination ) {
		
		$keys = array();
		
		foreach ( $this->location as $msg_key => $location ) {
			
			if ( 1 === preg_match( '/' . preg_quote( $destination ) . '/', $location ) ) {
				$keys[] = $msg_key;
			}
		}
		
		return $keys;
	}
	
	/**
	 * Display on the front-end of the site (if using WooCommerce or PMPro)
	 *
	 * @param string $msg
	 * @param int    $type
	 */
	private function displayFrontend( $msg, $type ) {
		
		if ( $this->hasWooCommerce() ) {
			
			if ( is_checkout() || is_cart() || is_account_page() ) {
				wc_add_notice( $msg, $type );
			}
		}
		
		if ( $this->hasPMPro() ) {
			
			global $pmpro_pages;
			
			if ( is_page( $pmpro_pages ) ) {
				
				global $pmpro_msg;
				global $pmpro_msgt;
				
				$pmpro_msg  = $msg;
				$pmpro_msgt = "pmpro_{$type}";
				
				pmpro_setMessage( $pmpro_msg, $pmpro_msgt, true );
			}
		}
	}
	
	/**
	 * WooCommerce is installed and active
	 *
	 * @return bool
	 */
	private function hasWooCommerce() {
		return function_exists( 'wc_add_notice' );
	}
	
	/**
	 * PMPro is installed and active
	 *
	 * @return bool
	 */
	private function hasPMPro() {
		return function_exists( 'pmpro_getAllLevels' );
	}
	
	/**
	 * Display in WP Admin (the backend)
	 *
	 * @param string $msg
	 * @param string $type
	 *
	 */
	private function displayBackend( $msg, $type ) {
		
		if ( ! Utilities::is_admin() ) {
			return;
		} ?>
		<div
			class="notice notice-<?php esc_html_e( $type ); ?> is-dismissible backend">
			<p><?php esc_html_e( wp_unslash( $msg ) ); ?></p>
		</div>
		<?php
	}
	
	/**
	 * Return all messages of a specific type
	 *
	 * @param $type
	 *
	 * @return string[]
	 */
	public function get( $type ) {
		
		$messages  = array();
		$utils     = Utilities::get_instance();
		$cache_key = $utils->get_util_cache_key();
		
		// Grab from the cache (if it exists)
		if ( null !== ( $tmp = Cache::get( 'err_info', $cache_key ) ) ) {
			
			$this->msg      = $tmp['msg'];
			$this->msgt     = $tmp['msgt'];
			$this->location = $tmp['location'];
		}
		
		$messages = array();
		
		foreach ( $this->msgt as $message_key => $message_type ) {
			
			if ( $message_type === $type ) {
				$messages[] = $this->msg[ $message_key ];
			}
		}
		
		return $messages;
		
	}
}
