# AI Coding Guidelines for Real Treasury Business Case Builder

- Follow [WordPress PHP coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).
- Use tabs for indentation; spaces only for alignment per WordPress PHP standards.
- Prefix global functions with `rtbcb_`.
- Prefix class names with `RTBCB_`.
- Start each PHP file with `defined( 'ABSPATH' ) || exit;`.
- Sanitize and escape all input and output with the appropriate `esc_*` function.
- Wrap user visible strings in translation functions like `__( 'text', 'rtbcb' )`.
- Do not modify code in the `vendor/` directory; it contains third-party dependencies.
- After making changes to PHP files, run `find . -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 php -l` to check for syntax errors.
- Run `bash tests/run-tests.sh` to execute PHP and JS tests as well as optional `phpcs` checks.
- Ensure `phpcs --standard=WordPress` passes when available.
