/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Edit from './edit';
import Save from "./save";
const el = wp.element.createElement;

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType( 'image-upload-via-imgur/upload', {
	title: __( 'Image Upload via Imgur', 'image-upload-for-imgur' ),
	description: __('Provides a Gutenberg block to upload an image to imgur via API.', 'image-upload-for-imgur'),
  icon: el('img', {src: ' data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAJCAYAAAAo/ezGAAABhmlDQ1BJQ0MgcHJvZmlsZQAAKJF9kT1Iw0AcxV9TpX5UHewg4pChFgcLoiKOUsUiWChthVYdTC79giYNSYqLo+BacPBjserg4qyrg6sgCH6AODs4KbpIif9LCi1iPTjux7t7j7t3gFArMdXsmABUzTIS0YiYzqyKvld0ox+9CGFMYqYeSy6m0HZ83cPD17swz2p/7s/Rp2RNBnhE4jmmGxbxBvHMpqVz3icOsIKkEJ8Tjxt0QeJHrssuv3HOOyzwzICRSswTB4jFfAvLLcwKhko8TRxUVI3yhbTLCuctzmqpwhr35C/0Z7WVJNdpjiCKJcQQhwgZFRRRgoUwrRopJhK0H2njH3b8cXLJ5CqCkWMBZaiQHD/4H/zu1sxNTbpJ/gjQ+WLbH6OAbxeoV237+9i26yeA9xm40pr+cg2Y/SS92tSCR8DANnBx3dTkPeByBxh60iVDciQvTSGXA97P6JsywOAt0LPm9tbYx+kDkKKulm+Ag0MglKfs9Tbv7mrt7d8zjf5+AKwQcr5IxI7XAAAABmJLR0QAIgAmANmBjZc5AAAACXBIWXMAAA3XAAAN1wFCKJt4AAAAB3RJTUUH6AkOCykGenzzMgAAASdJREFUKM+10s0rrVEUBvDfe7pF3QzuSJJ8nLq5A/kjFDNlhP+Akk4ZmYrBKUMDGZgZyceEESUDxUAG6A44V+mIEUryEa/JUm9vR5ncXbu113qetdfaz9rKe8XD8l5xyn9aP7CKw/CTDJZm/DSDF/AW5zRj1YglsI4R9ONv7CpmcY1jNKMtGnnELhbRhQs0RP4B6lHBDo4K6EQxLviNaTxhEOOBD0TBRpTQEdwWtOIX2vEnirXjJyYKOcmesYQTnGIZ90HuxiYWsFFD7iTnl7GdL5BmbJqLnaIXQ+iL2F3YYfTgNsN//xzyeehYxVmAFdQFuRL4WnyIueC+Yh8rmAlZS3jAFS5rPS3J2CQXG8NkzOYf5jN5DdHQV3J9a43iBi/YQtN3kj4AFxBKjOA6Ir4AAAAASUVORK5CYII='}),
  "attributes": {
    "preview": {
      "type": "boolean",
      "default": false
    },
    "images": {
      "type": "array",
      "default": []
    },
    "blockId": {
      "type": "string",
      "default": ""
    },
    "error": {
      "type": "string",
      "default": ""
    },
    "spinner": {
      "type": "boolean",
      "default": false
    }
  },

  /**
   * @see ./edit.js
   */
  edit: Edit,

  /**
   * @see ./save.js
   */
  save: Save,
} );
