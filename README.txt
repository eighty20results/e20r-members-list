=== E20R Better Members List for Paid Memberships Pro ===
Contributors: eighty20results
Tags: paid memberships pro, members, memberships, pmpro enhancements, better members list, members list
Requires at least: 4.9
Tested up to: 5.4
Requires PHP: 7.1
Stable tag: 5.10
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl

== Description ==

Extensible, sortable & bulk action capable members listing tool for Paid Memberships Pro. This plugin is a complete replacement for the "Members List" functionality in PMPro and supports most of the same filters and hooks. The key differences have to do with managing columns. Now you can also use the [standard WordPress filters](https://developer.wordpress.org/reference/classes/wp_list_table/) to columns you can add/remove/make sortable, additional bulk actions, etc.

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

=== e20r-memberslist-http-headers ===

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
Example: `add_filter( 'e20r-memberslist-http-headers', array(
                                                          "Content-Type: text/csv",
                                                          "Cache-Control: max-age=0, no-cache, no-store",
                                                      	"Pragma: no-cache",
                                                      	"Connection: close",
                                                      	'Content-Disposition: attachment; filename="members_list.csv"',
                                                      )
                  );`

=== e20r-members-list-enddate-col-name ===

Modifies: The header label for the PMPro enddate column on the Members List page or in the CSV export file

Dependencies: N/A

Default: 'Expired' if viewing old/expired members list. 'Expires' if viewing a list of active members

Example: `add_filter( 'e20r-members-list-enddate-col-name', function( $label ) { return 'Terminated'; } ); // Replace enddate header with 'Terminated'`

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


=== e20r-members-list-expires-col-value ===

Modifies: Format the date value for the data displayed for the Expires/membership_enddate column

Purpose:  The filter

Arguments: string $end_date, \stdClass $member

Default: `pmpro_memberships_users.enddate, $query_result_member_data_from_enddate`

Example: `add_filter( 'e20r-members-list-expires-col-value'', 'e20r_set_expires_date', 10, 2 );`

=== pmpro_members_list_csv_default_columns ===

Modifies: Default list of member data columns to load and either export or display in the list

Purpose:  The filter allows you to change the default columns this plugin will collect user/member data for to display in the Members List (or Export to CSV).

Default:

Example: `add_filter( 'e20r_memberslist_page_prepend_cols', '__return_true' );`


=== e20r-members-list-add-to-default-table-columns ===

Modifies: Export to CSV

Purpose:  The filter processes the list of columns to include on the Members List table.

Default: Members_List::$default_columns

Example: `add_filter( 'e20r-members-list-add-to-default-table-columns', 'e20r_set_default_table_columns', 10, 2 );`


=== e20r-members-list-page-prepend-cols ===

Modifies: Export to CSV

Purpose:  The filter processes a single boolean return value (true/false). The filter determines whether to add the column list (array) returned by the the `e20r_memberslist_columnlist` filter to the front or back of the default list of columns/data to export.

Default: false

Example: `add_filter( 'e20r-members-list-page-prepend-cols', '__return_true' );`

=== e20r-members-list-csv-datetime-format ===

Modifies: Format of start/end and registration date/time during Export

Purpose: Allow a programmer to set a custom date/time format for the exported Membership start date, membership end date and WordPress user registration date. Must return a valid PHP `date()` format (see PHP date() documentation/man page for valid parameters).

Default: WordPress -> Settings -> General -> "Date Format" and "Time Format" setting

Example: `add_filter( 'e20r-members-list-csv-datetime-format', "function( $datetime_format, $date_format, $time_format ) { return 'Y-m-d\TH:i:s'; }" );`

=== e20r-members-list-db-type-header-map ===

Modifies: Extends the DB column -> CSV export file mapping (with table type)

Purpose: Let a programmer add more columns to the CSV export file (with the corresponding data). By default, the plugin supports adding columns from the 'wp_user', 'wp_usermeta' 'pmpro_memberships_users', 'pmpro_membership_levels' and 'pmpro_discount_codes' DB tables by adding the new column to the db/header map.

Default: The default Export to CSV columns

Dependencies: The 'e20r-members-list-default-csv-columns' filter will also need to include/return the columns mapped by this filter ( 'e20r-members-list-db-type-header-map' )

Example: `add_filter( 'e20r-members-list-db-type-header-map', 'e20r_add_to_db_header_map', 10, 2 );`


== Actions ==

To Be Announced...

== Known Issues ==

As of Paid Memberships Pro v2.2+, the PMPro plugin started sending HTTP content to the browser at a very early stage of the connection process. As a result, anything that uses the standard action hooks in WordPress (like this plugin) and wants to send header info to the browser so it "does the right thing" is being messed up.

I am working to isolate the source of this problem and resolving it.

My apologies for not yet having a solution for the incompatibility introduced by PMPro v2.2+.

== Changelog ==

== 5.10 ==
* BUG FIX: Fatal error in Utilities library
* BUG FIX: Got IDE warning for missing variable (thinks $this->items may be dynamic)
* BUG FIX: Didn't initiate the total_items variable
* BUG FIX: Avoid PHP Warning when logging debug info about records found & pagination
* BUG FIX: Handle situations where there are no records found (w/o logging warnings or errors)
* ENHANCEMENT: Use $wpdb->num_rows instead of 'FOUND_ROWS()' which is slow when possible


== 5.9.1 ==
* BUG FIX: Improved performance from Utilities library

== 5.9 ==
* BUG FIX: Adding utilities library as subtree

== 5.8 ==
* BUG FIX: Shipping plugin lacks standard utilities library

== 5.7.6 ==
* BUG FIX: Possibly causing fatal error when running/activated

== 5.7.5 ==
* BUG FIX: Migrate origin sources to github.com

== 5.7.4 ==
* BUG FIX: Removing .git data

== 5.7.3 ==
* BUG FIX: Removed extra data from .zip archive

== 5.7.2 ==
* BUG FIX: Didn't always include the discount code in exported data

== 5.7.1 ==
* ENHANCEMENT: Pushing to WordPress.org repository
* ENHANCEMENT: Pushing to wordpress.org from Github.com
* BUG FIX: Prevent defining controller class more than once

== 5.6 ==
* ENHANCEMENT: Updated utilities library

== 5.5 ==
* ENHANCEMENT: Added new utilities library

== 5.4 ==
* BUG FIX: Unhandled exception for autoloader registration
* BUG FIX: Didn't include the period number for recurring billing if period > 1 (i.e. 2 years, 4 weeks, or 3 months, etc) - Thanks to user @jaco44!

== 5.3 ==
* ENHANCEMENT: Tested with WordPress v5.1
* BUG FIX: Removed some of the unnecessary debug logging

== 5.2 ==
* BUG FIX: Used 'Never' as the expiration/enddate when membership has recurring payment.
* ENHANCEMENT: Try to load the next payment date as the "Expired"/"Expires" column value for recurring payment memberships

== 5.1 ==
* ENHANCEMENT: The "Export to CSV" function now creates a valid Import from CSV file (no conversion needed)
* BUG FIX: Updates caused problems with extra column (added by 3rd party) info during CSV export
* BUG FIX: Export to CSV generates a valid import file, but we accidentally used an invalid `membership_enddate` value in some cases

== 5.0 ==
* BUG FIX: Didn't save the standard export column data to the CSV file
* ENHANCEMENT: Various filter updates and updated README.txt with own filters & actions section
* ENHANCEMENT: Add a 'Discount Code' column to the Members List

== 4.1 ==

* ENHANCEMENT: Added 'e20r-memberslist-http-headers' filter to let programmer extend/modify the HTTP request header(s)
* BUG FIX: Use array, not text, for the CSV header (column names)
* BUG FIX: Use get_cfg_var() and not ini_get() for max_execution_time
* BUG FIX: Update copyright notice

== 4.0 ==

* ENHANCEMENT: Simplified menu structure
* BUG FIX: Updated plugin to support Paid Memberships V2.0+ menu structure
* BUG FIX: Update Utilities library

== 3.3 ==

* ENHANCEMENT: Added Translations if possible/applicable
* ENHANCEMENT: For no-fee memberships, use 'Free' as the amount (filterable)
* ENHANCEMENT: Added the 'e20r-memberslist-column-value-free' filter to let the admin change the 'no fee' text from "Free" to whatever they want
* BUG FIX: Fix column width for the Fee column
* BUG FIX: Remove E20R_Members_List::forceTLS12() method

== 3.2 ==

* ENHANCEMENT: Add 'Search results for ...' text after submitting member search
* ENHANCEMENT: Added/Updated PHPDoc blocks in Members_List_Page() class
* ENHANCEMENT: Added custom links to easily resubmit a search when there are no results found when searching the current list of members
* ENHANCEMENT: Standardize translation domain string with a class constant E20R_Members_List::plugin_slug
* BUG FIX: Don't show a 'Search again' link for the currently selected type when no members are found after a search
* BUG FIX: Don't allow clone of Members_List_Page() class (singleton)
* BUG FIX: Don't allow clone of Bulk_Cancel() class (singleton)
* BUG FIX: Don't allow clone of Bulk_Update() class (singleton)
* BUG FIX: Allow standardized list of searchable membership levels/lists
* BUG FIX: Use 'paid-memberships-pro' as the I18N domain for PMPro specific text
* BUG FIX: Attempted to load variable when we needed to load the warning/info messages
* BUG FIX: E20R_MEMBERSLIST_VER constant no located in E20R_Members_List() definition file

== 3.1 ==

* ENHANCEMENT: Added banner and icon

== 3.0 ==

* ENHANCEMENT: Pushing to WordPress.org repository
* BUG FIX: Prevent defining controller class more than once

== 2.7 ==

* ENHANCEMENT: Change the names of the export .csv columns to match the expected column names for the Import Members from CSV plugin
* ENHANCEMENT: Added PHPDoc block for the Members_List::metadata_where() method
* ENHANCEMENT: WPCS formatting of Members_List() class
* ENHANCEMENT: Updated the Utilities sub-module
* BUG FIX: An update would sometimes get ignored (not saved)

== 2.6 ==

* BUG FIX: Didn't include the values for any defined extra columns

== 2.5 ==

* ENHANCEMENT: Let a developer change the 'expiration' ('last') column label with the 'e20r-members-list-enddate-col-name' filter
* ENHANCEMENT: Let a developer change the enddate value to match the (new?) 'last' column value (expiration date) with the 'e20r-members-list-enddate-col-result' filter

== 2.4 ==

* BUG FIX: Allow user to reset search
* BUG FIX: Incorrect # of found records returned on search
* BUG FIX: Error looking up user ID for metadata
* BUG FIX: Clean URL (GET params from URL) if user has (just) searched

== 2.2 ==

* BUG FIX: No longer supporting per line 'Cancel Membership' link

== 2.1 ==

* BUG FIX: Clear search field when user clicks 'Update List' button
* BUG FIX: Show 'Invalid' if the startdate value is incorrect (not a current/real date value)
* ENHANCEMENT: Add build tools & one-click update support tools
* ENHANCEMENT: Single license text instance
* ENHANCEMENT: Load class if not previously defined
* ENHANCEMENT: WordPress Style updates
* ENHANCEMENT: Improved English grammar in error message

== 2.0 ==

* Initial public release




