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

class Bulk_Update {

	/**
	 * Instance of this class
	 *
	 * @var null|Bulk_Update
	 */
	private static $instance = null;

	/**
	 * Update operation to perform
	 *
	 * @var null|string
	 */
	private $operation = null;

	/**
	 * List of members to update
	 *
	 * @var array[]
	 */
	private $members_to_update = array();

	/**
	 * Bulk_Update constructor (singleton)
	 *
	 * @access private
	 */
	private function __construct() {
	}

	/**
	 * __clone() method for Bulk_Update() (singleton class)
	 *
	 * @access private
	 */
	private function __clone(){}

	/**
	 * Get or create an instance of the Bulk_Update class
	 *
	 * @return Bulk_Update|null
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Handle bulk update (for core member list columns). Triggers action for external bulk update activities/fields
	 *
	 * @return bool
	 */
	public function update() {

		$utils         = Utilities::get_instance();
		$update_errors = array();
		$level_failed  = array();

		$utils->log( 'User count to update: ' . count( $this->members_to_update ) );
		$utils->log( 'Request: ' . print_r( $_REQUEST, true ) );

		/**
		 * Process build-in edit fields for the specified members to update
		 */
		foreach ( $this->members_to_update as $key => $user_info ) {

			$level_changed  = false;
			$old_user_level = $utils->get_variable(
				"e20r-members-list-db_membership_id_{$user_info['user_id']}",
				0
			);
			$new_user_level = $utils->get_variable(
				"e20r-members-list-new_membership_id_{$user_info['user_id']}",
				0
			);
			$record_id      = $utils->get_variable(
				"e20r-members-list-db_record_id_{$user_info['user_id']}",
				0
			);

			if ( empty( $user_info['user_id'] ) ) {
				$utils->log( 'User ID is NULL. Returning!' );
				return false;
			}

			$utils->log( "Have to update level for {$user_info['user_id']}? {$new_user_level}" );

			// Update the membership level for the user we're processing
			if ( ! empty( $new_user_level ) && $old_user_level !== $new_user_level ) {

				if ( false === $this->update_membership( $user_info['user_id'], $old_user_level, $new_user_level ) ) {

					// Add to list of failed updates
					$level_failed[] = array(
						'user_id'   => $user_info['user_id'],
						'new_level' => $new_user_level,
						'old_level' => $old_user_level,
					);
				} else {
					// Used by subsequen update operations (start/end date)
					$level_changed = true;
				}
			}

			$old_startdate = $utils->get_variable(
				"e20r-members-list-db_startdate_{$user_info['user_id']}",
				''
			);
			$new_startdate = $utils->get_variable(
				"e20r-members-list-new_startdate_{$user_info['user_id']}",
				''
			);

			$utils->log(
				"Have to update start date for {$user_info['user_id']}? N:{$new_startdate} vs O:{$old_startdate}"
			);

			if ( $old_startdate !== $new_startdate ) {

				if ( empty( $new_startdate ) ) {
					$utils->log( 'Error: Start date cannot be empty!, using old start date' );
					$new_startdate = $old_startdate;
				}

				if ( false === $this->update_date(
					'startdate',
					$user_info['user_id'],
					$old_user_level,
					$new_user_level,
					$new_startdate,
					$record_id,
					$level_changed
				)
				) {
					$startdate_failed[] = array(
						'user_id'  => $user_info['user_id'],
						'old_date' => $old_startdate,
						'new_date' => $new_startdate,
						'level_id' => ( $level_changed ? $new_user_level : $old_user_level ),
					);
				}
			}

			$old_enddate = $utils->get_variable(
				"e20r-members-list-db_enddate_{$user_info['user_id']}",
				''
			);
			$new_enddate = $utils->get_variable(
				"e20r-members-list-new_enddate_{$user_info['user_id']}",
				''
			);

			$utils->log( "Have to update end date for {$user_info['user_id']}? N:{$new_enddate} vs O:{$old_enddate}" );

			if ( $old_enddate !== $new_enddate ) {

				$utils->log( "Updating end date to {$new_enddate}" );

				if ( false === $this->update_date(
					'enddate',
					$user_info['user_id'],
					$old_user_level,
					$new_user_level,
					$new_enddate,
					$record_id,
					$level_changed
				)
				) {
					$enddate_failed[] = array(
						'user_id'  => $user_info['user_id'],
						'old_date' => $old_startdate,
						'new_date' => $new_startdate,
						'level_id' => ( $level_changed ? $new_user_level : $old_user_level ),
					);
				}
			}

			$old_status = $utils->get_variable(
				"e20r-members-list-db_status_{$user_info['user_id']}",
				''
			);
			$new_status = $utils->get_variable(
				"e20r-members-list-new_status_{$user_info['user_id']}",
				''
			);

			$utils->log( "Have to update status for {$user_info['user_id']}? {$new_status}" );

			if ( $old_status !== $new_status ) {

				if ( false === $this->update_status( $record_id, $new_status ) ) {
					$status_failed[] = array(
						'user_id'    => $user_info['user_id'],
						'level_id'   => $user_info['level_id'],
						'new_status' => $new_startdate,
						'old_status' => $old_status,
					);
				}
			}
		}

		/**
		 * Trigger action for bulk update (allows external handling of bulk update if needed/desired)
		 *
		 * @action e20r_memberslist_process_bulk_updates
		 *
		 * @param array[] $members_to_update - List of list of user ID's and level IDs for the selected bulk-update users
		 */
		do_action( 'e20r_memberslist_process_bulk_updates', $this->members_to_update );

		/**
		 * Error handling for build-in edit fields
		 */
		// translators: %1$s user's email address, %2$d user ID, %3$s type of data, %4$s new value, %5$s new value, %6$s existing level
		$msg_template = __(
			'Error updating data for %1$s (ID: %2$d). Could not update %3$s from %4$s to %5$s (current membership level: \'%6$s\')',
			E20R_Members_List::PLUGIN_SLUG
		);

		if ( ! empty( $level_failed ) ) {

			foreach ( $level_failed as $info ) {
				$user           = get_user_by( 'ID', $info['user_id'] );
				$new_user_level = pmpro_getLevel( $info['new_level'] );
				$old_user_level = pmpro_getLevel( $info['old_level'] );

				$update_errors[] = sprintf(
					$msg_template,
					$user->user_email,
					$user->ID,
					__( 'membership level', 'e20r-members-list' ),
					$old_user_level->name,
					( ! empty( $new_user_level->name ) ?
						$new_user_level->name :
						__( 'Not Applicable', E20R_Members_List::PLUGIN_SLUG )
					),
					( ! empty( $old_user_level->name ) ?
						$old_user_level->name :
						__( 'Not Applicable', E20R_Members_List::PLUGIN_SLUG )
					)
				);
			}
		}

		if ( ! empty( $enddate_failed ) ) {
			foreach ( $enddate_failed as $info ) {
				$user       = get_user_by( 'ID', $info['user_id'] );
				$user_level = pmpro_getLevel( $info['level_id'] );

				$update_errors[] = sprintf(
					$msg_template,
					$user->user_email,
					$user->ID,
					__( 'membership end date', 'e20r-members-list' ),
					$info['old_date'],
					$info['new_date'],
					$user_level->name
				);
			}
		}

		if ( ! empty( $startdate_failed ) ) {
			foreach ( $startdate_failed as $info ) {
				$user       = get_user_by( 'ID', $info['user_id'] );
				$user_level = pmpro_getLevel( $info['level_id'] );

				$update_errors[] = sprintf(
					$msg_template,
					$user->user_email,
					$user->ID,
					__( 'membership start date', 'e20r-members-list' ),
					$info['old_date'],
					$info['new_date'],
					$user_level->name
				);
			}
		}

		if ( ! empty( $status_failed ) ) {

			foreach ( $status_failed as $info ) {
				$user       = get_user_by( 'ID', $info['user_id'] );
				$user_level = pmpro_getLevel( $info['level_id'] );

				$update_errors[] = sprintf(
					$msg_template,
					$user->user_email,
					$user->ID,
					__( 'membership status', 'e20r-members-list' ),
					$info['old_status'],
					$info['new_status'],
					$user_level->name
				);
			}
		}

		/**
		 * Add error messages to back-end info display
		 */
		if ( ! empty( $update_errors ) ) {

			$utils->log( 'Generated ' . count( $update_errors ) . ' errors during bulk update!' );

			foreach ( $update_errors as $e_msg ) {
				$utils->log( "Error: {$e_msg}" );
				$utils->add_message( $e_msg, 'error', 'backend' );
			}

			// And return false (error)
			return false;
		}

		// All's good!
		return true;
	}

