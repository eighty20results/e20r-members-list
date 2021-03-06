<?php
/**
 * Copyright (c) 2018-2021 - Eighty / 20 Results by Wicked Strong Chicks.
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

use E20R\Members_List\Controller\E20R_Members_List;
use E20R\Utilities\Utilities;

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
	 * List of member IDs to update
	 *
	 * @var array|int[]
	 */
	private $members_to_update = array();

	/**
	 * Bulk_Cancel constructor (singleton)
	 *
	 * @access private
	 */
	private function __construct() {
	}

	/**
	 * The __clone() method for Bulk_Cancel() (singleton class)
	 *
	 * @access private
	 */
	private function __clone() {}

	/**
	 * Process cancellations for all members/membership_ids
	 */
	public function cancel() {

		// Process all User & level ID for the single action.
		$failed = array();

		$utils = Utilities::get_instance();
		$utils->log( 'Cancelling ' . count( $this->members_to_update ) . ' members' );

		// Process all selected records/members
		foreach ( $this->members_to_update as $key => $cancel_info ) {

			if ( false === $this->cancel_member( $cancel_info['user_id'], $cancel_info['level_id'] ) ) {
				$failed[] = $cancel_info['user_id']; // FIXME: Add level info for multiple membership levels
			}
		}

		//Check for errors & display error banner if we got one.
		if ( ! empty( $failed ) ) {

			$message = sprintf(
				// translators: %1$s List of User IDs
				__(
					'Unable to cancel membership(s) for the following user IDs: %1$s',
					'e20r-members-list'
				),
				implode( ', ', $failed )
			);

			if ( function_exists( 'pmpro_setMessage' ) ) {
				pmpro_setMessage( $message, 'error' );
			} else {
				global $msg;
				global $msgt;

				$msg  = $message;
				$msgt = 'error';
			}
		}
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
	 * @param array $member_info
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
