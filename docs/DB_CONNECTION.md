# Database Connection Settings

Persistent MySQL connections reduce connection overhead and improve
performance for heavy queries.

## Enabling Persistent Connections

- Edit your `wp-config.php` and prefix `DB_HOST` with `p:`:

    ```php
    define( 'DB_HOST', 'p:localhost' );
    ```

- Alternatively, install a pooling plugin such as
  [HyperDB](https://github.com/Automattic/hyperdb) which manages connections
  across multiple servers.

## Pooling Expectations

- The plugin reuses the existing persistent connection when available.
- Connection pools should allow enough workers to handle concurrent requests.
- Monitor idle connections and adjust MySQL's `wait_timeout` if connections
  drop unexpectedly.

For setup steps see the project [README](../README.md#step-3-enable-persistent-database-connections).
