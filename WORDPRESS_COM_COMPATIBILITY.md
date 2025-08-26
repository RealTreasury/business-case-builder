# WordPress.com Compatibility Guide

## Issue Resolution: Admin Menu Not Displaying on WordPress.com

### Problem
The Real Treasury Business Case Builder plugin's admin menu was not displaying on WordPress.com hosted sites, even though it worked perfectly on self-hosted WordPress installations.

### Root Cause
WordPress.com has different user capability requirements compared to self-hosted WordPress. The plugin was using hardcoded `manage_options` capability, which is often not available to site administrators on WordPress.com.

### Solution
Implemented a dynamic capability system that adapts to the hosting environment:

#### WordPress.com Environment
- **Primary capability**: `edit_pages`
- **Fallback capabilities**: `edit_posts` → `read`
- **Detection method**: Checks for WordPress.com-specific constants and server indicators

#### Self-hosted WordPress
- **Primary capability**: `manage_options` (maintains existing security)
- **Fallback capabilities**: `edit_pages` → `edit_posts` → `read`

### Implementation Details

#### Key Files Modified:
1. `admin/classes/Admin.php` - Updated menu registration and capability checks
2. `inc/utils/helpers.php` - Added environment detection and capability functions
3. `real-treasury-business-case-builder.php` - Added testing support

#### New Functions Added:
- `rtbcb_is_wordpress_com()` - Detects WordPress.com hosting environment
- `rtbcb_get_admin_capability()` - Returns appropriate capability for current environment
- `rtbcb_user_can_admin()` - Checks if user has admin access with environment compatibility

### Testing
- Created comprehensive test suite (`tests/wordpress-com-compatibility.test.php`)
- Updated integration tests to match new permission model
- All existing functionality preserved for self-hosted WordPress

### Benefits
1. **WordPress.com Compatibility**: Admin menu now displays properly on WordPress.com
2. **Security Maintained**: Still uses `manage_options` on self-hosted sites where available
3. **Graceful Fallbacks**: Works across different WordPress hosting environments
4. **Backward Compatible**: No breaking changes for existing installations

### Usage
No action required from users. The plugin automatically detects the hosting environment and adjusts capability requirements accordingly.

### Debug Information
When `WP_DEBUG` is enabled, the plugin logs which capability is being used for admin menu registration, helping with troubleshooting.

---

**Resolution Date**: August 26, 2025  
**Issue Reference**: #598  
**WordPress.com Status**: ✅ Compatible