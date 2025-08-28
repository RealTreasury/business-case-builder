<?php
/**
 * Treasury maturity model assessment.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class RTBCB_Maturity_Model.
 */
class RTBCB_Maturity_Model {
    /**
     * Assess treasury maturity level.
     *
     * @param array $company_data Company data.
     * @return array {
     *     @type string $level     Maturity level.
     *     @type string $narrative Narrative explaining the level.
     * }
     */
    public function assess( $company_data ) {
        $staff = isset( $company_data['staff_count'] ) ? intval( $company_data['staff_count'] ) : 0;

        if ( $staff > 1000 ) {
            $level = __( 'Optimized', 'rtbcb' );
        } elseif ( $staff > 500 ) {
            $level = __( 'Strategic', 'rtbcb' );
        } elseif ( $staff > 100 ) {
            $level = __( 'Developing', 'rtbcb' );
        } else {
            $level = __( 'Basic', 'rtbcb' );
        }

        $narrative = sprintf(
            __( 'Current maturity level is %1$s based on staff count of %2$d.', 'rtbcb' ),
            $level,
            $staff
        );

        return [
            'level'     => $level,
            'narrative' => $narrative,
        ];
    }
}
