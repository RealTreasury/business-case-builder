# AI Instructions for templates/

- PHP template files included by the plugin live here.
- Start each template file with `defined( 'ABSPATH' ) || exit;`.
- Keep templates focused on markup; move heavy logic to classes.
- Escape variables with appropriate `esc_*` functions.
- Wrap translatable text in `__()` or `_e()` using the `rtbcb` text domain.
