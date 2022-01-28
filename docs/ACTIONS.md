## Actions supported by this plugin

*Note:* The Filters and Actions sections are incomplete!

To best understand how to extend this plugin, we recommend searching through the plugin sources for calls to the `apply_filter()` and `do_action()` functions.

There are also several Paid Memberships Pro specific filters present in this plugin to, as well as possible, maintain compatibility with the default PMPro Members List functionality.

### e20r_memberslist_process_bulk_updates

Purpose: Allows post-update processing during a bulk update operation, or creation of your own bulk-update operation.

Dependencies: N/A

Default: List (array) of member information supplied by the Bulk_Update::set_members() method.

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
	'e20r_memberslist_process_bulk_updates',
	function( $member_info_array ) {
		foreach( $member_info_array as $key => $user_info ) {
			// Do something to the user record based on WPUser->id and PMPro membership level ID
			...
		}
	},
	11
);
```

### e20r_memberslist_process_bulk_cancel

Purpose: Allows post-update processing during a bulk cancel operation, or creation of your own bulk-cancel operation.

Dependencies: N/A

Default: List (array) of member information supplied by the Bulk_Cancel::set_members() method.

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
	'e20r_memberslist_process_bulk_cancel',
	function( $member_info_array ) {
		foreach( $member_info_array as $key => $user_info ) {
			// Do additional cancellation operations to the user record based on WPUser->id and/or the PMPro membership level ID. I.e. clean-up, etc.
			...
		}
	},
	11
);
```
