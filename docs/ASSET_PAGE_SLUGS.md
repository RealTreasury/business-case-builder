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
