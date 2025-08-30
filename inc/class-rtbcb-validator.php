<?php
defined( 'ABSPATH' ) || exit;

/**
 * Data validation utilities.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Class RTBCB_Validator.
 */
class RTBCB_Validator {
    /**
     * Validate and sanitize form data.
     *
     * @param array $data Raw form data.
     * @return array Sanitized data or array with 'error' key.
     */
    public function validate( array $data ): array {
        $sanitized = rtbcb_sanitize_form_data( wp_unslash( $data ) );

        if ( isset( $data['company_name'] ) ) {
            $sanitized['company_name'] = sanitize_text_field( wp_unslash( $data['company_name'] ) );
        }

        $required_fields = [
            'company_name' => __( 'Company name is required.', 'rtbcb' ),
            'email'        => __( 'Email is required.', 'rtbcb' ),
            'company_size' => __( 'Company size is required.', 'rtbcb' ),
        ];

        foreach ( $required_fields as $field => $message ) {
            if ( empty( $sanitized[ $field ] ) ) {
                return [
                    'error' => $message,
                ];
            }
        }

        if ( ! rtbcb_is_business_email( $sanitized['email'] ) ) {
            return [
                'error' => __( 'Please use your business email address.', 'rtbcb' ),
            ];
        }

        $length_limits = [
            'company_name' => 255,
            'email'        => 254,
        ];

        foreach ( $length_limits as $field => $limit ) {
            if ( isset( $sanitized[ $field ] ) && strlen( $sanitized[ $field ] ) > $limit ) {
                return [
                    'error' => sprintf(
                        __( '%s cannot exceed %d characters.', 'rtbcb' ),
                        ucwords( str_replace( '_', ' ', $field ) ),
                        $limit
                    ),
                ];
            }
        }

        return $sanitized;
    }
}
