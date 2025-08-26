<?php
/**
 * Modern Utilities and Helper Functions
 * 
 * Clean utility functions following WordPress coding standards
 * 
 * @package RealTreasuryBusinessCaseBuilder
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sanitize and validate OpenAI API key
 * 
 * @param string $api_key API key to validate
 * @return string|false Sanitized API key or false if invalid
 */
function rtbcb_validate_api_key( $api_key ) {
    $api_key = sanitize_text_field( $api_key );
    
    if ( empty( $api_key ) ) {
        return false;
    }
    
    if ( ! preg_match( '/^sk-[a-zA-Z0-9]{48,}$/', $api_key ) ) {
        return false;
    }
    
    return $api_key;
}

/**
 * Format currency amount for display
 * 
 * @param float  $amount   Amount to format
 * @param string $currency Currency code (default: USD)
 * @return string Formatted currency string
 */
function rtbcb_format_currency( $amount, $currency = 'USD' ) {
    $amount = floatval( $amount );
    
    switch ( $currency ) {
        case 'USD':
            return '$' . number_format( $amount, 0 );
        case 'EUR':
            return '€' . number_format( $amount, 0 );
        case 'GBP':
            return '£' . number_format( $amount, 0 );
        default:
            return number_format( $amount, 0 ) . ' ' . esc_html( $currency );
    }
}

/**
 * Format percentage for display
 * 
 * @param float $percentage Percentage value
 * @param int   $decimals   Number of decimal places
 * @return string Formatted percentage string
 */
function rtbcb_format_percentage( $percentage, $decimals = 1 ) {
    return number_format( floatval( $percentage ), $decimals ) . '%';
}

/**
 * Generate secure nonce for AJAX requests
 * 
 * @param string $action Action name
 * @return string Nonce value
 */
function rtbcb_create_nonce( $action ) {
    return wp_create_nonce( 'rtbcb_' . sanitize_key( $action ) );
}

/**
 * Verify nonce for AJAX requests
 * 
 * @param string $nonce  Nonce value
 * @param string $action Action name
 * @return bool True if nonce is valid
 */
function rtbcb_verify_nonce( $nonce, $action ) {
    return wp_verify_nonce( $nonce, 'rtbcb_' . sanitize_key( $action ) );
}

/**
 * Log error message with proper formatting
 * 
 * @param string $message Error message
 * @param string $context Error context
 * @return void
 */
function rtbcb_log_error( $message, $context = 'general' ) {
    if ( ! WP_DEBUG_LOG ) {
        return;
    }
    
    $formatted_message = sprintf(
        '[RTBCB:%s] %s',
        esc_html( $context ),
        esc_html( $message )
    );
    
    error_log( $formatted_message );
}

/**
 * Get user-friendly error message
 * 
 * @param string $error_code Error code
 * @return string User-friendly error message
 */
function rtbcb_get_user_friendly_error( $error_code ) {
    $messages = array(
        'unauthorized' => __( 'Authentication failed. Please check your API key.', 'rtbcb' ),
        'rate_limit_exceeded' => __( 'Rate limit exceeded. Please try again later.', 'rtbcb' ),
        'server_error' => __( 'Service temporarily unavailable. Please try again later.', 'rtbcb' ),
        'invalid_input' => __( 'Invalid input provided. Please check your data.', 'rtbcb' ),
        'missing_data' => __( 'Required information is missing.', 'rtbcb' ),
        'security_check_failed' => __( 'Security verification failed.', 'rtbcb' ),
        'insufficient_permissions' => __( 'You do not have permission to perform this action.', 'rtbcb' ),
        'api_error' => __( 'An error occurred while communicating with the service.', 'rtbcb' ),
    );
    
    return isset( $messages[ $error_code ] ) ? $messages[ $error_code ] : __( 'An unexpected error occurred.', 'rtbcb' );
}

/**
 * Sanitize and validate ROI calculation inputs
 * 
 * @param array $inputs Raw input data
 * @return array|WP_Error Sanitized inputs or error
 */
function rtbcb_sanitize_calculation_inputs( $inputs ) {
    if ( ! is_array( $inputs ) ) {
        return new WP_Error( 'invalid_input', __( 'Input must be an array.', 'rtbcb' ) );
    }
    
    $required_fields = array(
        'treasury_staff_count',
        'treasury_staff_salary',
        'time_savings_percentage',
        'investment_cost'
    );
    
    $sanitized = array();
    
    foreach ( $required_fields as $field ) {
        if ( ! isset( $inputs[ $field ] ) ) {
            return new WP_Error( 'missing_field', sprintf( __( 'Required field missing: %s', 'rtbcb' ), $field ) );
        }
        
        $value = floatval( $inputs[ $field ] );
        
        if ( $value < 0 ) {
            return new WP_Error( 'invalid_value', sprintf( __( 'Field cannot be negative: %s', 'rtbcb' ), $field ) );
        }
        
        $sanitized[ $field ] = $value;
    }
    
    // Optional fields with defaults
    $optional_fields = array(
        'error_reduction_percentage' => 0,
        'compliance_cost_savings' => 0,
        'other_cost_savings' => 0,
        'implementation_cost' => 0,
        'training_cost' => 0,
        'maintenance_cost' => 0
    );
    
    foreach ( $optional_fields as $field => $default ) {
        $sanitized[ $field ] = isset( $inputs[ $field ] ) ? floatval( $inputs[ $field ] ) : $default;
    }
    
    return $sanitized;
}

