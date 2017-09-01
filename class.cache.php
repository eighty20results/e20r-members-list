<?php
/**
 * Copyright (c) 2016-2017 - Eighty / 20 Results by Wicked Strong Chicks.
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

namespace E20R\Utilities;

// Deny direct access to the file
if ( ! defined( 'ABSPATH' ) && function_exists( 'wp_die' ) ) {
	wp_die( "Cannot access file directly" );
}

if ( ! class_exists( 'E20R\Utilities\Cache' ) ) {
	
	class Cache {
		
		/**
		 * Default cache group
		 * @var string
		 */
		const CACHE_GROUP = 'e20r_group';
		
		/**
		 * Fetch entry from cache
		 *
		 * @param  mixed $key
		 * @param string $group
		 *
		 * @return bool|mixed|null
		 */
		public static function get( $key, $group = self::CACHE_GROUP ) {
			
			$found = null;
			
			$value = get_transient( "{$group}_{$key}" );
			
			if ( false === $value || false === ( $value instanceof Cache_Object ) ) {
				$value = null;
			} else {
				$value = $value->value;
			}
			
			return $value;
		}
		
		/**
		 * Store entry in cache
		 *
		 * @param string $key
		 * @param mixed  $value
		 * @param int    $expires
		 * @param string $group
		 *
		 * @return bool
		 */
		public static function set( $key, $value, $expires = 3600, $group = self::CACHE_GROUP ) {
			
			$data = new Cache_Object( $key, $value );
			
			return set_transient( "{$group}_{$key}", $data, $expires );
		}
		
		/**
		 * Delete a cache entry
		 *
		 * @param string $key
		 * @param string $group
		 *
		 * @return bool - True if successful, false otherwise
		 */
		public static function delete( $key, $group = self::CACHE_GROUP ) {
			
			return delete_transient( "{$group}_{$key}" );
		}
	}
}
