# ASSET PAGE SLUGS

The plugin only enqueues its public assets on specific page slugs or
when the `[rt_business_case_builder]` shortcode is present.

- Default slugs:
    - `business-case`
    - `business-case-report`
    - `business-case-builder`
    - `rtbcb`
- Additional slugs may be registered with the `rtbcb_asset_page_slugs` filter.

```php
add_filter(
    'rtbcb_asset_page_slugs',
    fn( $slugs ) => array_merge( $slugs, [ 'rtbcb' ] )
);
```

This ensures that `rtbcb-wizard.js` and `rtbcb-wizard-component.js` load
on the custom page.
Ensure the page also enqueues the `wp-element` script so `wp.element.render` is available
before `rtbcb-wizard-component.js` runs:

```php
wp_enqueue_script( 'wp-element' );
```

If using a headless or non-WordPress frontend, expose compatible `React` and `ReactDOM`
APIs as `window.wp.element` or adjust the component to work without them.
