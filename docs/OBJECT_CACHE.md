# Persistent Object Cache Setup

The plugin caches OpenAI model metadata using WordPress's object cache.
To keep responses available across requests, configure a persistent cache
backend such as Redis or Memcached.

- Install and configure a persistent cache plugin. Examples:
    - [Redis Object Cache](https://wordpress.org/plugins/redis-cache/)
    - [Memcached](https://wordpress.org/plugins/memcached/)
- Verify the setup with:

    ```bash
    wp cache type
    ```

- When the OpenAI API key changes, the plugin automatically clears the
  `rtbcb_openai_models` cache key.
- Manually flush the cache if needed:

    ```bash
    wp cache delete rtbcb_openai_models
    # or flush everything
    wp cache flush
    ```

For API timeout configuration, see [timeout-config.md](timeout-config.md).
