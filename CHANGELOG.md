# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## v8.6 - 2022-03-27
- BUG FIX: Revert codecov upload step (Eighty/20Results Bot on Github)
- BUG FIX: Set permissions for coverage output directory (Eighty/20Results Bot on Github)
- BUG FIX: More debug output (Eighty/20Results Bot on Github)
- BUG FIX: Typo in Makefile (Eighty/20Results Bot on Github)
- BUG FIX: Adding more debug text to makefile (Eighty/20Results Bot on Github)
- BUG FIX: Adding debug text to makefile (Eighty/20Results Bot on Github)
- BUG FIX: Adding PHP 8.1 to test matrix (Eighty/20Results Bot on Github)
- BUG FIX: Integration tests weren't doing much (Eighty/20Results Bot on Github)
- BUG FIX: Add PHP 8.1 to test matrix and revert code coverage upload (Eighty/20Results Bot on Github)
- BUG FIX: Reverted codecov upload step and adding php 8.1 to test matrix (Eighty/20Results Bot on Github)
- BUG FIX: Be explicit about the file to load in action (Eighty/20Results Bot on Github)
- BUG FIX: Different config for codecov-action (Eighty/20Results Bot on Github)
- BUG FIX: Try to fix setup for codecov/codecov-action (Eighty/20Results Bot on Github)
- BUG FIX: Update codecov configuration and rename the config file (Eighty/20Results Bot on Github)
- BUG FIX: Revert removal of PMPro dependency for PHPStan tests (Eighty/20Results Bot on Github)
- BUG FIX: Attempting to enable code coverage uploads (Eighty/20Results Bot on Github)
- BUG FIX: Adding the codecov.yml config file (Eighty/20Results Bot on Github)
- BUG FIX: PHPDoc entry for failed class variable was wrong (Eighty/20Results Bot on Github)
- BUG FIX: More unit test coverage for the bulk operations including fix for members_to_process check (Eighty/20Results Bot on Github)
- BUG FIX: Checksum for composer wasn't a make variable (Eighty/20Results Bot on Github)
- BUG FIX: Exception for when the array of users/levels to perform bulk operations is incorrect (Eighty/20Results Bot on Github)
- BUG FIX: Make coverage a local only thing (for now?) (Eighty/20Results Bot on Github)
- BUG FIX: Don't create unneeded coverage subdirectories (Eighty/20Results Bot on Github)
- BUG FIX: Fix workflow config (Eighty/20Results Bot on Github)
- BUG FIX: Use remote coverage service (Eighty/20Results Bot on Github)
- BUG FIX: Problem with coverage artifacts (Eighty/20Results Bot on Github)
- BUG FIX: Bumped version number to 8.6 (Eighty/20Results Bot on Github)
- BUG FIX: Clean up message in get_members() -> InvalidSQL exception (Eighty/20Results Bot on Github)
- BUG FIX: Not re-running workflow when PR is updated/edited (Eighty/20Results Bot on Github)
- BUG FIX: Add support for Brain\faker() mocking for WP and the ext-mysqli extension for integration testing purposes (Eighty/20Results Bot on Github)
- BUG FIX: Transition to mocking PMPro functions for Integration tests (probably moving actual PMPro integration to acceptance testing) (Eighty/20Results Bot on Github)
- BUG FIX: Make sure Integration/Acceptance test container uses PHP v7.4 (Eighty/20Results Bot on Github)
- BUG FIX: Added PHP8/Paid Memberships Pro incompatibility as a 'Known Issue' (Eighty/20Results Bot on Github)
- BUG FIX: Updated tags for Wodby docker4wordpress (Eighty/20Results Bot on Github)
- BUG FIX: Wrong number of placeholders and data in sprintf() (Eighty/20Results Bot on Github)
- BUG FIX: Clean up Known Issues text (Eighty/20Results Bot on Github)
- BUG FIX: Refactored option HTML and changed to using gmdate() (Eighty/20Results Bot on Github)
- BUG FIX: Adding support for listing multiple memberships (still have TODOs, not releasing yet) (Eighty/20Results Bot on Github)
- BUG FIX: Added a known issue (Eighty/20Results Bot on Github)
- BUG FIX: Cleaned up namespace path (Eighty/20Results Bot on Github)
- BUG FIX: No longer using singleton pattern in E20R_Members_List() class and fixed PHPCS warnings (Eighty/20Results Bot on Github)
- BUG FIX: Null value disallowed for str_replace() (Eighty/20Results Bot on Github)
- BUG FIX: Bulk-exported all users, not just the selected ones and fixed PHP Deprecated messages for bulk action checks (Eighty/20Results Bot on Github)
- BUG FIX: Adding tests with data for column_last() method (Eighty/20Results Bot on Github)
- BUG FIX: Fix column_last() to avoid PHP Warning messages and refactor, make the definition of an 'empty' enddate filtered and add helper methods. Added exceptions and handlers (Eighty/20Results Bot on Github)
- BUG FIX: Add e20r_members_list_empty_date_values filter documentation (Eighty/20Results Bot on Github)
- BUG FIX: Cleanup patchwork.json (Eighty/20Results Bot on Github)
- BUG FIX: Variables rely on tests/_env/.env.testing (Eighty/20Results Bot on Github)
- BUG FIX: Run install for WP and set only DEBUG variable (Eighty/20Results Bot on Github)
- BUG FIX: Make sure we have the needed database tables for testing (Eighty/20Results Bot on Github)
- BUG FIX: Actual path to source files for e20r utilities module is src/E20R/... (Eighty/20Results Bot on Github)
- BUG FIX: PMPro doesn't check if constants are defined before use triggering test failures for us (Eighty/20Results Bot on Github)
- BUG FIX: Load fixtures to load/clear the DB (Eighty/20Results Bot on Github)
- BUG FIX: Lacked DB records for PMPro and User in integration tests (Eighty/20Results Bot on Github)
- BUG FIX: Wrong path to test specific _bootstrap.php file (Eighty/20Results Bot on Github)
- BUG FIX: Didn't load test specific _bootstrap.php files (Eighty/20Results Bot on Github)
- BUG FIX: Initial commit for column_last integration test (Eighty/20Results Bot on Github)
- BUG FIX: Add WP_DEBUG logging for test container(s) (Eighty/20Results Bot on Github)
- BUG FIX: Use E20R_PLUGIN_NAME env variable to set PROJECT_NAME variable (with default value set to plugin slug) (Eighty/20Results Bot on Github)
- BUG FIX: Upgraded wodby container environments (Eighty/20Results Bot on Github)
- BUG FIX: Fixed install-hooks for pre-commit git hook (Eighty/20Results Bot on Github)
- BUG FIX: Path to documentation (Eighty/20Results Bot on Github)
- BUG FIX: Make sure we strip away the test file (Eighty/20Results Bot on Github)

