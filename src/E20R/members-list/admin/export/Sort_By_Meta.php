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
 * @package E20R\Members_List\Admin\Export\Sort_By_Meta
 */

namespace E20R\Members_List\Admin\Export;

if ( ! defined( 'ABSPATH' ) && ! defined( 'PLUGIN_PHPUNIT' ) ) {
	die( 'WordPress not loaded. Naughty, naughty!' );
}

if ( ! class_exists( '\\E20R\\Members_List\\Admin\\Export\\Sort_By_Meta' ) ) {

	/**
	 * Handles metadata based sorting for the export operation.
	 */
	class Sort_By_Meta {

		/**
		 * The metadata key to sort by
		 *
		 * @var string $meta_key
		 */
		private $meta_key;

		/**
		 * Configure/save the sort order for the Metadata search
		 *
		 * @var string $order Sort order
		 */
		private $order = 'DESC';

		/**
		 * Sort_Meta constructor.
		 *
		 * @param string $key_name The metadata key to sort by.
		 * @param string $order The metadata value sort order.
		 */
		public function __construct( $key_name, $order = 'DESC' ) {
			$this->meta_key = $key_name;
			$this->order    = strtoupper( $order );
		}

		/**
		 * Sorts the actual records for us.
		 *
		 * @param array $a The first value to compare the sort against
		 * @param array $b The 2nd value to compare the sort against
		 *
		 * @return int|false
		 */
		public function sort_records( $a, $b ) {

			$a_user_id = is_array( $a ) ?
				$a['user_id'] :
				( is_a( $a, '\WP_User' ) ? $a->ID : null );
			$b_user_id = is_array( $b ) ?
				$b['user_id'] :
				( is_a( $b, '\WP_User' ) ? $b->ID : null );

			if ( is_null( $a_user_id ) || is_null( $b_user_id ) ) {
				return false;
			}

			// Check if the field specified exists in the data
			if ( ! isset( $a[ $this->meta_key ] ) ) {
				$a_value = get_user_meta( $a_user_id, $this->meta_key, true );

			} else {
				$a_value = $b[ $this->meta_key ];
			}

			if ( ! isset( $b[ $this->meta_key ] ) ) {
				$b_value = get_user_meta( $b_user_id, $this->meta_key, true );
			} else {
				$b_value = $b[ $this->meta_key ];
			}

			if ( $a_value === $b_value ) {
				return 0;
			}

			if ( 'DESC' === $this->order ) {
				return ( $a_value > $b_value ? 1 : - 1 );
			}

			if ( 'ASC' === $this->order ) {
				return ( $a_value < $b_value ? 1 : - 1 );
			}

			return false;
		}
	}
}
