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

use E20R\Members_List\Admin\Exceptions\InvalidMemberList;
use E20R\Members_List\Admin\Exceptions\InvalidProperty;
use E20R\Members_List\Admin\Exceptions\PMProNotActive;
use E20R\Utilities\Message;
use E20R\Utilities\Utilities;
use function pmpro_cancelMembershipLevel;

if ( ! defined( 'ABSPATH' ) && ! defined( 'PLUGIN_PHPUNIT' ) ) {
	die( 'WordPress not loaded. Naughty, naughty!' );
}

if ( ! class_exists( 'E20R\Members_List\Admin\Bulk\Bulk_Cancel' ) ) {

	/**
	 * The bulk cancel operation handler
	 */
	class Bulk_Cancel extends Bulk_Operations {

		/**
		 * Bulk_Cancel constructor (singleton)
		 *
		 * @param array[]|null   $members_to_update The array of member IDs to perform the bulk cancel operation against
		 * @param Utilities|null $utils Instance of the E20R Utilities Module class
		 *
		 * @access public
		 * @throws InvalidProperty Thrown when the class or base class lacks the specified set() property
		 * @throws InvalidMemberList Thrown when the supplied array isn't null or has the wrong format
		 */
		public function __construct( $members_to_update = null, $utils = null ) {

			if ( empty( $utils ) ) {
				$message = new Message();
				$utils   = new Utilities( $message );
			}

			parent::__construct( $members_to_update, $utils );

			$this->set( 'operation', 'cancel' );
		}

		/**
		 * Process cancellations for all members/membership_ids
		 *
		 * @return bool
		 * @throws InvalidProperty Thrown if 'members_to_update' has been removed as a property from the Bulk_*() classes
		 */
		public function execute() {

			if ( ! $this->pmpro_is_active() ) {
				return false;
			}

			// Process all User & level ID for the single action.
			$this->failed = array();
			$this->utils->log( 'Cancelling ' . count( $this->members_to_update ) . ' members' );

			// Process all selected records/members
			foreach ( $this->members_to_update as $key => $cancel_info ) {
				if ( false === $this->cancel_member( $cancel_info['user_id'], $cancel_info['level_id'] ) ) {
					if ( ! isset( $this->failed[ $cancel_info['user_id'] ] ) ) {
						$this->failed[ $cancel_info['user_id'] ] = array();
					}
					$this->failed[ $cancel_info['user_id'] ][] = $cancel_info['level_id']; // FIXME: Add level info for multiple membership levels
				}
			}

			/**
			 * Trigger action for bulk Cancel (allows external handling of bulk cancel operation if needed/desired)
			 *
			 * @action e20r_memberslist_process_bulk_cancel_done
			 *
			 * @param array[] $members_to_update - List of list of user ID's and level IDs for the selected bulk-update users
			 */
			do_action( "e20r_memberslist_process_bulk_{$this->operation}_done", $this, $this->get( 'members_to_update' ) );

			// Check for errors & display error banner if we got one.
			if ( ! empty( $this->failed ) ) {
				foreach ( $this->failed as $user_id => $level_ids ) {
					if ( empty( $level_ids ) ) {
						continue;
					}
					$message = sprintf(
						// translators: %1$s User ID, %2$s List of level(s) where the cancel operation failed
						esc_attr__(
							'Unable to cancel the following membership levels for user (ID: %1$s): %2$s',
							'e20r-members-list'
						),
						$user_id,
						implode( ', ', $level_ids )
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
		 *
		 * @throws PMProNotActive Thrown if the Paid Memberships Pro plugin is not installed and active
		 */
		public function cancel_member( $id, $level_id = null ) {
			if ( ! function_exists( 'pmpro_cancelMembershipLevel' ) ) {
				throw new PMProNotActive(
					esc_attr__(
						'The pmpro_cancelMembershipLevel() function is not defined. Is Paid Memberships Pro activated on this site?',
						'e20r-members-list'
					)
				);
			}
			$this->utils->log( "Cancelling membership level {$level_id} for user {$id}" );
			return pmpro_cancelMembershipLevel( $level_id, $id, 'admin_cancelled' );
		}

		/**
		 * Check if PMPro is active and throw exception if it isn't
		 *
		 * @return bool
		 */
		private function pmpro_is_active() {
			return function_exists( 'pmpro_cancelMembershipLevel' );
		}
	}
}
