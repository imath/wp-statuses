WP Statuses
===========

This plugin includes some suggestions and tries to contribute to [#12706](https://core.trac.wordpress.org/ticket/12706). The Goal is to allow custom status to be included into the WordPress UIs where it's possible to edit post statuses (Publish metabox & Post types List tables inline edits). Here are two examples of the Publishing metabox that is used to replace the regular WordPress Publish Metabox.

![The Publishing Metabox](https://cldup.com/gqFKVZbBYJ.png)

Using the following filter, you'll be able to have a demo of the custom statuses integration.

```php
add_filter( 'wp_statuses_use_custom_status', '__return_true' )
```

The above filter will create a `restricted` status for the Page's post type which is hiding the page's content until the user logs in.
It will also create an `archive` status for the Post's post type to remove posts from the loop without putting them into trash.

Configuration needed
--------------------

+ WordPress 4.7

Installation
------------

Before activating the plugin, make sure all the files of the plugin are located in `/wp-content/plugins/wp-statuses` folder.
