# Temporary Bypass Mode

The plugin can temporarily bypass AI enrichment, RAG searches and intelligent recommendations.

## Enabling

- Check the **Disable Heavy Features** option in the plugin settings.
- Or define the `RTBCB_FAST_MODE` constant in `wp-config.php`:

```php
define( 'RTBCB_FAST_MODE', true );
```

## Effects

- Skips calls to OpenAI and the RAG index.
- Uses basic category recommendations and fallback analysis.
- Displays an admin notice while active.

## Disabling

- Uncheck the option or remove the constant.
