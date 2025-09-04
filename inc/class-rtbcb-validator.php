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

               $text_fields = [
                       'job_title',
                       'treasury_automation',
                       'bank_import_frequency',
                       'reporting_cadence',
                       'payment_approval_workflow',
                       'reconciliation_method',
                       'cash_update_frequency',
                       'forecast_horizon',
                       'fx_management',
                       'intercompany_lending',
                       'audit_trail',
                       'current_tech',
                       'business_objective',
                       'implementation_timeline',
                       'budget_range',
               ];
               foreach ( $text_fields as $field ) {
                       if ( isset( $data[ $field ] ) ) {
                               $sanitized[ $field ] = sanitize_text_field( wp_unslash( $data[ $field ] ) );
                       }
               }

               $numeric_fields_extra = [
                       'annual_payment_volume',
                       'num_entities',
                       'num_currencies',
               ];
               foreach ( $numeric_fields_extra as $field ) {
                       if ( isset( $data[ $field ] ) && is_numeric( $data[ $field ] ) ) {
                               $sanitized[ $field ] = floatval( $data[ $field ] );
                       }
               }

               $array_fields = [
                       'primary_systems',
                       'reg_reporting',
                       'integration_requirements',
                       'investment_activities',
                       'treasury_kpis',
                       'decision_makers',
               ];
               foreach ( $array_fields as $field ) {
                       if ( isset( $data[ $field ] ) && is_array( $data[ $field ] ) ) {
                               $sanitized[ $field ] = array_map( 'sanitize_text_field', wp_unslash( $data[ $field ] ) );
                       }
               }

               if ( isset( $data['pain_point_rank'] ) && is_array( $data['pain_point_rank'] ) ) {
                       $sanitized['pain_point_rank'] = [];
                       foreach ( $data['pain_point_rank'] as $key => $value ) {
                               $sanitized['pain_point_rank'][ sanitize_key( $key ) ] = absint( $value );
                       }
               }

		if ( isset( $data['company_name'] ) ) {
			$sanitized['company_name'] = sanitize_text_field( wp_unslash( $data['company_name'] ) );
		}

               $required_fields = [
                        'company_name'           => __( 'Company name is required.', 'rtbcb' ),
                        'email'                  => __( 'Email is required.', 'rtbcb' ),
                        'company_size'           => __( 'Company size is required.', 'rtbcb' ),
                        'num_entities'           => __( 'Number of legal entities is required.', 'rtbcb' ),
                        'num_currencies'         => __( 'Number of active currencies is required.', 'rtbcb' ),
                        'treasury_automation'    => __( 'Treasury automation level is required.', 'rtbcb' ),
                        'primary_systems'        => __( 'Primary treasury systems are required.', 'rtbcb' ),
                        'bank_import_frequency'  => __( 'Bank statement import frequency is required.', 'rtbcb' ),
                        'reporting_cadence'      => __( 'Reporting cadence is required.', 'rtbcb' ),
                        'annual_payment_volume'  => __( 'Annual payment volume is required.', 'rtbcb' ),
                        'payment_approval_workflow' => __( 'Payment approval workflow is required.', 'rtbcb' ),
                        'reconciliation_method'  => __( 'Reconciliation method is required.', 'rtbcb' ),
                        'cash_update_frequency'  => __( 'Cash position update frequency is required.', 'rtbcb' ),
                        'integration_requirements' => __( 'Integration requirements are required.', 'rtbcb' ),
                        'forecast_horizon'       => __( 'Forecasting horizon is required.', 'rtbcb' ),
                        'fx_management'          => __( 'FX exposure management is required.', 'rtbcb' ),
                        'investment_activities'  => __( 'Investment activities are required.', 'rtbcb' ),
                        'intercompany_lending'   => __( 'Intercompany lending or netting is required.', 'rtbcb' ),
                        'audit_trail'            => __( 'Audit trail requirement is required.', 'rtbcb' ),
                        'implementation_timeline' => __( 'Implementation timeline is required.', 'rtbcb' ),
                        'budget_range'           => __( 'Budget range is required.', 'rtbcb' ),
               ];

		foreach ( $required_fields as $field => $message ) {
			if ( empty( $sanitized[ $field ] ) ) {
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
                        'annual_payment_volume',
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
