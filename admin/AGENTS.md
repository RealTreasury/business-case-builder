# AI Instructions for admin/

- Contains code for WordPress dashboard functionality.
- Class files should use the `RTBCB_Admin` prefix.
- Files ending in `-page.php` should render admin screens; escape all output.
- Use WordPress nonce functions for form submissions.
- Wrap user-visible strings in `__()` or `_e()` calls using the `rtbcb` text domain.
- Verify user capabilities (e.g., `current_user_can('manage_options')`) before rendering admin pages.
