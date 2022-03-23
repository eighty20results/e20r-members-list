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
 * @package E20R\Members_List\Admin\Bulk\Bulk_Operations
 */

namespace E20R\Members_List\Admin\Bulk;

use E20R\Members_List\Admin\Exceptions\InvalidMemberList;
use E20R\Members_List\Admin\Exceptions\InvalidProperty;
use E20R\Utilities\Message;
use E20R\Utilities\Utilities;

if ( ! defined( 'ABSPATH' ) && ! defined( 'PLUGIN_PHPUNIT' ) ) {
	die( 'WordPress not loaded. Naughty, naughty!' ); // @codeCoverageIgnore
}

if ( ! class_exists( '\\E20R\\Members_List\\Admin\\Bulk\\Bulk_Operations' ) ) {

	/**
	 * The bulk cancel operation handler
	 */
	abstract class Bulk_Operations {

		/**
		 * Update operation to perform
		 *
		 * @var null|string
		 */
		protected $operation = null;

		/**
		 * Array of WP_User IDs where the cancel operation failed
		 *
		 * @var array[][] $failed
		 */
		protected $failed = array();

		/**
		 * Array of members to update where the memebr date is represented as an array per member
		 *
		 * @var array[]
		 */
		protected $members_to_update = array();

		/**
		 * Instance of the E20R Utilities Module class
		 *
		 * @var Utilities|null $utils
		 */
		protected $utils = null;

		/**
		 * Bulk_Cancel constructor (singleton)
		 *
		 * @param array          $members_to_update The list of members and level IDs to process
		 * @param Utilities|null $utils Instance of the E20R Utilities Module class
		 *
		 * @access public
		 */
		public function __construct( $members_to_update = null, $utils = null ) {

			if ( empty( $utils ) ) {
				$message = new Message();
				$utils   = new Utilities( $message );
			}

			if ( null !== $members_to_update ) {
				$this->set( 'members_to_update', $members_to_update );
			}
			$this->utils = $utils;
		}

		/**
		 * Set the value for the supplied parameter
		 *
		 * @param string $param The parameter we want to set the value of
		 * @param mixed  $value The value to set the class parameter to
		 *
		 * @throws InvalidProperty Raised if the user supplies an invalid class parameter
		 * @throws InvalidMemberList Raised if we're attempting to set the 'members_to_update' property and its value isn't appropriate
		 */
		public function set( string $param, $value ) {

			if (
				'members_to_update' === $param &&
				null !== $value &&
				false === $this->valid_member_array( $value )
			) {
				throw new InvalidMemberList(
					esc_attr__(
						'The specified variable is not an array of user IDs',
						'e20r-members-list'
					)
				);
			} elseif ( 'members_to_update' === $param && null === $value ) {
				$value = array();
			}

			// Make sure we let the caller know there's a problem if the variable doesn't exist.
			if ( ! property_exists( $this, $param ) ) {
				throw new InvalidProperty(
					sprintf(
					// translators: %1$s - The parameter supplied, %2$s - The class name where we expect the supplied parameter to exist,
						esc_attr__( 'Invalid parameter "%1$s" supplied for %2$s', 'e20r-members-list' ),
						$param,
						__CLASS__
					)
				);
			}

			$this->{$param} = $value;
		}

		/**
		 * Get the value of the supplied class parameter
		 *
		 * @param string $param The parameter we want to set the value of
		 *
		 * @throws InvalidProperty Raised if the user supplies an invalid class parameter
		 *
		 * @returns mixed
		 */
		public function get( string $param ) {
			// Make sure we let the caller know there's a problem if the variable doesn't exist.
			if ( ! property_exists( $this, $param ) ) {
				throw new InvalidProperty(
					sprintf(
					// translators: %1$s - The class name where we expect the supplied parameter to exist.
						esc_attr__( 'Invalid parameter supplied for %1$s', 'e20r-members-list' ),
						__CLASS__
					)
				);
			}

			return $this->{$param};
		}

		/**
		 * Make sure the array is a valid user/membership level array
		 *
		 * @param mixed $members Variable (array) to test
		 *
		 * @return bool
		 */
		protected function valid_member_array( $members ) {

			if ( ! is_array( $members ) ) {
				return false;
			}

			if ( empty( $members ) ) {
				return true;
			}

			return array_reduce(
				$members,
				function ( $result, $item ) {
					return $result &&
						( isset( $item['user_id'] ) && is_int( $item['user_id'] ) ) &&
						( isset( $item['level_id'] ) && is_int( $item['level_id'] ) );
				},
				true
			);
		}

		/**
		 * Execute the Bulk Operation
		 *
		 * @return mixed
		 */
		abstract public function execute();
	}
}
