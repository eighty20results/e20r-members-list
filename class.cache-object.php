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
	wp_die("Cannot access file directly" );
}

if ( ! class_exists( 'E20R\Utilities\Cache_Object' ) ) {
	
	class Cache_Object {
		
		/**
		 * The Cache Key
		 * @var string
		 */
		private $key = null;
		
		/**
		 * The Cached value
		 * @var mixed
		 */
		private $value = null;
		
		/**
		 * Cache_Object constructor.
		 *
		 * @param string $key
		 * @param mixed  $value
		 */
		public function __construct( $key, $value ) {
			
			$this->key   = $key;
			$this->value = $value;
		}
		
		/**
		 * Setter for the key and value properties
		 *
		 * @param string $name
		 * @param mixed  $value
		 */
		public function __set( $name, $value ) {
			
			switch ( $name ) {
				case 'key':
				case 'value':
					$this->{$name} = $value;
					break;
			}
		}
		
		/**
		 * Getter for the key and value properties
		 *
		 * @param string $name
		 *
		 * @return mixed|null - Property value (for Key or Value property)
		 */
		public function __get( $name ) {
			
			$result = null;
			
			switch ( $name ) {
				case 'key':
				case 'value':
					
					$result = $this->{$name};
					break;
			}
			
			return $result;
		}
		
		public function __isset( $name ) {
			
			return isset( $this->{$name} );
		}
	}
}