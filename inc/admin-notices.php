<?php
/**
 * Admin notices for OpenAI API status
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display OpenAI API health notices
 */
function rtbcb_show_openai_api_notices() {
    // Only show on plugin pages
    $screen = get_current_screen();
    if ( ! $screen || strpos( $screen->id, 'rtbcb' ) === false ) {
        return;
    }

    $now         = time();
    $last_ok     = get_option( 'rtbcb_openai_last_ok', 0 );
    $last_error_at = get_option( 'rtbcb_openai_last_error_at', 0 );
    $error_data  = get_transient( 'rtbcb_openai_error' );

    // Determine notice type based on timing
    $show_red    = ( $now - $last_ok ) > 600 && ( $now - $last_error_at ) < 600; // Last OK > 10m ago AND recent error
    $show_yellow = ! $show_red && ( $now - $last_error_at ) < 600 && ! empty( $error_data ); // Recent rate limit/timeout
    $show_green  = ( $now - $last_ok ) < 180; // Last OK within 3 minutes

    if ( $show_red && is_array( $error_data ) ) {
        $message     = rtbcb_get_error_message( $error_data );
        $remediation = rtbcb_get_remediation_tip( $error_data );

        echo '<div class="notice notice-error is-dismissible rtbcb-api-notice" data-notice-type="error">';
        echo '<p><strong>' . esc_html__( 'OpenAI API Connection Failed', 'rtbcb' ) . '</strong></p>';
        echo '<p>' . esc_html( $message ) . '</p>';
        if ( $remediation ) {
            echo '<p><em>' . wp_kses_post( $remediation ) . '</em></p>';
        }

        if ( WP_DEBUG && ! empty( $error_data['body'] ) ) {
            echo '<details><summary>Debug Info</summary>';
            echo '<pre style="background: #f1f1f1; padding: 8px; font-size: 11px;">';
            echo esc_html( print_r( $error_data, true ) );
            echo '</pre></details>';
        }
        echo '</div>';
    } elseif ( $show_yellow && is_array( $error_data ) ) {
        echo '<div class="notice notice-warning is-dismissible rtbcb-api-notice" data-notice-type="warning">';
        echo '<p><strong>' . esc_html__( 'OpenAI API Rate Limited', 'rtbcb' ) . '</strong></p>';
        echo '<p>' . esc_html__( 'API requests are being rate limited. Tests may run slower.', 'rtbcb' ) . '</p>';
        echo '</div>';
    } elseif ( $show_green ) {
        echo '<div class="notice notice-success is-dismissible rtbcb-api-notice" data-notice-type="success">';
        echo '<p><span class="dashicons dashicons-yes-alt" style="color: green;"></span> ';
        echo esc_html__( 'OpenAI API connection healthy', 'rtbcb' ) . '</p>';
        echo '</div>';
    }
}
add_action( 'admin_notices', 'rtbcb_show_openai_api_notices' );

/**
 * Get user-friendly error message
 */
function rtbcb_get_error_message( $error_data ) {
    $messages = [
        401 => __( 'API key is invalid or has been revoked.', 'rtbcb' ),
        403 => __( 'API key does not have required permissions.', 'rtbcb' ),
        429 => __( 'Too many requests. API rate limit exceeded.', 'rtbcb' ),
        500 => __( 'OpenAI service is experiencing issues.', 'rtbcb' ),
        502 => __( 'OpenAI service is temporarily unavailable.', 'rtbcb' ),
        503 => __( 'OpenAI service is down for maintenance.', 'rtbcb' ),
    ];

    $status = $error_data['httpStatus'] ?? 0;
    return $messages[ $status ] ?? sprintf( __( 'API error (HTTP %d)', 'rtbcb' ), $status );
}

/**
 * Get remediation tip for error
 */
function rtbcb_get_remediation_tip( $error_data ) {
    $status       = $error_data['httpStatus'] ?? 0;
    $settings_url = admin_url( 'admin.php?page=rtbcb-unified-tests#settings' );

    switch ( $status ) {
        case 401:
        case 403:
            return sprintf(
                __( 'Please check your API key in <a href="%s">plugin settings</a>.', 'rtbcb' ),
                $settings_url
            );

        case 429:
            return __( 'Wait a few minutes before running more tests. Consider upgrading your OpenAI plan for higher limits.', 'rtbcb' );

        case 500:
        case 502:
        case 503:
            return __( 'This is a temporary OpenAI service issue. Try again in a few minutes.', 'rtbcb' );

        default:
            return null;
    }
}

/**
 * AJAX handler to dismiss API notices
 */
function rtbcb_dismiss_api_notice() {
    if ( ! check_ajax_referer( 'rtbcb_admin_nonce', 'nonce', false ) ) {
        wp_die();
    }

    $notice_type = sanitize_text_field( wp_unslash( $_POST['notice_type'] ?? '' ) );

    switch ( $notice_type ) {
        case 'error':
            delete_transient( 'rtbcb_openai_error' );
            update_option( 'rtbcb_openai_last_error_dismissed', time() );
            break;
        case 'warning':
        case 'success':
            update_option( "rtbcb_api_notice_{$notice_type}_dismissed", time() );
            break;
    }

    wp_die();
}
add_action( 'wp_ajax_rtbcb_dismiss_api_notice', 'rtbcb_dismiss_api_notice' );
