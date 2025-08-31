# Timeout Configuration

The plugin's API request timeout defaults to 300 seconds and can be adjusted up to 600 seconds from the settings page.

To prevent gateway timeout errors when generating large reports, adjust server and PHP timeouts:

## nginx

```
location ~ \.php$ {
    proxy_read_timeout 600;
    fastcgi_read_timeout 600;
}
```

Reload nginx after editing the configuration:

```
sudo nginx -s reload
```

## PHP-FPM

Increase the request termination limit to match nginx:

```
request_terminate_timeout = 600
```

Restart PHP-FPM to apply the change.
