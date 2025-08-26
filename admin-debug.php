<?php
/**
 * Real Treasury Business Case Builder - Admin Debug Script
 * 
 * This script helps diagnose admin menu visibility issues.
 * Upload this file to your WordPress root directory and access it via:
 * https://yourdomain.com/admin-debug.php
 * 
 * @package RealTreasuryBusinessCaseBuilder
 */

// Basic environment check
echo "<h1>Real Treasury Business Case Builder - Admin Debug</h1>\n";
echo "<h2>Environment Information</h2>\n";
echo "<ul>\n";
echo "<li>PHP Version: " . PHP_VERSION . "</li>\n";
echo "<li>WordPress Environment: " . (defined('ABSPATH') ? 'Yes' : 'No') . "</li>\n";
echo "<li>Plugin Directory: " . (defined('RTBCB_PLUGIN_DIR') ? RTBCB_PLUGIN_DIR : 'Not defined') . "</li>\n";
echo "<li>Current User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not available') . "</li>\n";
echo "<li>Server Name: " . ($_SERVER['SERVER_NAME'] ?? 'Not available') . "</li>\n";
echo "</ul>\n";

// Load WordPress if not already loaded
if (!defined('ABSPATH')) {
    echo "<h2>Loading WordPress...</h2>\n";
    
    // Try to find WordPress
    $wp_paths = [
        __DIR__ . '/wp-config.php',
        dirname(__DIR__) . '/wp-config.php',
        __DIR__ . '/../../wp-config.php',
    ];
    
    $wp_found = false;
    foreach ($wp_paths as $path) {
        if (file_exists($path)) {
            echo "<p>Found WordPress at: $path</p>\n";
            require_once $path;
            $wp_found = true;
            break;
        }
    }
    
    if (!$wp_found) {
        echo "<p style='color: red;'>Could not find WordPress installation. Please run this script from your WordPress root directory.</p>\n";
        exit;
    }
}

echo "<h2>Plugin Status</h2>\n";

// Check if plugin is activated
$active_plugins = get_option('active_plugins', []);
$plugin_file = 'real-treasury-business-case-builder/real-treasury-business-case-builder.php';
$is_active = in_array($plugin_file, $active_plugins);

echo "<ul>\n";
echo "<li>Plugin Active: " . ($is_active ? 'Yes' : 'No') . "</li>\n";

if ($is_active) {
    echo "<li>Plugin Class Available: " . (class_exists('RTBCB_Business_Case_Builder') ? 'Yes' : 'No') . "</li>\n";
    echo "<li>Admin Class Available: " . (class_exists('RTBCB_Admin') ? 'Yes' : 'No') . "</li>\n";
    
    // Check plugin instance
    if (class_exists('RTBCB_Business_Case_Builder')) {
        $plugin = RTBCB_Business_Case_Builder::get_instance();
        echo "<li>Plugin Instance Created: " . (is_object($plugin) ? 'Yes' : 'No') . "</li>\n";
        
        // Check services
        if (method_exists($plugin, 'get_service')) {
            $admin_service = $plugin->get_service('admin');
            echo "<li>Admin Service Registered: " . (is_object($admin_service) ? 'Yes (' . get_class($admin_service) . ')' : 'No') . "</li>\n";
        }
    }
}
echo "</ul>\n";

echo "<h2>Current User Information</h2>\n";
if (function_exists('wp_get_current_user')) {
    $current_user = wp_get_current_user();
    echo "<ul>\n";
    echo "<li>User ID: " . $current_user->ID . "</li>\n";
    echo "<li>User Login: " . $current_user->user_login . "</li>\n";
    echo "<li>User Roles: " . implode(', ', $current_user->roles) . "</li>\n";
    echo "<li>Can 'manage_options': " . (current_user_can('manage_options') ? 'Yes' : 'No') . "</li>\n";
    echo "<li>Can 'edit_pages': " . (current_user_can('edit_pages') ? 'Yes' : 'No') . "</li>\n";
    echo "<li>Can 'edit_posts': " . (current_user_can('edit_posts') ? 'Yes' : 'No') . "</li>\n";
    echo "</ul>\n";
} else {
    echo "<p>WordPress user functions not available.</p>\n";
}

