# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [7.6] - 2021-02-22
- BUG FIX: Implicitly defined variable fixes
- BUG FIX: Refactored to use Class::plugin_slug for translation strings
- BUG FIX: Using explicit namespace paths
- BUG FIX: Refactored e20r-memberslist-page.js
- BUG FIX: Exclude the inc/ directory from the Wordpress.org repo
- BUG FIX: Didn't install the composer dependencies
- ENH: Adding support for filter to set default sort order - e20r_memberslist_default_sort_order
- ENH: Adding new filter to documentation section
- ENH: Adding composer file for unit testing, CircleCI integration, etc

## [7.5] - 2021-02-10
- BUG FIX: Didn't automatically search for levels only when selected level is changed

## [7.4] - 
- ENH: Adding script to remove local & upstream tasks based on string pattern
- ENH: Adding support for auto-labeling of Pull Requests upon commit
- ENH: Adding automated release draft creation action
- ENH: Do not include the test and build_env directories
- ENH: Initial dockerfile for unittests
- ENH: Refactored to support autoloader
- BUG FIX: Make it work on the new hosting account
- BUG FIX: Didn't handle return key press for search functionality
- BUG FIX: Didn't reset the URI when clicking 'Clear Search'
- BUG FIX: Updated autoloader to support new file name structure

## [7.2] - 
- BUG FIX: Better detection of custom one-click update when not using wordpress.org repo version of plugin

## [7.1] - 
- BUG FIX: Avoid fatal error if E20R Utilities library is missing
- BUG FIX: Update removal logic when building SVN repo to commit as part of GitHub action

## [7.0] - 
- ENH: Allow filtering of User table fields to search by
- ENH: Allow filtering of usermeta table fields to search by
- ENH: Allow filtering of sort order in export
- ENH: Deprecate use of FOUND_ROWS() in pagination
- BUG FIX: Syntax error in update removal action
- BUG FIX: Uncaught exception in autoLoader()
- BUG FIX: Didn't include all logical user table fields in search query
- BUG FIX: Didn't trigger search if typing the 'enter' key

## [6.3] - 
- BUG FIX: Fatal error when e20r-Utilities module is present
- BUG FIX: Should also remove custom updater code from embedded utilities module

## [6.2] - 
- BUG FIX: Fatal error if Utilities module isn't pre-installed
- BUG FIX: Didn't remove the update functionality

## [6.1] - 
- BUG FIX: Updated for wordpress.org

## [6.0] - 
- BUG FIX: Didn't paginate correctly because the LIMIT logic caused us to not return the full number of records for the level/status
- BUG FIX: Potentially a fatal PHP error
- BUG FIX: Bad path to downloadable archive
- BUG FIX: Didn't (always) import the database needed for testing
- BUG FIX: Readme directions for installation were imprecise
- BUG FIX: Removed copy/paste Utilities module and using git subtree instead
- BUG FIX: Updated copyright notice


## [5.10] - 
- BUG FIX: Fatal error in Utilities library
- BUG FIX: Got IDE warning for missing variable (thinks $this->items may be dynamic)
- BUG FIX: Didn't initiate the total_items variable
- BUG FIX: Avoid PHP Warning when logging debug info about records found & pagination
- BUG FIX: Handle situations where there are no records found (w/o logging warnings or errors)
- ENHANCEMENT: Use $wpdb->num_rows instead of 'FOUND_ROWS()' which is slow when possible


## [5.9.1] - 
- BUG FIX: Improved performance from Utilities library

## [5.9] - 
- BUG FIX: Adding utilities library as subtree

## [5.8] - 
- BUG FIX: Shipping plugin lacks standard utilities library

## [5.7.6] - 
- BUG FIX: Possibly causing fatal error when running/activated

## [5.7.5] - 
- BUG FIX: Migrate origin sources to github.com

## [5.7.4] - 
- BUG FIX: Removing .git data

## [5.7.3] - 
- BUG FIX: Removed extra data from .zip archive

## [5.7.2] - 
- BUG FIX: Didn't always include the discount code in exported data

## [5.7.1] - 
- ENHANCEMENT: Pushing to WordPress.org repository
- ENHANCEMENT: Pushing to wordpress.org from Github.com
- BUG FIX: Prevent defining controller class more than once

## [5.6] - 
- ENHANCEMENT: Updated utilities library

## [5.5] - 
- ENHANCEMENT: Added new utilities library

## [5.4] - 
- BUG FIX: Unhandled exception for autoloader registration
- BUG FIX: Didn't include the period number for recurring billing if period > 1 (i.e. 2 years, 4 weeks, or 3 months, etc) - Thanks to user @jaco44!

## [5.3] - 
- ENHANCEMENT: Tested with WordPress v5.1
- BUG FIX: Removed some of the unnecessary debug logging

