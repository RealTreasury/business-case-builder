# AI Instructions for admin/

- Contains modern WordPress dashboard functionality with fresh architecture
- Class files should use the `RTBCB_Admin` prefix and follow PSR-4 autoloading
- Files ending in `-page.php` should render admin screens; escape all output with esc_*() functions
- Use WordPress nonce functions for all form submissions and AJAX requests
- Implement modern responsive design with mobile-first approach
- Use AJAX-powered interactions with proper error handling and loading states
- Follow WordPress coding standards and security best practices
- Ensure capability checks with current_user_can() for all admin operations