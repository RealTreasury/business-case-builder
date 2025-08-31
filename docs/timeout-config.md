# Timeout Configuration

The plugin defaults to a 300-second API request timeout and allows
configuration up to 600 seconds via the settings page. To prevent gateway
timeout errors when generating large reports, adjust server and PHP
timeouts:

## nginx

```nginx
location ~ \.php$ {
    proxy_read_timeout 600;
    fastcgi_read_timeout 600;
}
```

Reload nginx after editing the configuration:

```bash
sudo nginx -s reload
```

## PHP-FPM

Increase the request termination limit to match nginx:

```ini
request_terminate_timeout = 600
```

Restart PHP-FPM to apply the change.
