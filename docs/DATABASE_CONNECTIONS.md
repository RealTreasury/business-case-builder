# DATABASE_CONNECTIONS

Persistent MySQL connections reduce handshake overhead and keep this plugin responsive during heavy reporting.

- Prefix the database host with `p:` in `wp-config.php` to enable a persistent connection:

    ```php
    define( 'DB_HOST', 'p:localhost' );
    ```

- Alternatively, install a pooling plugin such as [HyperDB](https://github.com/Automattic/hyperdb) to manage shared connections.

After configuring persistence, verify the connection by checking that `$wpdb->dbhost` begins with `p:` inside a debugging session.
