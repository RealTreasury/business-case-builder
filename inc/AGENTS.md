# AI Instructions for inc/

- Core PHP classes and helper functions live here.
- All PHP files must start with `defined( 'ABSPATH' ) || exit;` to prevent direct access.
- Each class file should be named `class-rtbcb-{feature}.php` and declare a single `RTBCB_{Feature}` class.
- Add PHPDoc blocks for classes, properties, and methods.
- Ensure helper functions and classes sanitize all inputs and escape all outputs with appropriate WordPress functions.
- Helper functions belong in `helpers.php` and must be prefixed with `rtbcb_`.
