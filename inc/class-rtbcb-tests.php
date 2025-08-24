<?php
/**
 * Core testing helper methods.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class RTBCB_Tests.
 */
class RTBCB_Tests {
    /**
     * Verify LLM integration.
     *
     * @return array
     */
    public static function test_llm_integration() {
        if ( ! class_exists( 'RTBCB_LLM' ) ) {
            return [
                'passed'  => false,
                'message' => __( 'LLM class not found.', 'rtbcb' ),
            ];
        }

        try {
            $llm     = new RTBCB_LLM();
            $analysis = $llm->generate_business_case( [ 'company_name' => 'Test' ], [] );
            if ( is_wp_error( $analysis ) ) {
                return [
                    'passed'  => false,
                    'message' => $analysis->get_error_message(),
                ];
            }

            return [
                'passed'  => true,
                'message' => __( 'LLM generated response successfully.', 'rtbcb' ),
            ];
        } catch ( Exception $e ) {
            return [
                'passed'  => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify RAG index search works.
     *
     * @return array
     */
    public static function test_rag_index() {
        try {
            $rag     = new RTBCB_RAG();
            $results = $rag->search_similar( 'test', 1 );
            $passed  = is_array( $results );
            return [
                'passed'  => $passed,
                'message' => $passed ? __( 'RAG search returned results.', 'rtbcb' ) : __( 'RAG search failed.', 'rtbcb' ),
                'details' => $results,
            ];
        } catch ( Exception $e ) {
            return [
                'passed'  => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify ROI calculator functions.
     *
     * @return array
     */
    public static function test_roi_calculations() {
        $inputs = [
            'industry'               => 'test',
            'hours_reconciliation'   => 1,
            'hours_cash_positioning' => 1,
            'num_banks'              => 1,
            'ftes'                   => 1,
        ];

        try {
            $roi    = RTBCB_Calculator::calculate_roi( $inputs );
            $passed = is_array( $roi );
            return [
                'passed'  => $passed,
                'message' => $passed ? __( 'ROI calculation successful.', 'rtbcb' ) : __( 'ROI calculation failed.', 'rtbcb' ),
                'details' => $roi,
            ];
        } catch ( Exception $e ) {
            return [
                'passed'  => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify Portal integration.
     *
     * @return array
     */
    public static function test_portal() {
        $vendors = apply_filters( 'rt_portal_get_vendors', [] );
        if ( is_wp_error( $vendors ) ) {
            return [
                'passed'  => false,
                'message' => $vendors->get_error_message(),
            ];
        }

        $count  = is_array( $vendors ) ? count( $vendors ) : 0;
        $passed = $count > 0;

        return [
            'passed'  => $passed,
            'message' => $passed ? sprintf( __( 'Retrieved %d vendors.', 'rtbcb' ), $count ) : __( 'Portal returned no vendors.', 'rtbcb' ),
            'details' => [
                'vendor_count' => $count,
            ],
        ];
    }
}
