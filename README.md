WP Statuses
===========

This plugin includes some suggestions and tries to contribute to [#12706](https://core.trac.wordpress.org/ticket/12706). The Goal is to allow custom status to be included into the WordPress UIs where it's possible to edit post statuses (Publish metabox & Post types List tables inline edits). Here are two examples of the Publishing metabox that is used to replace the regular WordPress Publish Metabox.

![The Publishing Metabox](https://cldup.com/gqFKVZbBYJ.png)

Using the following filter, you'll be able to have a demo of the custom statuses integration for the page's post type:

```php
add_filter( 'wp_statuses_use_custom_status', '__return_true' )
```

Configuration needed
--------------------

+ WordPress 4.7

Installation
------------

Before activating the plugin, make sure all the files of the plugin are located in `/wp-content/plugins/wp-statuses` folder.
