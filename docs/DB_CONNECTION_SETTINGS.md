# DB CONNECTION SETTINGS

Enable persistent MySQL connections to reduce overhead.

## Persistent Connections

- Edit `wp-config.php` and prefix the host with `p:`:

```php
define( 'DB_HOST', 'p:localhost' );
```

- Confirm the MySQL server allows persistent connections.

## Connection Pooling

- Use a pooling plugin such as HyperDB.
- External proxies like ProxySQL can also manage pooling.
- The plugin assumes connections remain open between requests in production.
