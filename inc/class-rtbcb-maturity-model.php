<?php
/**
 * Simple treasury maturity model.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
}
