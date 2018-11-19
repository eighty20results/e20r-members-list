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

if ( ! class_exists( '\E20R\Utilities\Message' ) ) {
	
	/**
	 * Class Message
	 * @package E20R\Utilities
	 */
	class Message {
		
		/**
		 * Constant indicating the WP Front-end page(s)
		 */
		const FRONTEND_LOCATION = 1000;
		
		/**
		 * Constant indicating the WP back-end pages (wp-admin dashboard, etc)
		 */
		const BACKEND_LOCATION = 2000;
		
		/**
		 * Constant indicating the default location (wp-admin, I presume)
		 */
		const DEFAULT_LOCATION = 3000;
		
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
				
				$this->msg      = isset( $tmp['msg'] ) ? $tmp['msg'] : array();
				$this->msgt     = $tmp['msgt'] ? $tmp['msgt'] : array();
				$this->location = isset( $tmp['location'] ) ? $tmp['location'] : ( isset( $tmp['msgt_source'] ) ? $tmp['msgt_source'] : array() );
			}
			
			$location = $this->convertDestination( $location );
			
			$msg_found = array();
			
			// Look for duplicate messages
			foreach ( $this->msg as $key => $msg ) {
				
				if ( ! empty( $message ) && ! empty( $msg ) && false !== strpos( $message, $msg ) ) {
					$msg_found[] = $key;
				}
				
				// Fix bad location values
				if ( ! empty( $location ) ) {
					$this->location[ $key ] = $this->convertDestination( $location );
				}
			}
			
			// No duplicates found, so add the new one
			if ( empty( $msg_found ) ) {
				// Save new message
				$utils->log( "Adding a message to the list: {$message}" );
				
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
		 * Return the correct Destination constant value
		 *
		 * @param string $destination
		 *
		 * @return int
		 */
		private function convertDestination( $destination ) {
			
			if ( is_numeric( $destination ) ) {
				return $destination;
			}
			
			switch ( trim( strtolower( $destination ) ) ) {
				
				case 'backend':
					$destination = self::BACKEND_LOCATION;
					break;
				case 'frontend':
					$destination = self::FRONTEND_LOCATION;
					break;
				
				default:
					$destination = self::BACKEND_LOCATION;
			}
			
			return $destination;
		}
		
		/**
		 * Update the cached error/warning/notice messages
		 */
		private function updateCache() {
			
			$utils     = Utilities::get_instance();
			$cache_key = $utils->get_util_cache_key();
			
			$values = array(
				'msg'      => $this->msg,
				'msgt'     => $this->msgt,
				'location' => $this->location,
			);
			
			Cache::set( 'err_info', $values, DAY_IN_SECONDS, $cache_key );
		}
		
		/**
		 * Minimize duplication of WooCommerce alert messages
		 *
		 * @param null|bool $passthrough
		 *
		 * @return bool
		 */
		public function clearNotices( $passthrough = null ) {
			
			wc_clear_notices();
			
			return $passthrough;
		}
		
		/**
		 * Display the error/warning/notice messages in the appropriate destination
		 *
		 * @param string|null $destination
		 */
		public function display( $destination = null ) {
			
			if ( ! is_string( $destination ) ) {
				$destination = null;
			}
			
			$utils     = Utilities::get_instance();
			$cache_key = $utils->get_util_cache_key();
			
			global $pmpro_pages;
			
			// Load from cache if there are no messages found
			if ( empty( $this->msg ) ) {
				
				$msgs = Cache::get( 'err_info', $cache_key );
				
				if ( ! empty( $msgs ) ) {
					$this->msg      = $msgs['msg'];
					$this->msgt     = $msgs['msgt'];
					$this->location = $msgs['location'];
				}
			}
			
			if ( empty( $this->msg ) ) {
				return;
			}
			
			if ( empty( $destination ) && Utilities::is_admin() ) {
				$destination = self::BACKEND_LOCATION;
			}
			
			if ( empty( $destination ) && ( false === Utilities::is_admin() || is_page( $pmpro_pages ) || is_account_page() || is_cart() || is_checkout() ) ) {
				$destination = self::FRONTEND_LOCATION;
			}
			
			$found_keys = $this->extractByDestination( $this->convertDestination( $destination ) );
			
			$utils->log( "Have a total of " . count( $this->msg ) . " message(s). Found " . count( $found_keys ) . " messages for location {$destination}: " );
			
			foreach ( $found_keys as $key ) {
				
				if ( empty( $this->location ) || ! isset( $this->location[ $key ] ) ) {
					$location = self::BACKEND_LOCATION;
				} else {
					$location = $this->location[ $key ];
					unset( $this->location[ $key ] );
				}
				
				if ( ! empty( $this->msg[ $key ] ) ) {
					
					switch ( intval( $location ) ) {
						
						case self::FRONTEND_LOCATION:
							$utils->log( "Showing on front-end of site" );
							$this->displayFrontend( $this->msg[ $key ], $this->msgt[ $key ] );
							break;
						
						case self::BACKEND_LOCATION:
							$utils->log( "Showing on back-end of site" );
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
		 * @param int $destination
		 *
		 * @return array
		 */
		private function extractByDestination( $destination ) {
			
			$keys = array();
			
			foreach ( $this->location as $msg_key => $location ) {
				
				if ( $location == $destination ) {
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
		private function displayFrontend( $message, $type ) {
			
			if ( $this->hasWooCommerce() ) {
				
				Utilities::get_instance()->log( "Attempting to show on WooCommerce front-end" );
				wc_add_notice( $message, $type );
			}
			
			if ( $this->hasPMPro() ) {
				
				Utilities::get_instance()->log( "Attempting to show {$message} on PMPro front-end" );
				
				global $pmpro_msg;
				global $pmpro_msgt;
				global $msg;
				global $msgt;
				
				$pmpro_msg  = $message;
				$pmpro_msgt = "pmpro_{$type}";
				$msg        = $pmpro_msg;
				$msgt       = $pmpro_msgt;
				
				pmpro_setMessage( $pmpro_msg, $pmpro_msgt, true );
				$this->addPMProMessage( $pmpro_msg, $pmpro_msgt );
			}
		}
		
		/**
		 * Passthrough for some of the PMPro filters so we can display error message(s) on the
		 *
		 * @param mixed $arg1
		 * @param mixed $arg2
		 * @param mixed $arg3
		 * @param mixed $arg4
		 * @param mixed $arg5
		 * @param mixed $arg6
		 *
		 * @return mixed
		 */
		public function filter_passthrough( $arg1 = null, $arg2 = null, $arg3 = null, $arg4 = null, $arg5 = null, $arg6 = null ) {
			
			$utils = Utilities::get_instance();
			
			global $pmpro_pages;
			global $post;
			
			$page_list = array(
				$pmpro_pages['billing'],
				$pmpro_pages['account'],
			);
			
			if ( ! isset( $post->post_content ) || ( isset( $post->post_content ) && ! is_page( $page_list ) ) ) {
				
				$utils->log( "Not on billing or account shortcode/page" );
				
				return $arg1;
			}
			
			$utils->log( "Loading error messages for account/billing page: {$post->ID}" );
			$this->display( self::FRONTEND_LOCATION );
			
			return $arg1;
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
		 * Display the PMPro error message(s)
		 *
		 * @param string $message
		 * @param string $message_type
		 */
		public function addPMProMessage( $message, $message_type ) {
			
			Utilities::get_instance()->log( "Adding for PMPro page" );
			
			if ( ! empty( $message ) ) {
				printf( '<div id="pmpro_message" class="pmpro_message %s">%s</div>', $message_type, $message );
			}
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
				<p><?php echo wp_unslash( $msg ); ?></p>
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
}