echo "<h2>WordPress.com Environment Detection</h2>\n";
echo "<ul>\n";
echo "<li>IS_WPCOM constant: " . (defined('IS_WPCOM') && IS_WPCOM ? 'Yes' : 'No') . "</li>\n";
echo "<li>WPCOMSH_VERSION constant: " . (defined('WPCOMSH_VERSION') ? 'Yes (' . WPCOMSH_VERSION . ')' : 'No') . "</li>\n";
echo "<li>Server contains .wordpress.com: " . (strpos($_SERVER['SERVER_NAME'] ?? '', '.wordpress.com') !== false ? 'Yes' : 'No') . "</li>\n";
echo "<li>AUTOMATTIC_DOMAIN constant: " . (defined('AUTOMATTIC_DOMAIN') ? 'Yes' : 'No') . "</li>\n";
echo "</ul>\n";

echo "<h2>Admin Menu Check</h2>\n";
if (function_exists('get_option') && is_admin()) {
    global $admin_page_hooks, $menu, $submenu;
    
    // Force admin context
    define('WP_ADMIN', true);
    
    // Trigger admin_menu hook to register menus
    do_action('admin_menu');
    
    echo "<h3>Registered Admin Page Hooks</h3>\n";
    echo "<pre>\n";
    print_r($admin_page_hooks ?? []);
    echo "</pre>\n";
    
    echo "<h3>Main Menu Items</h3>\n";
    echo "<pre>\n";
    print_r($menu ?? []);
    echo "</pre>\n";
    
    // Check specifically for our menu
    $rtbcb_found = false;
    if (isset($admin_page_hooks['rtbcb-dashboard'])) {
        echo "<p style='color: green;'><strong>✓ Real Treasury menu found in admin_page_hooks!</strong></p>\n";
        $rtbcb_found = true;
    }
    
    if (isset($menu) && is_array($menu)) {
        foreach ($menu as $menu_item) {
            if (isset($menu_item[2]) && $menu_item[2] === 'rtbcb-dashboard') {
                echo "<p style='color: green;'><strong>✓ Real Treasury menu found in main menu!</strong></p>\n";
                echo "<p>Menu details: " . print_r($menu_item, true) . "</p>\n";
                $rtbcb_found = true;
                break;
            }
        }
    }
    
    if (!$rtbcb_found) {
        echo "<p style='color: red;'><strong>✗ Real Treasury menu NOT found in WordPress admin menus.</strong></p>\n";
        
        // Additional debugging
        echo "<h3>Capability Debugging</h3>\n";
        if (class_exists('RTBCB_Admin')) {
            $admin = new RTBCB_Admin();
            
            // Use reflection to access private methods for debugging
            $reflection = new ReflectionClass($admin);
            
            try {
                $capability_method = $reflection->getMethod('get_admin_capability');
                $capability_method->setAccessible(true);
                $capability = $capability_method->invoke($admin);
                echo "<p>Detected capability requirement: <strong>$capability</strong></p>\n";
                echo "<p>Current user has this capability: " . (current_user_can($capability) ? 'Yes' : 'No') . "</p>\n";
                
                $wpcom_method = $reflection->getMethod('is_wordpress_com');
                $wpcom_method->setAccessible(true);
                $is_wpcom = $wpcom_method->invoke($admin);
                echo "<p>WordPress.com environment detected: " . ($is_wpcom ? 'Yes' : 'No') . "</p>\n";
                
            } catch (Exception $e) {
                echo "<p style='color: orange;'>Could not access admin methods for debugging: " . $e->getMessage() . "</p>\n";
            }
        }
    }
} else {
    echo "<p>Cannot check admin menu - not in admin context or WordPress functions not available.</p>\n";
}

echo "<h2>Recommendations</h2>\n";
if (!$is_active) {
    echo "<p style='color: red;'>❌ <strong>The plugin is not activated.</strong> Please activate the Real Treasury Business Case Builder plugin from the WordPress admin.</p>\n";
} elseif (!function_exists('current_user_can') || !current_user_can('edit_pages')) {
    echo "<p style='color: orange;'>⚠️ <strong>User permissions issue.</strong> You may not have sufficient permissions to see the admin menu. Contact your site administrator.</p>\n";
} elseif (!$rtbcb_found) {
    echo "<p style='color: red;'>❌ <strong>Admin menu not registered.</strong> There may be a plugin conflict or error preventing the menu from appearing.</p>\n";
    echo "<ul>\n";
    echo "<li>Check for PHP errors in your WordPress error log</li>\n";
    echo "<li>Try deactivating other plugins temporarily</li>\n";
    echo "<li>Ensure you have the latest version of the plugin</li>\n";
    echo "<li>Contact support with this debug information</li>\n";
    echo "</ul>\n";
} else {
    echo "<p style='color: green;'>✅ <strong>Everything looks good!</strong> The Real Treasury admin menu should be visible.</p>\n";
}

echo "<p><em>Debug completed at " . date('Y-m-d H:i:s') . "</em></p>\n";
?>