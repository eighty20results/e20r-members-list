<?php
/**
 * Copyright (c) 2016 - 2022 - Eighty / 20 Results by Wicked Strong Chicks.
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
 * @package E20R\Members_List\Admin\Modules\Multiple_Members
 */

namespace E20R\Members_List\Admin\Modules;

/**
 * Support for PHP's Multiple Memberships plugin
 */
class Multiple_Members {

	/**
	 * Constructor for Multiple_Members
	 */
	public function __construct() {
		add_filter( 'e20r_members_list_add_to_default_table_columns', array( $this, 'mmpu_columns' ), -1 );
	}

	/**
	 * Set the MMPU specific column header(s)
	 *
	 * @param array $new Stuff asked for by other hook handlers
	 * @param array $default The default column list
	 *
	 * @return array
	 */
	public function mmpu_columns( $new, $default ) {
		$default['name'] = esc_attr_x( 'Level(s)', 'e20r-members-list' );
		return $default;
	}
	/**
	 * Generate the MMPU specific column information
	 *
	 * @param array $item The record to process column information for.
	 *
	 * @return string
	 */
	public function column_name( $item ) {

		// FIXME: Update this to support MMPU
		// FIXME: Allow adding more levels and changing the "primary" level.

		// These are used to configure the membership level with JavaScript.
		$membership_input = sprintf(
			'
				<input type="hidden" value="%1$d" class="e20r-members-list-membership-id" name="e20r-members-list-membership_id_%2$s" />
				<input type="hidden" value="%2$d" class="e20r-members-list-user-id" name="e20r-members-list-membership_id_user_id_%2$s" />
				<input type="hidden" value="%3$s" class="e20r-members-list-membership_id-label" name="e20r-members-list-membership_label_%2$s" />
				<input type="hidden" value="%1$d" class="e20r-members-list-db-membership_id" name="e20r-members-list-db_membership_id_%2$s" />
				<input type="hidden" value="%6$d" class="e20r-members-list-db-membership_level_ids" name="e20r-members-list-db_membership_level_ids_%2$s" />
				<input type="hidden" value="%5$d" class="e20r-members-list-db_record_id" name="e20r-members-list-db_record_id_%2$s" />
				<input type="hidden" value="%4$s" class="e20r-members-list-field-name" name="e20r-members-list-field_name_%2$s" />',
			$item['membership_id'],
			$item['ID'],
			$item['name'],
			'membership_id',
			$item['record_id'],
			$item['membership_level_ids']
		);

		$options = '';
		if ( function_exists( 'pmpro_getAllLevels' ) ) {
			$levels = pmpro_getAllLevels( true, true );
		} else {

			// Default info if PMPro is disabled.
			$null_level           = new \stdClass();
			$null_level->level_id = 0;
			$null_level->name     = esc_attr__( 'No levels found. Paid Memberships Pro is inactive!', 'e20r-members-list' );
			$levels               = array( $null_level );
		}

		foreach ( $levels as $level ) {
			$options .= sprintf(
				'<option value="%1$s" %2$s>%3$s</option>',
				$level->id,
				selected( $level->id, $item['membership_id'], false ),
				$level->name
			) . "\n";
		}
		$new_membershiplevel_input = sprintf(
			'<div class="ml-row-settings clearfix">
						%1$s
						<select name="e20r-members-list-new_membership_id_%2$s" class="e20r-members-list-select-membership_id">
						%3$s
						</select>
						<br>
						<a href="#" class="e20r-members-list-cancel e20r-members-list-link">%4$s</a>
					</div>',
			$membership_input,
			$item['ID'],
			$options,
			esc_attr__( 'Reset', 'e20r-members-list' )
		);

		$value = sprintf(
			'<a href="#" class="e20r-members-list_membership_id e20r-members-list-editable" title="%1$s">%2$s<span class="dashicons dashicons-edit"></a>%3$s',
			esc_attr__( 'Click to edit membership level', 'e20rapp' ),
			$item['name'],
			$new_membershiplevel_input
		);

		return '';
	}
}