/**
 * Get the appropriate capability for WordPress.com compatibility.
 * 
 * WordPress.com has different capability requirements than self-hosted WordPress.
 * This function ensures the admin functions work across all WordPress environments.
 *
 * @return string The capability required for admin access
 */
function rtbcb_get_admin_capability() {
    // Check if we're on WordPress.com
    if ( rtbcb_is_wordpress_com() ) {
        // WordPress.com uses different capabilities
        // Check for editor capability first, then fall back to read
        if ( current_user_can( 'edit_pages' ) ) {
            return 'edit_pages';
        } elseif ( current_user_can( 'edit_posts' ) ) {
            return 'edit_posts';
        } else {
            return 'read';
        }
    }
    
    // For regular WordPress installations, use manage_options if available
    if ( current_user_can( 'manage_options' ) ) {
        return 'manage_options';
    }
    
    // Fallback capability chain for other managed WordPress environments
    $fallback_caps = [ 'edit_pages', 'edit_posts', 'upload_files', 'read' ];
    
    foreach ( $fallback_caps as $cap ) {
        if ( current_user_can( $cap ) ) {
            return $cap;
        }
    }
    
    // Final fallback
    return 'read';
}

/**
 * Detect if we're running on WordPress.com
 *
 * @return bool True if running on WordPress.com
 */
function rtbcb_is_wordpress_com() {
    // Check for WordPress.com specific constants and functions
    if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
        return true;
    }
    
    // Check for WordPress.com VIP
    if ( defined( 'WPCOM_VIP' ) && WPCOM_VIP ) {
        return true;
    }
    
    // Check for WordPress.com specific functions
    if ( function_exists( 'wpcom_vip_file_get_contents' ) ) {
        return true;
    }
    
    // Check server environment indicators
    $server_name = sanitize_text_field( $_SERVER['SERVER_NAME'] ?? '' );
    if ( strpos( $server_name, '.wordpress.com' ) !== false ) {
        return true;
    }
    
    // Check for Automattic environment
    if ( defined( 'AUTOMATTIC_DOMAIN' ) ) {
        return true;
    }
    
    return false;
}

/**
 * Check if current user can manage plugin settings with WordPress.com compatibility
 * 
 * @return bool True if user has sufficient permissions
 */
function rtbcb_user_can_manage_settings() {
    // Settings require manage_options if available, otherwise use admin capability
    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }
    
    // For WordPress.com, allow users with admin capability to manage settings
    $admin_capability = rtbcb_get_admin_capability();
    return current_user_can( $admin_capability );
}

/**
 * Check if current user can access admin functions with WordPress.com compatibility
 * 
 * @return bool True if user has sufficient permissions
 */
function rtbcb_user_can_admin() {
    $capability = rtbcb_get_admin_capability();
    return current_user_can( $capability );
}

/**
 * Check if current user can view reports
 * 
 * @return bool True if user has sufficient permissions
 */
function rtbcb_user_can_view_reports() {
    return current_user_can( 'edit_posts' );
}

/**
 * Get plugin version
 * 
 * @return string Plugin version
 */
function rtbcb_get_plugin_version() {
    return defined( 'RTBCB_VERSION' ) ? RTBCB_VERSION : '2.1.0';
}

/**
 * Get plugin URL
 * 
 * @param string $path Optional path to append
 * @return string Plugin URL
 */
function rtbcb_get_plugin_url( $path = '' ) {
    $url = defined( 'RTBCB_URL' ) ? RTBCB_URL : plugin_dir_url( __DIR__ );
    
    if ( ! empty( $path ) ) {
        $url = rtrim( $url, '/' ) . '/' . ltrim( $path, '/' );
    }
    
    return $url;
}

/**
 * Get plugin directory path
 * 
 * @param string $path Optional path to append
 * @return string Plugin directory path
 */
function rtbcb_get_plugin_dir( $path = '' ) {
    $dir = defined( 'RTBCB_DIR' ) ? RTBCB_DIR : plugin_dir_path( __DIR__ );
    
    if ( ! empty( $path ) ) {
        $dir = rtrim( $dir, '/' ) . '/' . ltrim( $path, '/' );
    }
    
    return $dir;
}