	/**
	 * Change the membership level for the specified User ID
	 *
	 * @param int $user_id
	 * @param int $current_level_id
	 * @param int $new_level_id
	 *
	 * @return bool
	 */
	public function update_membership( $user_id, $current_level_id, $new_level_id ) {

		// Execute the membership level change for the specified user ID/Level ID
		if ( function_exists( 'pmpro_changeMembershipLevel' ) ) {
			return pmpro_changeMembershipLevel(
				$new_level_id,
				$user_id,
				'admin_change',
				$current_level_id
			);
		} else {
			return false;
		}
	}

	/**
	 * Update the specified field name (a date field)
	 *
	 * @param string   $field_name
	 * @param int      $user_id
	 * @param int      $current_level
	 * @param int      $new_level
	 * @param string   $new_date Uses MySQL DateTime format: YYYY-MM-DD HH:MM:SS
	 * @param null|int $record_id
	 * @param bool     $use_new
	 *
	 * @return bool|false|int
	 */
	public function update_date(
		$field_name,
		$user_id,
		$current_level,
		$new_level,
		$new_date,
		$record_id = null,
		$use_new = false
	) {

		$utils        = Utilities::get_instance();
		$date         = null;
		$where        = null;
		$where_format = null;

		// Make sure we received a valid date
		if ( ! empty( $new_date ) && false === $this->validate_date_format( $new_date, 'Y-m-d' ) ) {

			$user = get_user_by( 'ID', $user_id );

			$msg = sprintf(
				// translators: %1$s date value, %2$d record ID, %3$s email address
				__( 'Invalid date format for %1$s (record: %2$d/email: %3$s)', 'e20r-members-list' ),
				$new_date,
				( $record_id ? $record_id : __( 'Unknown', 'e20r-members-list' ) ),
				$user->user_email
			);

			$utils->log( $msg );
			$utils->add_message( $msg, 'error', 'backend' );

			return false;
		}

		global $wpdb;

		if ( true === $use_new ) {
			$where        = array(
				'membership_id' => $new_level,
				'user_id'       => $user_id,
				'status'        => 'active',
			);
			$where_format = array( '%d', '%d', '%s' );
		} elseif ( false === $use_new && ! empty( $record_id ) ) {
			$where        = array( 'id' => $record_id );
			$where_format = array( '%d' );
		} elseif ( false === $use_new && empty( $record_id ) && ! empty( $current_level ) ) {
			$where        = array(
				'membership_id' => $current_level,
				'user_id'       => $user_id,
				'status'        => 'active',
			);
			$where_format = array( '%d', '%d', '%s' );
		}

		if ( ! empty( $new_date ) ) {
			if ( true === apply_filters( 'e20r_memberslist_membership_starts_at_midnight', __return_true() ) ) {
				$date = date_i18n(
					'Y-m-d 00:00:00',
					strtotime( $new_date, time() )
				);
			} else {
				$date = date_i18n(
					'Y-m-d h:i:s',
					strtotime( $new_date, time() )
				);
			}
		}
		$retval = $wpdb->update(
			$wpdb->pmpro_memberships_users,
			array( $field_name => $date ),
			$where,
			array( '%s' ),
			$where_format
		);

		if ( false === $retval ) {
			return $retval;
		}

		return true;
	}

