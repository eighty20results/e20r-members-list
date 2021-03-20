=== E20R Better Members List for Paid Memberships Pro ===
Contributors: eighty20results
Tags: paid memberships pro, members, memberships, pmpro enhancements, better members list, members list
Requires at least: 4.9
Tested up to: 5.7
Requires PHP: 7.1
Stable tag: 
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl

== Description ==

Extensible, sortable & bulk action capable members listing tool for Paid Memberships Pro. This plugin is a complete replacement for the "Members List" functionality in PMPro and supports most of the same filters and hooks. The key differences have to do with managing columns. Now you can also use the [standard WordPress filters](https://developer.wordpress.org/reference/classes/wp_list_table/) to columns you can add/remove/make sortable, additional bulk actions, etc.

![Release to wordpress.org](https://github.com/eighty20results/e20r-members-list/workflows/Release%20to%20wordpress.org/badge.svg?branch=main)

== Installation ==

1. Upload the `e20r-members-list` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Extending the Members List ==

This plugin uses the `WP_List_Table` class to generate the flexible table approach you know and love from the WordPress Post/Page/etc back-end. As a result, it's has a standardized and flexible approach to adding columns to the table.

Iv'e also included a number of filters and actions to let a [PHP developer](https://eighty20results.com/need-something-else/) expand on the search functionality for the list.

The same goes for the Export to CSV functionality.

This plugin supports the standard Paid Memberships Pro filters to add new CSV export columns and data.

== Filters ==

Note: The Filters and Actions sections are incomplete!

To best understand how to extend this plugin, we recommend searching through the plugin sources for calls to the `apply_filter()` and `do_action()` functions.

There are also several Paid Memberships Pro specific filters present in this plugin to, as well as possible, maintain compatibility with the PMPro Members List functionality.

=== e20r_memberslist_http_headers ===

Modifies: HTTP header array to transmit to client before sending the actual .csv file to web client

Purpose: Update/add/remove HTTP headers for compatibility with HTTP server and HTTP client/browser

Dependencies: N/A

Default: List (array) of valid HTTP headers to support file download for both Apache and ngnix HTTP servers:
`
$headers = array(
    "Content-Type: text/csv",
    "Cache-Control: max-age=0, no-cache, no-store",
	"Pragma: no-cache",
	"Connection: close",
	'Content-Disposition: attachment; filename="members_list.csv"',
);
`
Example: `add_filter( 'e20r_memberslist_http_headers', array(
                                                          "Content-Type: text/csv",
                                                          "Cache-Control: max-age=0, no-cache, no-store",
                                                      	"Pragma: no-cache",
                                                      	"Connection: close",
                                                      	'Content-Disposition: attachment; filename="members_list.csv"',
                                                      )
                  );`

=== e20r_members_list_enddate_col_name ===

Modifies: The header label for the PMPro enddate column on the Members List page or in the CSV export file

Dependencies: N/A

Default: 'Expired' if viewing old/expired members list. 'Expires' if viewing a list of active members

Example: `add_filter( 'e20r_members_list_enddate_col_name', function( $label ) { return 'Terminated'; } ); // Replace enddate header with 'Terminated'`

=== e20r_memberslist_sql_columns ===

Modifies: The default DB columns and respective column aliases to load from the WordPress database

Purpose: Update the list of columns to load from the DB

Dependencies: If you modify the column list, you will also need to ensure that the SQL generation code includes any new tables and aliases, joins them properly, etc.

Default: Array of DB column names (with table alias) to load data for:

`
array( 'mu.id'              => 'record_id',
       	'u.ID'               => 'user_id',
       	'u.user_login'       => 'username',
       	'u.user_email'       => 'user_email',
       	'u.user_registered'  => 'joindate',
       	'mu.membership_id'   => 'membership_id',
       	'mu.initial_payment' => 'initial_payment',
       	'mu.billing_amount'  => 'recurring_payment',
       	'mu.cycle_period'    => 'billing_period_name',
       	'mu.cycle_number'    => 'billing_period_duration',
       	'mu.billing_limit'   => 'billing_limit',
       	'mu.code_id'         => 'pmpro_discount_code_id',
       	'mu.status'          => 'status',
       	'mu.trial_amount'    => 'trial_amount',
       	'mu.trial_limit'     => 'trial_limit',
       	'mu.startdate'       => 'startdate',
       	'mu.enddate'         => 'enddate',
       	'ml.name'            => 'membership',
    );
``

Example: `add_filter( 'e20r_memberslist_sql_columns', 'e20r_change_memberslist_sql_columns', 10, 1 );`


=== e20r_members_list_expires_col_value ===

Modifies: Format the date value for the data displayed for the Expires/membership_enddate column

Purpose:  The filter

Arguments: string $end_date, \stdClass $member

Default: `pmpro_memberships_users.enddate, $query_result_member_data_from_enddate`

Example: `add_filter( 'e20r_members_list_expires_col_value'', 'e20r_set_expires_date', 10, 2 );`

=== pmpro_members_list_csv_default_columns ===

Modifies: Default list of member data columns to load and either export or display in the list

Purpose:  The filter allows you to change the default columns this plugin will collect user/member data for to display in the Members List (or Export to CSV).

Default:

Example: `add_filter( 'e20r_memberslist_page_prepend_cols', '__return_true' );`


=== e20r_members_list_add_to_default_table_columns ===

Modifies: Export to CSV

Purpose:  The filter processes the list of columns to include on the Members List table.

Default: Members_List::$default_columns

Example: `add_filter( 'e20r_members_list_add_to_default_table_columns', 'e20r_set_default_table_columns', 10, 2 );`


=== e20r_members_list_page_prepend_cols ===

Modifies: Export to CSV

Purpose:  The filter processes a single boolean return value (true/false). The filter determines whether to add the column list (array) returned by the the `e20r_memberslist_columnlist` filter to the front or back of the default list of columns/data to export.

Default: false

Example: `add_filter( 'e20r_members_list_page_prepend_cols', '__return_true' );`

=== e20r_members_list_csv_datetime_format ===

Modifies: Format of start/end and registration date/time during Export

Purpose: Allow a programmer to set a custom date/time format for the exported Membership start date, membership end date and WordPress user registration date. Must return a valid PHP `date()` format (see PHP date() documentation/man page for valid parameters).

Default: WordPress -> Settings -> General -> "Date Format" and "Time Format" setting

Example: `add_filter( 'e20r_members_list_csv_datetime_format', "function( $datetime_format, $date_format, $time_format ) { return 'Y-m-d\TH:i:s'; }" );`

=== e20r_members_list_db_type_header_map ===

Modifies: Extends the DB column -> CSV export file mapping (with table type)

Purpose: Let a programmer add more columns to the CSV export file (with the corresponding data). By default, the plugin supports adding columns from the 'wp_user', 'wp_usermeta' 'pmpro_memberships_users', 'pmpro_membership_levels' and 'pmpro_discount_codes' DB tables by adding the new column to the db/header map.

Default: The default Export to CSV columns

Dependencies: The 'e20r_members_list_default_csv_columns' filter will also need to include/return the columns mapped by this filter ( 'e20r_members_list_db_type_header_map' )

Example: `add_filter( 'e20r_members_list_db_type_header_map', 'e20r_add_to_db_header_map', 10, 2 );`

=== e20r_memberslist_search_user_fields ===

Modifies: Array (list) of fields included when performing a search

Purpose: Change the fields to include in the SQL query generated by the search operation. See Default for info about fields being included already. NOTE: There's a separate filter for wp_usermeta fields. It is NOT (currently) possible to search in member information fields beyond the end date/start date.

Default: The default wp_users table fields to search in (i.e. user_login, user_nicename, display_name, user_email)

Dependencies: N/A

Example: `add_filter( 'e20r_memberslist_search_user_fields', 'e20r_update_usertable_fields', 10, 1 );`

=== e20r_memberslist_search_usermeta_fields ===

Modifies: Array (list) of fields included when performing a search

Purpose: Change the fields to include in the SQL query generated by the search operation. See Default for info about fields being included already. NOTE: There's a separate filter for wp_users fields. It is NOT (currently) possible to search in member information fields beyond the end date/start date.

Default: The default wp_usermeta table fields to search in (i.e. meta_value)

Dependencies: N/A

Example: `add_filter( 'e20r_memberslist_search_usermeta_fields', 'e20r_update_usertable_fields', 10, 1 );`

=== e20r_memberslist_default_sort_order ===

Modifies: The default database sort order for the members list
Purpose: Allow a developer to change the default sort order from Ascending to Descending (DESC) if that's a desirable order for them

Default: 'ASC'

Dependencies: N/A

Example: `add_filter( 'e20r_memberslist_search_usermeta_fields', "function() { return 'DESC'; }", 10, 1 );`

== Actions ==

To Be Announced...

== Known Issues ==

As of Paid Memberships Pro v2.2+, the PMPro plugin started sending HTTP content to the browser at a very early stage of the connection process. As a result, anything that uses the standard action hooks in WordPress (like this plugin) and wants to send header info to the browser so it "does the right thing" is being messed up.

I am working to isolate the source of this problem and resolving it.

My apologies for not yet having a solution for the incompatibility introduced by PMPro v2.2+.

== Changelog ==

See the official [CHANGELOG.md file](CHANGELOG.md).
