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

        return $sanitized;
    }
}

