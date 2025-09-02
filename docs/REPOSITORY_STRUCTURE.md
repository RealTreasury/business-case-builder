# Repository Structure

Documentation reflects repository layout for version 2.1.10.

## Directory Tree

Output of `find . -maxdepth 2 -type d -not -path './.git*'`:

```text
.
./docs
./vendor
./templates
./tests
./tests/helpers
./inc
./public
./public/css
./public/js
./admin
./admin/partials
./admin/css
./admin/js
```

## AGENTS Files

The repository uses `AGENTS.md` files to outline coding rules for each directory.

- `AGENTS.md` – global WordPress standards and test commands.
- `admin/AGENTS.md` – dashboard code conventions.
- `docs/AGENTS.md` – Markdown style and link checks.
- `inc/AGENTS.md` – class structure and helper function requirements.
- `public/AGENTS.md` – front-end hooks, nonces, and translation rules.
- `templates/AGENTS.md` – template markup guidelines.
- `tests/AGENTS.md` – instructions for running the test suite.
- `vendor/AGENTS.md` – third-party dependencies, do not modify.

## Coding Guidelines

### Root

- Follow WordPress PHP coding standards.
- Use tabs for indentation; spaces only for alignment.
- Start each PHP file with `defined( 'ABSPATH' ) || exit;`.
- Prefix global functions with `rtbcb_`.
- Prefix class names with `RTBCB_`.
- Sanitize and escape all input and output using appropriate `esc_*` functions.
- Wrap user-visible strings in translation functions like `__( 'text', 'rtbcb' )`.
- Do not modify code in `vendor/`.
- After changing PHP files, run `find . -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 php -l` to check syntax.
- Run `bash tests/run-tests.sh` and ensure `phpcs --standard=WordPress` passes when available.

### admin/

- Contains WordPress dashboard functionality.
- Class files use `RTBCB_Admin` prefix.
- Files ending in `-page.php` render admin screens and must escape all output.
- Use WordPress nonce functions for form submissions.
- Wrap user-visible strings in `__()` or `_e()` with the `rtbcb` text domain.
- Verify capabilities such as `current_user_can( 'manage_options' )` before rendering pages.

### inc/

- Core PHP classes and helper functions.
- Start each file with `defined( 'ABSPATH' ) || exit;`.
- Each class file named `class-rtbcb-{feature}.php` declaring a single `RTBCB_{Feature}` class.
- Add PHPDoc blocks for classes, properties, and methods.
- Ensure helpers sanitize inputs and escape outputs with WordPress functions.
- Helper functions reside in `helpers.php` and must be prefixed with `rtbcb_`.

### public/

- Front-end hooks and assets for the plugin.
- Use `RTBCB_Public` prefix for classes.
- Escape all output before rendering.
- Wrap text in translation functions with the `rtbcb` text domain.
- Use WordPress nonces for front-end forms or AJAX endpoints.

### templates/

- PHP template files included by the plugin.
- Start each template with `defined( 'ABSPATH' ) || exit;`.
- Keep templates focused on markup; move heavy logic to classes.
- Escape variables with `esc_*` functions.
- Wrap translatable text in `__()` or `_e()` using the `rtbcb` text domain.

### tests/

- Automated tests and diagnostics.
- Run `bash tests/run-tests.sh` before committing changes.
- Ensure environment variables like `OPENAI_API_KEY` and `RTBCB_TEST_MODEL` are set.

### docs/

- Project documentation in Markdown.
- Use a single `#` title and keep lines under 100 characters.
- Lint docs with `npx markdownlint docs/**/*.md`.
- Check links with `npx markdown-link-check <file>`.

### vendor/

- Third-party libraries.
- Do not modify files in this directory.

