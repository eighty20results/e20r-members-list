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

use E20R\Members_List\Admin\Exceptions\InvalidProperty;
use E20R\Members_List\Admin\Exceptions\PMProNotActive;
use E20R\Utilities\Message;
use E20R\Utilities\Utilities;

if ( ! defined( 'ABSPATH' ) && ! defined( 'PLUGIN_PHPUNIT' ) ) {
	die( 'WordPress not loaded. Naughty, naughty!' );
}

if ( ! class_exists( '\\E20R\\Members_List\\Admin\\Bulk\\Bulk_Cancel' ) ) {

	/**
	 * The bulk cancel operation handler
	 */
	class Bulk_Cancel extends Bulk_Operations {

		/**
		 * Bulk_Cancel constructor (singleton)
		 *
		 * @param array[]|int[]|null $members_to_update The array of member IDs to perform the bulk cancel operation against
		 * @param Utilities|null     $utils Instance of the E20R Utilities Module class
		 *
		 * @access public
		 * @throws InvalidProperty Thrown when the class or base class lacks the specified set() property
		 */
		public function __construct( $members_to_update = array(), $utils = null ) {

			if ( empty( $utils ) ) {
				$message = new Message();
				$utils   = new Utilities( $message );
			}

			parent::__construct( $utils );

			$this->set( 'operation', 'cancel' );
			$this->set( 'members_to_update', $members_to_update );
		}

		/**
		 * Process cancellations for all members/membership_ids
		 */
		public function execute() {

			// Process all User & level ID for the single action.
			$this->failed = array();
			$this->utils->log( 'Cancelling ' . count( $this->members_to_update ) . ' members' );

			// Process all selected records/members
			foreach ( $this->members_to_update as $key => $cancel_info ) {
				try {
					if ( false === $this->cancel_member( $cancel_info['user_id'], $cancel_info['level_id'] ) ) {
						if ( ! is_array( $this->failed[ $cancel_info['user_id'] ] ) ) {
							$this->failed[ $cancel_info['user_id'] ] = array();
						}
						$this->failed[ $cancel_info['user_id'] ][] = $cancel_info['level_id']; // FIXME: Add level info for multiple membership levels
					}
				} catch ( PMProNotActive $e ) {
					$this->utils->add_message( $e->getMessage(), 'error', 'backend' );
					$this->failed[] = $cancel_info['user_id'];
					return false;
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
					$message = sprintf(
						// translators: %1$s User ID, %2$s List of level(s) where the cancel operation failed
						esc_attr__(
							'Unable to cancel the following membership levels for user (ID: %1$s): %2$s',
							'e20r-members-list'
						),
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
						'Cannot find the pmpro_cancelMembershipLevel() function!',
						'e20r-members-list'
					)
				);
			}
			$this->utils->log( "Cancelling membership level {$id} for user {$id}" );
			return \pmpro_cancelMembershipLevel( $level_id, $id, 'admin_cancelled' );
		}
	}
}
