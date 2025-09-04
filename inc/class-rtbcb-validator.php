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

               $report_type = isset( $data['report_type'] ) ? sanitize_text_field( wp_unslash( $data['report_type'] ) ) : 'basic';
               $report_type = in_array( $report_type, [ 'basic', 'enhanced' ], true ) ? $report_type : 'basic';

               $required_fields = [
                       'company_name' => __( 'Company name is required.', 'rtbcb' ),
                       'email'        => __( 'Email is required.', 'rtbcb' ),
               ];

               if ( 'enhanced' === $report_type ) {
                       $required_fields = array_merge(
                               $required_fields,
                               [
                                       'company_size'           => __( 'Company size is required.', 'rtbcb' ),
                                       'industry'               => __( 'Industry is required.', 'rtbcb' ),
                                       'job_title'              => __( 'Job title is required.', 'rtbcb' ),
                                       'num_entities'           => __( 'Number of entities is required.', 'rtbcb' ),
                                       'num_currencies'         => __( 'Number of currencies is required.', 'rtbcb' ),
                                       'num_banks'              => __( 'Number of banks is required.', 'rtbcb' ),
                                       'hours_reconciliation'   => __( 'Reconciliation hours are required.', 'rtbcb' ),
                                       'hours_cash_positioning' => __( 'Cash positioning hours are required.', 'rtbcb' ),
                                       'ftes'                   => __( 'FTEs are required.', 'rtbcb' ),
                               ]
                       );
               }

               foreach ( $required_fields as $field => $message ) {
                       $value = $sanitized[ $field ] ?? ( $data[ $field ] ?? '' );
                       if ( empty( $value ) ) {
                               return [
                                       'error' => $message,
                               ];
                       }
               }

$numeric_fields = [
'num_entities',
'num_currencies',
'num_banks',
'hours_reconciliation',
'hours_cash_positioning',
'ftes',
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
