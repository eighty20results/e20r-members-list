=== E20R Enhanced Members List for Paid Memberships Pro ===
Contributors: eighty20results
Tags: paid memberships pro, members, memberships, pmpro enhancements, enhanced members list, members list
Requires at least: 4.9
Tested up to: 4.9.8
Stable tag: 2.7

== Description ==

Extensible, sortable & bulk action capable members listing tool for Paid Memberships Pro

== Installation ==

1. Upload the `e20r-members-list` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Changelog ==

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




