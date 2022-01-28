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

use E20R\Members_List\Admin\Exceptions\InvalidProperty;
use E20R\Utilities\Message;
use E20R\Utilities\Utilities;
use function pmpro_cancelMembershipLevel;

if ( ! defined( 'ABSPATH' ) && ! defined( 'PLUGIN_PHPUNIT' ) ) {
	die( 'WordPress not loaded. Naughty, naughty!' );
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
		 * @var null|int[] $failed
		 */
		protected $failed = null;

		/**
		 * Array of members to update where the memebr date is represented as an array per member
		 *
		 * @var array[]|int[]|null
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
		 * @param Utilities|null $utils Instance of the E20R Utilities Module class
		 *
		 * @access public
		 */
		public function __construct( $utils = null ) {

			if ( empty( $utils ) ) {
				$message = new Message();
				$utils   = new Utilities( $message );
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
		 */
		public function set( string $param, $value ) {
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
		 * Execute the Bulk Operation
		 *
		 * @return mixed
		 */
		abstract public function execute();
	}
}
