=== <Plugin Name> ===
Contributors: eighty20results
Tags: paid memberships pro, members, memberships, pmpro enhancements, better members list, members list
Requires at least: 5.0
Tested up to: 1.0
Requires PHP: 7.1
Stable tag: 1,.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl

[![Automated Tests](https://github.com/eighty20results/{$short_name}/actions/workflows/pushed-to-github.yml/badge.svg)](https://github.com/eighty20results/{$short_name}/actions/workflows/pushed-to-github.yml) [![Release plugin package](https://github.com/eighty20results/{$short_name}/actions/workflows/release-plugin.yml/badge.svg)](https://github.com/eighty20results/{$short_name}/actions/workflows/release-plugin.yml)

== Description ==

Extensible, sortable & bulk action capable members listing tool for Paid Memberships Pro. This plugin is a complete replacement for the "Members List" functionality in PMPro and supports most of the same filters and hooks. The key differences have to do with managing columns. Now you can also use the [standard WordPress filters](https://developer.wordpress.org/reference/classes/wp_list_table/) to columns you can add/remove/make sortable, additional bulk actions, etc.

== Installation ==

1. Upload the `{$short_name}` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Extending the Members List ==

This plugin uses the `WP_List_Table` class to generate the flexible table approach you know and love from the WordPress Post/Page/etc back-end. As a result, it's has a standardized and flexible approach to adding columns to the table.

I've also included a number of filters and actions to let a [PHP developer](https://eighty20results.com/need-something-else/) expand on the search functionality for the list.

The same goes for the Export to CSV functionality.

This plugin should support the standard Paid Memberships Pro filters in order to add new CSV export columns and data.

== Supported Filters ==
See [FILTERS.md](https://github.com/eighty20results.com/{$short_name}/blob/main/docs/FILTERS.md)

== Supported Actions ==
See [ACTIONS.md](https://github.com/eighty20results.com/{$short_name}/blob/main/docs/ACTIONS.md)

== Known Issues ==
No known issues at this time

== Changelog ==
See the official [CHANGELOG.md](https://github.com/eighty20results.com/{$short_name}/blob/main/CHANGELOG.md) file
