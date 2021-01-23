=== Eighty/20 Results Utilities Module ===
Contributors: eighty20results
Tags: e20r-utilities, module, licensing, tools
Requires at least: 5.0
Tested up to: 5.6
Stable tag: 1.0.8

Adds various utility functions and license capabilities for Eighty/20 Results developed plugins

== Description ==
The plugin consolidates required functionality for a number of Eighty / 20 Results developed plugins.

== Installation ==

1. Upload the `00-e20r-utilities` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Reporting Issues/Problems ==
Please report all issues/problems on the [plugin's GitHub 'Issues' page](https://github.com/eighty20results/Utilities/issues)

= Changelog =

== v1.0.8 ==

* BUG FIX: Problems updating update module call-out in other plugins embedding 00-e20r-utilities

== v1.0.7 ==

* BUG FIX: Exception handling in autoloader

== v1.0.6 ==

* BUG FIX: Need the path to the plugin for plugin-update-checker to work
* BUG FIX: Shellcheck updates
* BUG FIX: Typo in changelog script
* ENH: Adding changelog generation and updating metadata

== v1.0.4 ==

* BUG FIX: Wrong path when loading the plugin update checker
* ENH: Bumping version number and change log management logic


== 1.0.3 ==
* BUG FIX: Attempting to fix plugin updater

== 1.0.2 ==
* BUG FIX: Path to the GDPR policy template HTML file was incorrect after the refactor

== 1.0.1 ==
* BUG FIX: Make sure this loads as one of the very first plugin(s)

== 1.0 ==
* Initial release of the E20R Utilities module (plugin)