## v8.5 - 2022-02-04
- BUG FIX: Kept trying to build for main branch in the test workstream (Eighty/20Results Bot on Github)
- BUG FIX: Attempting to remove the wrong build directory (Eighty/20Results Bot on Github)
- BUG FIX: Provide feedback if .zip file exists (Eighty/20Results Bot on Github)
- BUG FIX: Clean up line lengths (Eighty/20Results Bot on Github)
- BUG FIX: Trying to figure out why the test deployment workflow fails (Eighty/20Results Bot on Github)
- BUG FIX: Remove unused variable (Eighty/20Results Bot on Github)
- BUG FIX: Clean up workflow definitions (Eighty/20Results Bot on Github)
- BUG FIX: Typo in remove_update.sh (Eighty/20Results Bot on Github)
- BUG FIX: Refactored the remove_update.sh script which strips the non-WP approved one-click updater (Eighty/20Results Bot on Github)
- BUG FIX: Fix path for skeleton files in .gitignore and update URL for docs (Eighty/20Results Bot on Github)
- BUG FIX: Update .gitattributes with new CI/CD helpers (Eighty/20Results Bot on Github)
- BUG FIX: Clean up the workflow file and add hints about how to trigger different deployment outcomes (Eighty/20Results Bot on Github)
- BUG FIX: Fix path to FILTERS.md and ACTIONS.md in README.{md,txt} (Eighty/20Results Bot on Github)
- BUG FIX: Still relying on BUILD_DIR which isn't needed! (Eighty/20Results Bot on Github)
- BUG FIX: Run dependabot on a cron schedule (6am every day) (Eighty/20Results Bot on Github)
- BUG FIX: Run dependabot whenever we push to the repo. (Eighty/20Results Bot on Github)
- BUG FIX: Don't need a separate deploy-to-wp action for GitHub (Eighty/20Results Bot on Github)
- BUG FIX: Various updates for the build/deploy process (Eighty/20Results Bot on Github)
- BUG FIX: Various updates to make the wordpress.org deployment work as expected (Eighty/20Results Bot on Github)
- BUG FIX: deploy.sh didn't work as expected (Eighty/20Results Bot on Github)
- BUG FIX: Make sure the .zip file exists as part of the build step (Eighty/20Results Bot on Github)
- BUG FIX: Skip all of the stuff we don't need in the SVN repo (Eighty/20Results Bot on Github)
- Add .gitattributes file (Eighty/20Results Bot on Github)
- BUG FIX: Several fixes for deployment script(s) (Eighty/20Results Bot on Github)
- BUG FIX: Include needed utilities for deploy.sh in Dockerfile.unittest (Eighty/20Results Bot on Github)
- Add .gitattributes file (Eighty/20Results Bot on Github)
- BUG FIX: More deploy.sh script fixes (Thomas Sjolshagen)
- BUG FIX: deploy.sh fixes for wordpress.org deployment (Thomas Sjolshagen)
- BUG FIX: Workflow step-conditional clean-up. (Thomas Sjolshagen)
- BUG FIX: Remove the concept of binaries in a BUILD_DIR for wordpress.org deployments (Thomas Sjolshagen)
- BUG FIX: Updates to properly trigger the deploy.sh script (Thomas Sjolshagen)
- BUG FIX: Make sure the BUILD_DIR environment variable is set for the deploy target (Thomas Sjolshagen)
- BUG FIX: Make sure we mock the svn command too when running against a non-release branch/tag (Thomas Sjolshagen)
- BUG FIX: Needed the BUILD_DIR environment variable to be set (Thomas Sjolshagen)
- BUG FIX: Clean up step names (Thomas Sjolshagen)
- BUG FIX: Just won't check if the SSH hostname is set without failing (Thomas Sjolshagen)
- BUG FIX: Updated name of workflow to reflect its test status (Thomas Sjolshagen)
- BUG FIX: Simplify env variable presence check(s) (Thomas Sjølshagen)
- BUG FIX: One more place where the wrong keyword was used (Thomas Sjolshagen)
- BUG FIX: Workflow file doesn't parse (Thomas Sjolshagen)
- BUG FIX: Set bash as default shell in workflow (Thomas Sjølshagen)
- Testing the workflow editor (Thomas Sjølshagen)
- BUG FIX: Trying to fix a syntax error in test github action (Thomas Sjolshagen)
- BUG FIX: Refactored the environment variable definitions (Thomas Sjolshagen)
- BUG FIX: Too broad brushed the update to the test workflow (Thomas Sjolshagen)
- BUG FIX: Separate deployment to wordpress.org and WooCommerce store (Thomas Sjolshagen)
- BUG FIX: Updated metadata.json for v8.5 and WP 5.9 (Thomas Sjolshagen)
- BUG FIX: Updated README info (v8.5 for WP 5.9) (Thomas Sjolshagen)
- BUG FIX: Updated CHANGELOG (v8.5 for WP 5.9) (Thomas Sjolshagen)
- BUG FIX: Couldn't always build a git log (set MAIN_BRANCH_NAME := main in Makefile config) (Thomas Sjolshagen)
- 🔄 Generated POT File (WordPress .pot File Generator)
- BUG FIX: Typos in the plugin list to activate (Thomas Sjolshagen)
- BUG FIX: Disallow loading the same plugin source twice in ActivateUtilitiesPlugin() (Thomas Sjolshagen)
- BUG FIX: Clean up docker-compose file for manual testing (Thomas Sjolshagen)
- BUG FIX: Refactored to include all plugin dependencies (Thomas Sjolshagen)
- BUG FIX: Include the WooCommerce dependency (Thomas Sjolshagen)
- BUG FIX: WPCS compliance update (Thomas Sjolshagen)
- BUG FIX: Update unit tests for full coverage (Thomas Sjolshagen)
- BUG FIX: Update PHPDoc string for get_members() method (Thomas Sjolshagen)
- BUG FIX: Fix unit tests for the Bulk_Cancel() and Bulk_Operations() classes (Thomas Sjolshagen)
- BUG FIX: Use more clear request variable name for user IDs to perform bulk operations against and refactor the custom bulk action trigger (Thomas Sjolshagen)
- BUG FIX: Didn't show the missing parameter in exception message (Thomas Sjolshagen)
- BUG FIX: PHPdoc for execute() method and skip error message if no level IDs were specified as failed. (Thomas Sjolshagen)
- BUG FIX: Refactored export button and bulk-export functionality in e20r-memberslist-page.js (Thomas Sjolshagen)
- BUG FIX: Documentation describing how to add and process custom bulk action(s) for selected users. (Thomas Sjolshagen)
- BUG FIX: Now passing it_tries_to_cancel_membership_for_the_specified_wpuser_id (Thomas Sjolshagen)
- BUG FIX: Regenerated UnitTesterActions.php (Thomas Sjolshagen)
- BUG FIX: Use exception when PMPro is not detected (Thomas Sjolshagen)
- BUG FIX: Didn't ignore the PHPUnit cache (Thomas Sjolshagen)
- BUG FIX: Add the php-mock composer package (Thomas Sjolshagen)
- BUG FIX: Typo in bootstrap setting (Thomas Sjolshagen)
- BUG FIX: Initial commit for the PMProNotActive.php exception (Thomas Sjolshagen)
- BUG FIX: Handle new exceptions and Bulk_*() operation class refactoring (Thomas Sjolshagen)
- BUG FIX: Refactored for different Bulk_* operation classes with common base class Bulk_Operations() (Thomas Sjolshagen)
- BUG FIX: Initial commit of parent Bulk_Operations() class (Thomas Sjolshagen)
- BUG FIX: Initial commit of InvalidProperty() class (Thomas Sjolshagen)
- BUG FIX: Invalid @package description (Thomas Sjolshagen)
- BUG FIX: Stopped using CircleCI for testing (Thomas Sjolshagen)
- BUG FIX: Don't include our own build file in the container (Thomas Sjolshagen)
- BUG FIX: Initial commit of Bulk_Cancel_UnitTest.php (Thomas Sjolshagen)
- BUG FIX: Add required Namespaces for unit tests (Thomas Sjolshagen)
- BUG FIX: Removed wpunit.suite.yml since we now call those types of tests integration tests (Thomas Sjolshagen)
- BUG FIX: Wrong filename for the Codeception bootstrap loader (Thomas Sjolshagen)
- BUG FIX: Make sure we load the required Utilities class definition for testing purposes (Thomas Sjolshagen)
- BUG FIX: Remove ignores for fixed PHPStan errors (Thomas Sjolshagen)
- BUG FIX: Refactored cancel_member() method in Bulk_Cancel.php (Thomas Sjolshagen)
- BUG FIX: Document filters and actions in Bulk_Cancel() (Thomas Sjolshagen)
- BUG FIX: Remove stale/unused code in Bulk_Cancel.php (Thomas Sjolshagen)
- BUG FIX: Document filters and actions in Bulk_Update() (Thomas Sjolshagen)
- BUG FIX: Remove stale/unused code (Thomas Sjolshagen)
- BUG FIX: Clean up and fix tests after refactoring (Thomas Sjolshagen)
- BUG FIX: Add try/catch for MixpanelConnector() instantiation (Thomas Sjolshagen)
- BUG FIX: Make sure default value is something other than false (Thomas Sjolshagen)
- BUG FIX: Autogenerated and updated (Thomas Sjolshagen)
- BUG FIX: No longer using a custom autoloader and moved tests (Thomas Sjolshagen)
- BUG FIX: Allowed whitespace with numeric membership level(s) for generating SQL (Thomas Sjolshagen)
- BUG FIX: Didn't load the plugin(s) in the expected order (Thomas Sjolshagen)
- BUG FIX: Add the Utilities and PMPro plugin paths to the docker-compose.yml file (Thomas Sjolshagen)
- BUG FIX: Refactored sources not accounted for in codeception.dist.yml (Thomas Sjolshagen)
- BUG FIX: Add support for the E20R Utilities Module to be mounted into the test container (Thomas Sjolshagen)
- BUG FIX: Didn't activate WP plugins we depend on (Thomas Sjolshagen)
- BUG FIX: 'level' should default to 'active' or whatever the developer chose via the filter (Thomas Sjolshagen)
- BUG FIX: Didn't include level in search and using URL redirect to handle search & level update(s) (Thomas Sjolshagen)
- BUG FIX: Typo in workflow name (Thomas Sjolshagen)
- BUG FIX: Refactored autoloader and ActivateUtilitiesPlugin() loader (Thomas Sjolshagen)
- BUG FIX: Fix readme templates and files (adding badges, tags) (Thomas Sjolshagen)
- BUG FIX: Activating wrong plugin for integration testing (Thomas Sjolshagen)
- BUG FIX: Use local paths for plugin and dependencies for dockerized testing on laptop (Thomas Sjolshagen)
- BUG FIX: Workflows now using standard scripts (Thomas Sjolshagen)
- BUG FIX: Clean up the composer.json file (Thomas Sjolshagen)
- BUG FIX: Clean up _bootstrap.php files (Thomas Sjolshagen)
- BUG FIX: Always triggering die() due to misconfigured check for certain constants (Thomas Sjolshagen)
- BUG FIX: PHPStan detected errors/problems (Thomas Sjolshagen)
- BUG FIX: ignoreErrors entry for poorly documented function in related plugin (Thomas Sjolshagen)
- BUG FIX: Allow PHPStan testing without exiting for Sort_By_Meta.php and fix PHPStan indicated error (Thomas Sjolshagen)
- BUG FIX: Allow PHPStan testing without dying for Bulk_Update.php (Thomas Sjolshagen)
- BUG FIX: Allow PHPStan testing without dying for Export_Members.php, and fix PHPStan issues (Thomas Sjolshagen)
- BUG FIX: Fix PHPStan errors and allow PHPStan testing without dying (Thomas Sjolshagen)
- BUG FIX: Refactored MMPU module and fixed PHPStan errors (Thomas Sjolshagen)
- BUG FIX: Renamed to Multiple_Memberships() and allowing PHPStan testing without WP loaded (Thomas Sjolshagen)
- BUG FIX: Couldn't load for testing, PHPStan errors, (Thomas Sjolshagen)
- BUG FIX: PHPDoc string is incomplete for constructor (Thomas Sjolshagen)
- BUG FIX: Fix path to Integration Test dockerfile (now tests/_docker) (Thomas Sjolshagen)
- BUG FIX: Add more plugin info for wp-admin page and fix action hook definitions (Thomas Sjolshagen)
- BUG FIX: Ignore the pmpro_* specific properties assigned to WP_User in PHPStan scans (Thomas Sjolshagen)
- BUG FIX: Build .pot file(s) whenever the pull request is created (Thomas Sjolshagen)
- BUG FIX: Allow execution of a single unit test (Thomas Sjolshagen)
- BUG FIX: Didn't configure the MixpanelConnector() properly (Thomas Sjolshagen)
- BUG FIX: Refactor cache management (Thomas Sjolshagen)
- BUG FIX: Simplify unit/integration testing. Fix export-to-csv issues, respect WP date format setting when displaying data, add filter to let user define default status to use in list, use exceptions for more stuff, add caching for performance, make it possible to support MMPU (later). (Thomas Sjolshagen)
- BUG FIX: Let user specify name for the export file (.csv) (Thomas Sjolshagen)
- BUG FIX: Fix the CSV download issue by using AJAX based exports. Fix intermittent search issues. Add getter for Members_List_Page() class. Enable mocking for unit/integration testing. (Thomas Sjolshagen)
- BUG FIX: Add support for Unit & Integration testing, WPCS improvements, disallow direct load of sources, and initial work to support MMPU in the future (Thomas Sjolshagen)
- BUG FIX: Add support for Unit & Integration testing, WPCS improvements and disallow direct load of sources. (Thomas Sjolshagen)
- BUG FIX: Exclude JavaScript source files from PHPStan checks (Thomas Sjolshagen)
- BUG FIX: Avoid double loading of source file(s) (Thomas Sjolshagen)
- BUG FIX: Use the E20R Utilities Module, add Mixpanel metrics and add support for mocking when unit/integration testing (Thomas Sjolshagen)
- BUG FIX: Visual cue when processing export operation (Thomas Sjolshagen)
- BUG FIX: Initial stub for the Multiple Memberships Per User add-on (Thomas Sjolshagen)
- BUG FIX: Incorrect path to Integration tests (Thomas Sjolshagen)
- BUG FIX: Mode flexible codeception require-dev section (Thomas Sjolshagen)
- BUG FIX: Latest Makefile fixes (Thomas Sjolshagen)
- BUG FIX: Include inc/ (assumes composer-prod only) and languages/ when building the .zip archive (Thomas Sjolshagen)
- BUG FIX: Skip tests/ directory during PHPStan scanning (Thomas Sjolshagen)
- BUG FIX: Add composer modules used in production to the local test docker environment and rename the prepare-docker.sh script to prepare-local-test-environment.sh (Thomas Sjolshagen)
- BUG FIX: Using exceptions to handle issues with SQL or DB connectivity/querying. (Thomas Sjolshagen)
- BUG FIX: Adding new DB exception (for when there's a problem with executing a DB query) (Thomas Sjolshagen)
- BUG FIX: Refactoring for namespace/directory, proper escaping of I18N string and various WPCS, PHPStan updates to Bulk_Update.php (Thomas Sjolshagen)
- BUG FIX: Refactoring for namespace/directory, proper escaping of I18N string and various WPCS, PHPStan updates to Export_Members.php (Thomas Sjolshagen)
- BUG FIX: Refactoring for namespace/directory, more clear exceptions used, proper escaping of I18N string, fixed CSS/JS loading as a result and various WPCS, PHPStan updates to Members_List.php (Thomas Sjolshagen)
- BUG FIX: Refactoring for namespace/directory, proper escaping of I18N string and various WPCS, PHPStan updates to Bulk_Cancel.php (Thomas Sjolshagen)
- BUG FIX: WPCS, PHPStan and namespace/directory updates to Sort_By_Meta.php (Thomas Sjolshagen)
- BUG FIX: Refactored exceptions for directory/namespace updates (Thomas Sjolshagen)
- BUG FIX: Refactored namespace/directories (Thomas Sjolshagen)
- BUG FIX: WPCS compliance updates and proper escaping of strings ( esc_attr__() vs __() ) (Thomas Sjolshagen)
- BUG FIX: Standardized PHP functions we can mock for testing across E20R plugins (Thomas Sjolshagen)
- BUG FIX: Match version number in package.json to plugin version (Thomas Sjolshagen)
- BUG FIX: The WP add_action() function is missing when doing PHPStan analysis and fixed plugin comment section per WPCS requirements. (Thomas Sjolshagen)
- BUG FIX: The WP add_action() function is missing when doing PHPStan analysis (Thomas Sjolshagen)
- BUG FIX: Updated copyright notice for 2022 (Thomas Sjolshagen)
- BUG FIX: Reformated phpunit.xml for readability (Thomas Sjolshagen)
- BUG FIX: Moved to support namespace/directory alignment (Thomas Sjolshagen)
- BUG FIX: Allow PHPStan test infra to install extra plugins as needed. Add mixpanel support. (Thomas Sjolshagen)
- BUG FIX: MySQL listening port conflict no test server (switch to 3307 for published port in docker stack) (Thomas Sjolshagen)
- BUG FIX: Fix global .gitignore to skip the correct files in build/ (Thomas Sjolshagen)
- BUG FIX: Couldn't always clean inc/ (Thomas Sjolshagen)
- BUG FIX: Missing E20R_PLUGIN_NAME environment variable setting in release-plugin workflow (Thomas Sjolshagen)
- BUG FIX: Incorrect paths for PHPStan execution against plugin sources (Thomas Sjolshagen)
- BUG FIX: Clean-up and prerequisite targets were faulty (Thomas Sjolshagen)
- ENHANCEMENT: Adding exception for the Utilities module (Thomas Sjolshagen)
- ENHANCEMENT: Use custom exceptions for SQL generation (Thomas Sjolshagen)
- BUG FIX: Use separate Utilities module (Thomas Sjolshagen)
- ENHANCEMENT: Streamline the configuration of the build commands (Makefile and various build scripts) (Thomas Sjolshagen)
- ENHANCEMENT: More automation for the CI/CD pipeline (Thomas Sjolshagen)
- BUG FIX: More refactoring of the automated testing (Thomas Sjolshagen)
- ENHANCEMENT: Adding CODEOWNERS for the project (Thomas Sjolshagen)
- BUG FIX: Updated the codeception configuration (Thomas Sjolshagen)
- BUG FIX: Refactoring automated testing (Thomas Sjolshagen)
- BUG FIX: Refactoring for separate Utilities module (Thomas Sjolshagen)
- BUG FIX: Expanded composer configuration and updated autoloads (Thomas Sjolshagen)
- ENHANCEMENT: Refactored the documentation for Actions and Filters (Thomas Sjolshagen)
- BUG FIX: Updated to latest Github Action Workflow definitions (Thomas Sjolshagen)
- BUG FIX: Dependency and code standards fixes (Thomas Sjolshagen)
- BUG FIX: Local test server updates for docker config (Thomas Sjolshagen)
- BUG FIX: Updating Makefile to support new build/test infrastructure (Thomas Sjolshagen)
- BUG FIX: Remove CircleCI dependency (Thomas Sjolshagen)
- BUG FIX: Didn't set the date format to match the WP setting as documented (Thomas Sjolshagen)
- BUG FIX: Make sure the manifest exists before trying to pull the docker image (Thomas Sjolshagen)
- BUG FIX: Refactored export_members class and better error handling when transmitting to client browser. (Thomas Sjolshagen)
- BUG FIX: Updates to troubleshoot codeception start errors (Thomas Sjolshagen)
- BUG FIX: Make sure the dependencies are installed (Thomas Sjolshagen)
- BUG FIX: Wrong job name. Now wp-unit-test (Thomas Sjolshagen)
- BUG FIX: Adding wp unit testing to Github action(s) (Thomas Sjolshagen)
- BUG FIX: Incorrect path(s) to composer and PHP (Thomas Sjolshagen)
- BUG FIX: Didn't know where composer was installed (Thomas Sjolshagen)
- BUG FIX: Update PHP (Thomas Sjolshagen)
- BUG FIX: Override from environment (if set) (Thomas Sjolshagen)
- BUG FIX: Enable dependabot for Github actions (Thomas Sjolshagen)
- BUG FIX: Syntax error in Makefile (Thomas Sjolshagen)
- BUG FIX: Indentation error (Thomas Sjolshagen)
- BUG FIX: Don't use Makefile repo-login target (Thomas Sjolshagen)
- BUG FIX: Syntax error in conditional (Thomas Sjolshagen)
- BUG FIX: Attempt to exit if CONTAINER_ACCESS_TOKEN hasn't been defined (Thomas Sjolshagen)
- BUG FIX: Didn't account for environment setting for DOCKER password (Thomas Sjolshagen)
- BUG FIX:  Error: Cannot perform an interactive login from a non TTY device (Thomas Sjolshagen)
- BUG FIX: Access token as an environment variable (Thomas Sjolshagen)
- BUG FIX: docker hub login not working (Thomas Sjolshagen)
- BUG FIX: Wrong option for curl and set make options (Thomas Sjolshagen)
- BUG FIX: Silence curl during download (Thomas Sjolshagen)
- BUG FIX: Can't make install docker-compose (Thomas Sjolshagen)
- BUG FIX: Typo for secrets context (Thomas Sjolshagen)
- BUG FIX: Include environment variables for make commands (Thomas Sjolshagen)
- BUG FIX: Need to include docker-compose (Thomas Sjolshagen)
- BUG FIX: Make sure we include the right directory to clean (Thomas Sjolshagen)
- BUG FIX: Revert change to token variable name (Thomas Sjolshagen)
- BUG FIX: The secret is called GITHUB, not GITHUB_TOKEN (Thomas Sjolshagen)

## [8.4] - 2021-03-21
- BUG FIX: Remove old one-click updater (Thomas Sjolshagen)

## [8.3] - 2021-03-21
- BUG FIX: Didn't remove the custom one-click updater properly (Thomas Sjolshagen)

## [8.2] - 2021-03-21
- BUG FIX: Incorrect version updated during build (v8.2 for WP 5.7) (Thomas Sjolshagen)
- BUG FIX: Not enough whitespace in changelog.md (Thomas Sjolshagen)
- BUG FIX: metadata.json not being updated correctly (Thomas Sjolshagen)
- BUG FIX: Updated version info (Thomas Sjolshagen)
- BUG FIX: Typo for codeception config file and didn't exclude docker dir from svn (Thomas Sjolshagen)
- BUG FIX: Wrong format for time command (Thomas Sjolshagen)

## [8.1] - 2021-03-21
- BUG FIX: Updated version to 8.1 (Thomas Sjolshagen)
- BUG FIX: Didn't remove all of the non-essential files before pushing to the WP SVN (Thomas Sjolshagen)

## [8.0] - 2021-03-20
- BUG FIX: Auto-generate the change log from the commit log (Thomas Sjolshagen)
- BUG FIX: Didn't have coverage set up correctly (Thomas Sjolshagen)
- BUG FIX: Errors generating changelog.md (Thomas Sjolshagen)
- BUG FIX: Various bugs in build scripts (Thomas Sjolshagen)
- BUG FIX: Added some very basic unit tests for the auto_loader() function (Thomas Sjolshagen)
- BUG FIX: Renamed the plugin source file so updated the .pot header (Thomas Sjolshagen)
- BUG FIX: Various updates to get PHPCS WPCS syntax checking to work as expected and Unit tests to pass (Thomas Sjolshagen)
- BUG FIX: Not allowed to override require_once (Thomas Sjolshagen)
- BUG FIX: Warnings when there are no member records (Thomas Sjolshagen)
- BUG FIX: Unit tests for load_hooks() and get_instance() (Thomas Sjolshagen)
- BUG FIX: Setting the record count in the constructor is silly (Thomas Sjolshagen)
- BUG FIX: Adding and allowing execution of first (local) Unit test (Thomas Sjolshagen)
- BUG FIX: Wrong path to utilities classes after renaming directory from class to src (Thomas Sjolshagen)
- BUG FIX: Not checking if Utilities module is present (Thomas Sjolshagen)
- BUG FIX: Various refactoring and adding unit test framework (Thomas Sjolshagen)
- BUG FIX: WPCS compliance updates (Thomas Sjolshagen)
- BUG FIX: Initial work on fixing some of issues with the search functionality (Thomas Sjolshagen)
- BUG FIX: Added ability to create a release from a commit/action (Thomas Sjolshagen)
- BUG FIX: Didn't make sure the data is avilable before exporting (Thomas Sjolshagen)
- BUG FIX: Exclude the .lock file (Thomas Sjolshagen)
- BUG FIX: Catch autoLoader() exceptions (Thomas Sjolshagen)
- BUG FIX: Auto-generate required documentation files (README, CHANGELOG and metadata.json files) (Thomas Sjolshagen)
- BUG FIX: Didn't include the (new) CHANGELOG.md file in the plugin build script (Thomas Sjolshagen)
- BUG FIX: Using the github.com Issues page for the plugin (Thomas Sjolshagen)
- ENH: Added skeleton (template) files for documentation, etc. (Thomas Sjolshagen)
- BUG FIX: Didn't install the composer dependencies (Thomas Sjolshagen)
- BUG FIX: Exclude the inc/ directory from the Wordpress.org repo (Thomas Sjolshagen)
- ENH: Adding composer file for unit testing, CircleCI integration, etc (Thomas Sjolshagen)
- BUG FIX: Didn't exclude everything (Thomas Sjolshagen)
- BUG FIX: Allow IDE to connect to docker hosted DB (Thomas Sjolshagen)
- BUG FIX: Refactored (Thomas Sjolshagen)
- ENH: Adding new filter to documentation secton (Thomas Sjolshagen)
- ENH: Adding support for filter to set default sort order - e20r_memberslist_default_sort_order (Thomas Sjolshagen)
- BUG FIX: Using explicit namespace paths (Thomas Sjolshagen)
- BUG FIX: Refactored to use Class::plugin_slug for translation strings (Thomas Sjolshagen)
- BUG FIX: Refactored for silly max-char (self enforced) limits (Thomas Sjolshagen)
- BUG FIX: Removed stale commented code (Thomas Sjolshagen)
- BUG FIX: Implicitly defined variable fixes (Thomas Sjolshagen)
- BUG FIX: Didn't automatically search for levels only when selected level is changed (Thomas Sjolshagen)

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
- ENHANCEMENT: Standardize translation domain string with a class constant 'e20r-members-list'
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
- ENHANCEMENT: Let a developer change the enddate value to match the (new?) 'last' column value (expiration date) with the 'e20r_members_list_enddate_col_result' filter

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