## [5.2] - 
- BUG FIX: Used 'Never' as the expiration/enddate when membership has recurring payment.
- ENHANCEMENT: Try to load the next payment date as the "Expired"/"Expires" column value for recurring payment memberships

## [5.1] - 
- ENHANCEMENT: The "Export to CSV" function now creates a valid Import from CSV file (no conversion needed)
- BUG FIX: Updates caused problems with extra column (added by 3rd party) info during CSV export
- BUG FIX: Export to CSV generates a valid import file, but we accidentally used an invalid `membership_enddate` value in some cases

## [5.0] - 
- BUG FIX: Didn't save the standard export column data to the CSV file
- ENHANCEMENT: Various filter updates and updated README.txt with own filters & actions section
- ENHANCEMENT: Add a 'Discount Code' column to the Members List

## [4.1] - 

- ENHANCEMENT: Added 'e20r_memberslist_http_headers' filter to let programmer extend/modify the HTTP request header(s)
- BUG FIX: Use array, not text, for the CSV header (column names)
- BUG FIX: Use get_cfg_var() and not ini_get() for max_execution_time
- BUG FIX: Update copyright notice

## [4.0] - 

- ENHANCEMENT: Simplified menu structure
- BUG FIX: Updated plugin to support Paid Memberships V2.0+ menu structure
- BUG FIX: Update Utilities library

## [3.3] - 

- ENHANCEMENT: Added Translations if possible/applicable
- ENHANCEMENT: For no-fee memberships, use 'Free' as the amount (filterable)
- ENHANCEMENT: Added the 'e20r_memberslist_column_value_free' filter to let the admin change the 'no fee' text from "Free" to whatever they want
- BUG FIX: Fix column width for the Fee column
- BUG FIX: Remove E20R_Members_List::forceTLS12() method

## [3.2] - 

- ENHANCEMENT: Add 'Search results for ...' text after submitting member search
- ENHANCEMENT: Added/Updated PHPDoc blocks in Members_List_Page() class
- ENHANCEMENT: Added custom links to easily resubmit a search when there are no results found when searching the current list of members
- ENHANCEMENT: Standardize translation domain string with a class constant E20R_Members_List::PLUGIN_SLUG
- BUG FIX: Don't show a 'Search again' link for the currently selected type when no members are found after a search
- BUG FIX: Don't allow clone of Members_List_Page() class (singleton)
- BUG FIX: Don't allow clone of Bulk_Cancel() class (singleton)
- BUG FIX: Don't allow clone of Bulk_Update() class (singleton)
- BUG FIX: Allow standardized list of searchable membership levels/lists
- BUG FIX: Use 'paid-memberships-pro' as the I18N domain for PMPro specific text
- BUG FIX: Attempted to load variable when we needed to load the warning/info messages
- BUG FIX: E20R_MEMBERSLIST_VER constant no located in E20R_Members_List() definition file

## [3.1] - 

- ENHANCEMENT: Added banner and icon

## [3.0] - 

- ENHANCEMENT: Pushing to WordPress.org repository
- BUG FIX: Prevent defining controller class more than once

## [2.7] - 

- ENHANCEMENT: Change the names of the export .csv columns to match the expected column names for the Import Members from CSV plugin
- ENHANCEMENT: Added PHPDoc block for the Members_List::metadata_where() method
- ENHANCEMENT: WPCS formatting of Members_List() class
- ENHANCEMENT: Updated the Utilities sub-module
- BUG FIX: An update would sometimes get ignored (not saved)

## [2.6] - 

- BUG FIX: Didn't include the values for any defined extra columns

## [2.5] - 

- ENHANCEMENT: Let a developer change the 'expiration' ('last') column label with the 'e20r_members_list_enddate_col_name' filter
- ENHANCEMENT: Let a developer change the enddate value to match the (new?) 'last' column value (expiration date) with the 'e20r_members_list-enddate_col_result' filter

## [2.4] - 

- BUG FIX: Allow user to reset search
- BUG FIX: Incorrect # of found records returned on search
- BUG FIX: Error looking up user ID for metadata
- BUG FIX: Clean URL (GET params from URL) if user has (just) searched

## [2.2] - 

- BUG FIX: No longer supporting per line 'Cancel Membership' link

## [2.1] - 

- BUG FIX: Clear search field when user clicks 'Update List' button
- BUG FIX: Show 'Invalid' if the startdate value is incorrect (not a current/real date value)
- ENHANCEMENT: Add build tools & one-click update support tools
- ENHANCEMENT: Single license text instance
- ENHANCEMENT: Load class if not previously defined
- ENHANCEMENT: WordPress Style updates
- ENHANCEMENT: Improved English grammar in error message

## [2.0] - 

- Initial public release
