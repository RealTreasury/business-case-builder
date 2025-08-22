<?php
/**
 * Helper functions for the Real Treasury Business Case Builder plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sanitize form input data
 *
 * @param array $data Raw form data
 * @return array Sanitized data
 */
function rtbcb_sanitize_form_data( $data ) {
    $sanitized = [];
    
    // Email
    if ( isset( $data['email'] ) ) {
        $sanitized['email'] = sanitize_email( $data['email'] );
    }
    
    // Text fields
    $text_fields = [ 'company_size', 'industry' ];
    foreach ( $text_fields as $field ) {
        if ( isset( $data[ $field ] ) ) {
            $sanitized[ $field ] = sanitize_text_field( $data[ $field ] );
        }
    }
    
    // Numeric fields
    $numeric_fields = [
        'hours_reconciliation'   => [ 'min' => 0,   'max' => 168 ],
        'hours_cash_positioning' => [ 'min' => 0,   'max' => 168 ],
        'num_banks'              => [ 'min' => 1,   'max' => 50 ],
        'ftes'                   => [ 'min' => 0.5, 'max' => 100 ],
    ];
    
    foreach ( $numeric_fields as $field => $limits ) {
        if ( isset( $data[ $field ] ) ) {
            $value = floatval( $data[ $field ] );
            $value = max( $limits['min'], min( $limits['max'], $value ) );
            $sanitized[ $field ] = $value;
        }
    }
    
    // Pain points array
    if ( isset( $data['pain_points'] ) && is_array( $data['pain_points'] ) ) {
        $valid_pain_points = [
            'manual_processes',
            'poor_visibility',
            'forecast_accuracy',
            'compliance_risk',
            'bank_fees',
            'integration_issues',
        ];
        
        $sanitized['pain_points'] = array_filter(
            array_map( 'sanitize_text_field', $data['pain_points'] ),
            function ( $point ) use ( $valid_pain_points ) {
                return in_array( $point, $valid_pain_points, true );
            }
        );
    }
    
    return $sanitized;
}

/**
 * Validate email domain
 *
 * @param string $email Email address
 * @return bool True if valid business email
 */
function rtbcb_is_business_email( $email ) {
    $consumer_domains = [
        'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com',
        'aol.com', 'icloud.com', 'mail.com', 'protonmail.com',
    ];
    
    $domain = substr( strrchr( $email, '@' ), 1 );
    return ! in_array( strtolower( $domain ), $consumer_domains, true );
}

/**
 * Get client information for analytics
 *
 * @return array Client data
 */
function rtbcb_get_client_info() {
    return [
        'ip'          => rtbcb_get_client_ip(),
        'user_agent'  => isset( $_SERVER['HTTP_USER_AGENT'] ) ?
            sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
        'referrer'    => isset( $_SERVER['HTTP_REFERER'] ) ?
            esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
        'utm_source'  => isset( $_GET['utm_source'] ) ?
            sanitize_text_field( wp_unslash( $_GET['utm_source'] ) ) : '',
        'utm_medium'  => isset( $_GET['utm_medium'] ) ?
            sanitize_text_field( wp_unslash( $_GET['utm_medium'] ) ) : '',
        'utm_campaign'=> isset( $_GET['utm_campaign'] ) ?
            sanitize_text_field( wp_unslash( $_GET['utm_campaign'] ) ) : '',
    ];
}

/**
 * Get client IP address
 *
 * @return string IP address
 */
function rtbcb_get_client_ip() {
    $ip_keys = [ 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ];
    
    foreach ( $ip_keys as $key ) {
        if ( ! empty( $_SERVER[ $key ] ) ) {
            $ip = wp_unslash( $_SERVER[ $key ] );
            
            // Handle comma-separated IPs
            if ( strpos( $ip, ',' ) !== false ) {
                $ip = trim( explode( ',', $ip )[0] );
            }
            
            // Validate IP
            if ( filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) ) {
                return $ip;
            }
        }
    }
    
    return isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
}