	/**
	 * Update the user's Membership status for the specified record
	 *
	 * @param int    $record_id
	 * @param string $status
	 *
	 * @return bool
	 */
	public function update_status( $record_id, $status ) {

		global $wpdb;

		$retval = $wpdb->update(
			$wpdb->pmpro_memberships_users,
			array( 'status' => $status ),
			array( 'id' => $record_id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( ! empty( $retval ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Set the list of members to update
	 *
	 * @param array[] $member_info
	 */
	public function set_members( $member_info ) {
		$this->members_to_update = $member_info;
	}

	/**
	 * Return the list of members being updated
	 *
	 * @return array[]
	 */
	public function get_members() {
		return $this->members_to_update;
	}

	/**
	 * Return the ongoing operation
	 *
	 * @return null|string
	 */
	public function get_operation() {
		return $this->operation;
	}

	/**
	 * Configure/set the Operation for the Bulk Update
	 *
	 * @param string $operation
	 */
	public function set_operation( $operation ) {
		$this->operation = $operation;
	}

	/**
	 * Test the date supplied for MySQL compliance
	 *
	 * @param string $date
	 * @param string $format
	 *
	 * @return bool
	 *
	 * @credit Stack Overflow: User @glavić - https://stackoverflow.com/a/12323025
	 */
	private function validate_date_format( $date, $format = 'Y-m-d' ) {

		$check_date = \DateTime::createFromFormat( $format, $date );

		return $check_date && $check_date->format( $format ) === $date;
	}
}
