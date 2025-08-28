# Repository Structure

## Directory Tree

Output of `find . -maxdepth 2 -type d -not -path './.git*'`:

```
.
./admin
./admin/css
./admin/js
./admin/partials
./inc
./public
./public/css
./public/js
./templates
./tests
./tests/helpers
./vendor
```

## Coding Guidelines

### Root

- Follow WordPress PHP coding standards.
- Use four spaces for indentation.
- Prefix global functions with `rtbcb_`.
- Prefix class names with `RTBCB_`.
- Sanitize and escape all input and output using appropriate `esc_*` functions.
- Wrap user-visible strings in translation functions like `__( 'text', 'rtbcb' )`.
- Do not modify code in `vendor/`.
- After changing PHP files, run `find . -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 php -l` to check syntax.

### admin/

- Contains WordPress dashboard functionality.
- Class files use `RTBCB_Admin` prefix.
- Files ending in `-page.php` render admin screens and must escape all output.
- Use WordPress nonce functions for form submissions.

### inc/

- Core PHP classes and helper functions.
- Each class file named `class-rtbcb-{feature}.php` declaring a single `RTBCB_{Feature}` class.
- Include PHPDoc blocks for classes, properties, and methods.
- Helper functions reside in `helpers.php` and must be prefixed with `rtbcb_`.

### public/

- Front-end hooks and assets for the plugin.
- Use `RTBCB_Public` prefix for classes.
- Escape all output before rendering.

### templates/

- PHP template files included by the plugin.
- Keep templates focused on markup; move heavy logic to classes.
- Escape variables with `esc_*` functions.
- Wrap translatable text in `__()` or `_e()` using the `rtbcb` text domain.

### vendor/

- Third-party libraries.
- Do not modify files in this directory.

