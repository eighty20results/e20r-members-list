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
 * @package E20R\Members_List\Admin\Bulk\Bulk_Cancel
 */

namespace E20R\Members_List\Admin\Bulk;

use E20R\Utilities\Message;
use E20R\Utilities\Utilities;

if ( ! defined( 'ABSPATH' ) && ! defined( 'PLUGIN_PHPUNIT' ) ) {
	die( 'WordPress not loaded. Naughty, naughty!' );
}

if ( ! class_exists( '\\E20R\\Members_List\\Admin\\Bulk\\Bulk_Cancel' ) ) {

	/**
	 * The bulk cancel operation handler
	 */
	class Bulk_Cancel {

		/**
		 * Instance of this class
		 *
		 * @var null|Bulk_Cancel
		 */
		private static $instance = null;

		/**
		 * Update operation to perform
		 *
		 * @var null|string
		 */
		private $operation = null;

		/**
		 * Array of members to update where the memebr date is represented as an array per member
		 *
		 * @var array[]|int[]|null
		 */
		private $members_to_update = array();

		/**
		 * Instance of the E20R Utilities Module class
		 *
		 * @var Utilities|null $utils
		 */
		private $utils = null;

		/**
		 * Bulk_Cancel constructor (singleton)
		 *
		 * @param array[]|int[]|null $members_to_update The array of member IDs to perform the bulk cancel operation against
		 * @param Utilities|null     $utils             Instance of the E20R Utilities Module class
		 *
		 * @access public
		 */
		public function __construct( $members_to_update = array(), $utils = null ) {

			if ( empty( $utils ) ) {
				$message = new Message();
				$utils   = new Utilities( $message );
			}

			$this->utils = $utils;

			if ( ! empty( $members_to_update ) ) {
				$this->members_to_update = $members_to_update;
			}

			self::$instance = $this;
		}

		/**
		 * Process cancellations for all members/membership_ids
		 */
		public function cancel() {

			// Process all User & level ID for the single action.
			$failed = array();

			$this->utils->log( 'Cancelling ' . count( $this->members_to_update ) . ' members' );

			// Process all selected records/members
			foreach ( $this->members_to_update as $key => $cancel_info ) {

				if ( false === $this->cancel_member( $cancel_info['user_id'], $cancel_info['level_id'] ) ) {
					$failed[] = $cancel_info['user_id']; // FIXME: Add level info for multiple membership levels
				}
			}

			// Check for errors & display error banner if we got one.
			if ( ! empty( $failed ) ) {

				$message = sprintf(
				// translators: %1$s List of User IDs
					esc_attr__(
						'Unable to cancel membership(s) for the following user IDs: %1$s',
						'e20r-members-list'
					),
					implode( ', ', $failed )
				);

				$this->utils->add_message( $message, 'error', 'backend' );

				if ( function_exists( 'pmpro_setMessage' ) ) {
					pmpro_setMessage( $message, 'error' );
				} else {
					global $msg;
					global $msgt;

					$msg  = $message;
					$msgt = 'error';
				}

				return false;
			}

			return true;
		}

		/**
		 * The cancel member action
		 *
		 * @param int      $id       User ID
		 * @param int|null $level_id Level ID
		 *
		 * @return bool
		 */
		public static function cancel_member( $id, $level_id = null ) {

			if ( function_exists( 'pmpro_cancelMembershipLevel' ) ) {
				return pmpro_cancelMembershipLevel( $level_id, $id, 'admin_cancelled' );
			} else {
				return false;
			}

		}

		/**
		 * Get or create an instance of the Bulk_Cancel class
		 *
		 * @return Bulk_Cancel|null
		 */
		public static function get_instance() {

			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Set the list of members & their levels to update
		 *
		 * @param array $member_info The array of members we intend to process.
		 */
		public function set_members( $member_info = array() ) {
			$this->members_to_update = $member_info;
		}

		/**
		 * Return the list of members being updated
		 *
		 * @return array
		 */
		public function get_members() {
			return $this->members_to_update;
		}
	}
}
