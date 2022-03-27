### E20R Better Members List for Paid Memberships Pro
`Contributors: eighty20results` <br />
`Tags: paid memberships pro, members, memberships, pmpro enhancements, better members list, members list, addon` <br />
`Requires at least: 4.9` <br />
`Tested up to: 5.9.2` <br />
`Requires PHP: 7.1` <br />
`Stable tag: 8.6` <br />
`License: GPLv2` <br />
`License URI: http://www.gnu.org/licenses/gpl` <br />

[![Automated Tests](https://github.com/eighty20results/e20r-members-list/actions/workflows/pushed-to-github.yml/badge.svg)](https://github.com/eighty20results/e20r-members-list/actions/workflows/pushed-to-github.yml) [![Release plugin package](https://github.com/eighty20results/e20r-members-list/actions/workflows/release-plugin.yml/badge.svg)](https://github.com/eighty20results/e20r-members-list/actions/workflows/release-plugin.yml)

### Description

Extensible, sortable & bulk action capable members listing tool for Paid Memberships Pro. This plugin is a complete replacement for the "Members List" functionality in PMPro and supports most of the same filters and hooks. The key differences have to do with managing columns. Now you can also use the [standard WordPress filters](https://developer.wordpress.org/reference/classes/wp_list_table/) to columns you can add/remove/make sortable, additional bulk actions, etc.

### Installation

1. Upload the `e20r-members-list` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

### Extending the Members List

This plugin uses the `WP_List_Table` class to generate the flexible table approach you know and love from the WordPress Post/Page/etc back-end. As a result, it's has a standardized and flexible approach to adding columns to the table.

I've also included a number of filters and actions to let a [PHP developer](https://eighty20results.com/need-something-else/) expand on the search functionality for the list.

The same goes for the Export to CSV functionality.

This plugin should support the standard Paid Memberships Pro filters in order to add new CSV export columns and data.

### Supported Filters
See [FILTERS.md](https://github.com/eighty20results.com/e20r-members-list/blob/main/docs/FILTERS.md)

### Supported Actions
See [ACTIONS.md](https://github.com/eighty20results.com/e20r-members-list/blob/main/docs/ACTIONS.md)


### Known Issues
PHP 8.0 and later introduces warning messages for certain behaviors that were ignored prior to v8.0. Because of this, and the fact that this plugin relies on functionality from Paid Memberships Pro, the "end date" column may print messages indicating problems with the `trim()` function. Until Paid Memberships Pro updates their plugin to support PHP8.x, these messages will need to be disabled in your web server configuration (suppressed).

Setting the "Members per page" in the "Options" drop-down on the Members List page to a number greater than 50 can result in unexpected errors/warnings. The default value is 20. One symptom is seeing the PHP warning: "Warning: Unknown: Input variables exceeded 2000. To increase the limit change max_input_vars in php.ini. in Unknown on line 0"

### Changelog
See the official [CHANGELOG.md](https://github.com/eighty20results.com/e20r-members-list/blob/main/CHANGELOG.md) file
