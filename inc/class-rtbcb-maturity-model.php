<?php
defined( 'ABSPATH' ) || exit;

/**
	* Simple treasury maturity model.
	*
	* @package RealTreasuryBusinessCaseBuilder
	*/

/**
	* Provides a basic maturity assessment.
	*/
class RTBCB_Maturity_Model {
	/**
	* Assess maturity level using full-time equivalents.
	*
	* @param array $company_data Company data inputs.
	* @return array Assessment results.
	*/
	public function assess( $company_data ) {
		$level = __( 'Basic', 'rtbcb' );
		$ftes  = isset( $company_data['ftes'] ) ? floatval( $company_data['ftes'] ) : 1.0;

		if ( $ftes > 5 ) {
			$level = __( 'Advanced', 'rtbcb' );
		} elseif ( $ftes > 2 ) {
			$level = __( 'Intermediate', 'rtbcb' );
		}

		return [
			'level'      => $level,
			'assessment' => sprintf(
				/* translators: %s: maturity level */
				__( 'Treasury maturity level: %s', 'rtbcb' ),
				$level
			),
			'score'      => rand( 60, 90 ),
		];
	}

	/**
	* Assess treasury maturity from user inputs.
	*
	* Sanitizes provided data and derives a maturity level
	* with explanatory rationale.
	*
	* @param array $user_inputs User-provided data.
	* @return array {
	*     @type string $level     Maturity level.
	*     @type string $rationale Explanation for the level.
	* }
	*/
	private function assess_treasury_maturity( $user_inputs ) {
		$sanitized = [];

		foreach ( $user_inputs as $key => $value ) {
			$sanitized[ $key ] = sanitize_text_field( $value );
		}

		$ftes = isset( $sanitized['ftes'] ) ? floatval( $sanitized['ftes'] ) : 0.0;

		if ( $ftes > 5 ) {
			$level     = __( 'Advanced', 'rtbcb' );
			$rationale = __( 'More than five dedicated treasury FTEs suggests advanced maturity.', 'rtbcb' );
		} elseif ( $ftes > 2 ) {
			$level     = __( 'Intermediate', 'rtbcb' );
			$rationale = __( 'Between two and five FTEs indicates intermediate maturity.', 'rtbcb' );
		} else {
			$level     = __( 'Basic', 'rtbcb' );
			$rationale = __( 'Limited FTEs dedicated to treasury keep maturity at a basic level.', 'rtbcb' );
		}

		return [
			'level'     => $level,
			'rationale' => $rationale,
		];
	}
}
