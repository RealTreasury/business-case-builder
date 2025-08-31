# Database Connections

Persistent connections reduce overhead for frequent form submissions and
report generation requests.

- Prefix the MySQL host with `p:` in `wp-config.php` to enable built-in
    persistent connections:

```php
define( 'DB_HOST', 'p:localhost' );
```

- For large deployments, use a pooling plugin such as
    [HyperDB](https://wordpress.org/plugins/hyperdb/)
    to share connections across requests.
- Confirm that database servers allow persistent connections and that pool sizes
    meet expected concurrent traffic.

These settings help the plugin avoid repeated connection setup during heavy lead
processing.
