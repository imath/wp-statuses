# WP Statuses

As described in [#12706](https://core.trac.wordpress.org/ticket/12706), the WordPress Publish Metabox does not support custom statuses so far. The Core Trac ticket also introduces some questions about the way this Metabox is designed, in particular the visibility's section of it. This plugin is first suggesting a new Publishing Metabox as shown below.

![Built in statuses in the Publishing Metabox](https://cldup.com/7_IigUCAPn.png)

**PS**: The Password protected visibility option is now included into the statuses dropdowns and labels for Private and Published has been renamed respectively to Privately published and Publicly published.

#### Custom statuses for builtin Post Types

```php
add_filter( 'wp_statuses_use_custom_status', '__return_true' )
```

Using the above filter will demonstrate how it is possible to add custom statuses for WordPress builtin Post Types.

+ A new `restricted` status for the page's post type. Pages using this status will need the user to be logged in to view their content.
+ A new `archive` status for the post's post type. Posts using this status will be removed from front-end loops and only viewable from the Archived view of the Posts Administration screen.

#### Custom statuses for Custom Post Types

In this [gist](https://gist.github.com/imath/2b6d2ce1ead6aba11c8ad12c6beb4770) a new Post Type named "ticket" is registered along with custom statuses. It's a tiny use case showing how you can __with less than 150 lines__ begin to build an Issue reporting system. The ticket's post type has 5 statuses:

+ The WordPress builtin Draft and Pending statuses,
+ Three custom statuses: `assigned`, `resolved` & `invalid`, as shown below.

![Custom statuses in the Publishing Metabox](https://cldup.com/fggsxk5-O0.png)

WP Statuses also takes care of the Bulk Edit and Quick Edit actions of the Post types administration screens. Below is a screen capture of the Quick Edit form for an item of the ticket's post type.

![Quick edit](https://cldup.com/sr8ggoKZb5.png)

#### Registering a custom status

To register a custom status and make it appear into the Post Type's UIs, you will use the WordPress' `register_post_status()` function making sure to add these WP Statuses specific arguments. Below is the list of them.

|Type| Name | Description |
| --- | --- | --- |
| Array | `post_type` | The list of post type names the status should be applied to |
| bool | `show_in_metabox_dropdown` | Whether to show the status in the Publishing Metabox |
| bool | `show_in_inline_dropdown` | Whether to show the status in the Bulk Edit and Quick Edit forms |
| Array | `labels` | An associative array containing the labels for the two previous contexts. If there are not defined, The value of the `label` argunent of the `register_post_status()` function will be used. Keys are `metabox_dropdown` for the Publishing Metabox and `inline_dropdown` for Bulk/Quick Edit forms |
| String | `dashicon` | The dachicon's name |

For example, to add an `archive` custom status to Posts, you can use the WordPress function this way:

```php
register_post_status( 'archive', array(
		/* WordPress built in arguments. */
		'label'                       => __( 'Archive', 'wp-statuses' ),
		'label_count'                 => _n_noop( 'Archived <span class="count">(%s)</span>', 'Archived <span class="count">(%s)</span>', 'wp-statuses' ),
		'public'                      => false,
		'show_in_admin_all_list'      => false,
		'show_in_admin_status_list'   => true,

		/* WP Statuses specific arguments. */
		'post_type'                   => array( 'post' ), // Only for posts!
		'show_in_metabox_dropdown'    => true,
		'show_in_inline_dropdown'     => true,
		'show_in_press_this_dropdown' => true,
		'labels'                      => array(
			'metabox_dropdown' => __( 'Archived',        'wp-statuses' ),
			'inline_dropdown'  => __( 'Archived',        'wp-statuses' ),
		),
		'dashicon'                    => 'dashicons-archive',
	) );
```

## Configuration needed

+ WordPress 4.7

## Installation

Before activating the plugin, make sure all the files of the plugin are located in `/wp-content/plugins/wp-statuses` folder.
