## Actions supported by this plugin

*Note:* The Filters and Actions sections are incomplete!

To best understand how to extend this plugin, we recommend searching through the plugin sources for calls to the `apply_filter()` and `do_action()` functions.

There are also several Paid Memberships Pro specific filters present in this plugin to, as well as possible, maintain compatibility with the default PMPro Members List functionality.

### e20r_memberslist_process_bulk_{$operation}_done

Purpose: Allows post-update processing after a bulk operation, or creation of your own bulk operation. Use the `Bulk_Cancel::execute()` or `Bulk_Update::execute()` class methods as examples.

Dependencies: N/A

Default: List (array) of member information supplied by the calling function

```
$member_info = array(
	array(
		'user_id'  => $user_id,
		'level_id' => $user_level,
	),
		array(
		'user_id'  => $user_id,
		'level_id' => $user_level,
	),
	...
);
```
Example:
```
add_action(
	'e20r_memberslist_process_bulk_updates_done',
	function( $member_info_array ) {
		foreach( $member_info_array as $key => $user_info ) {
			// Do something to the user record based on WP_User->id and PMPro membership level ID
			...
		}
	},
	11
);
```

```
add_action(
	'e20r_memberslist_process_bulk_cancel_done',
	function( $member_info_array ) {
		foreach( $member_info_array as $key => $user_info ) {
			// Cancel the membership level(s) for the user record based on WP_User->id
			...
		}
	},
	11
);
```
