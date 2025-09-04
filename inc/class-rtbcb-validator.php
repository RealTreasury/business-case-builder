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
 * @param array  $data        Raw form data.
 * @param string $report_type Report type (basic or enhanced).
 * @return array Sanitized data or array with 'error' key.
 */
public function validate( array $data, string $report_type = 'basic' ): array {
$sanitized   = rtbcb_sanitize_form_data( wp_unslash( $data ) );
$report_type = sanitize_text_field( $report_type );
$report_type = in_array( $report_type, [ 'enhanced', 'comprehensive' ], true ) ? 'enhanced' : 'basic';

if ( isset( $data['company_name'] ) ) {
$sanitized['company_name'] = sanitize_text_field( wp_unslash( $data['company_name'] ) );
}

$required_fields = [
'company_name' => __( 'Company name is required.', 'rtbcb' ),
'email'        => __( 'Email is required.', 'rtbcb' ),
];

if ( 'enhanced' === $report_type ) {
$required_fields = array_merge(
$required_fields,
[
'company_size'         => __( 'Company size is required.', 'rtbcb' ),
'industry'             => __( 'Industry is required.', 'rtbcb' ),
'job_title'            => __( 'Job title is required.', 'rtbcb' ),
'num_entities'         => __( 'Number of legal entities is required.', 'rtbcb' ),
'num_currencies'       => __( 'Number of active currencies is required.', 'rtbcb' ),
'num_banks'            => __( 'Number of banking relationships is required.', 'rtbcb' ),
'hours_reconciliation' => __( 'Bank reconciliation hours are required.', 'rtbcb' ),
'hours_cash_positioning' => __( 'Cash positioning hours are required.', 'rtbcb' ),
'ftes'                 => __( 'Treasury team size is required.', 'rtbcb' ),
'treasury_automation'  => __( 'Treasury workflow automation level is required.', 'rtbcb' ),
]
);
}

foreach ( $required_fields as $field => $message ) {
if ( ! isset( $sanitized[ $field ] ) || '' === $sanitized[ $field ] ) {
return [
'error' => $message,
];
}
}

$numeric_fields = [
'hours_reconciliation',
'hours_cash_positioning',
'num_banks',
'ftes',
'num_entities',
'num_currencies',
];

foreach ( $numeric_fields as $field ) {
if ( isset( $data[ $field ] ) && '' !== $data[ $field ] && ! is_numeric( $data[ $field ] ) ) {
return [
'error' => sprintf(
__( '%s must be a numeric value.', 'rtbcb' ),
ucwords( str_replace( '_', ' ', $field ) )
),
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

