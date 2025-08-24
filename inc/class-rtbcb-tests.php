<?php
/**
 * Test utilities for Real Treasury Business Case Builder.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Class RTBCB_Tests.
 *
 * Provides lightweight self-tests for various subsystems.
 */
class RTBCB_Tests {
    /**
     * Verify LLM integration can generate a basic business case.
     *
     * @return array{
     *     passed: bool,
     *     message: string,
     * }
     */
    public static function test_llm_integration() {
        if ( ! class_exists( 'RTBCB_LLM' ) ) {
            return [
                'passed'  => false,
                'message' => __( 'RTBCB_LLM class not available.', 'rtbcb' ),
            ];
        }

        $llm    = new RTBCB_LLM();
        $result = $llm->generate_business_case(
            [
                'company_name' => 'Test Co',
                'industry'     => 'banking',
                'company_size' => 'small',
            ],
            []
        );

        if ( is_wp_error( $result ) || empty( $result ) ) {
            return [
                'passed'  => false,
                'message' => __( 'Failed to generate business case.', 'rtbcb' ),
            ];
        }

        return [
            'passed'  => true,
            'message' => __( 'LLM integration operational.', 'rtbcb' ),
        ];
    }

    /**
     * Verify RAG index search returns an array.
     *
     * @return array{
     *     passed: bool,
     *     message: string,
     * }
     */
    public static function test_rag_index() {
        if ( ! class_exists( 'RTBCB_RAG' ) ) {
            return [
                'passed'  => false,
                'message' => __( 'RAG class not available.', 'rtbcb' ),
            ];
        }

        $rag     = new RTBCB_RAG();
        $results = $rag->search_similar( 'test', 1 );
        $passed  = is_array( $results );

        return [
            'passed'  => $passed,
            'message' => $passed ? __( 'RAG search executed.', 'rtbcb' ) : __( 'RAG search failed.', 'rtbcb' ),
        ];
    }

    /**
     * Perform a simple RAG search.
     *
     * @return bool True on success, false on failure.
     */
    public static function test_rag_search() {
        if ( ! class_exists( 'RTBCB_RAG' ) ) {
            return false;
        }

        $rag     = new RTBCB_RAG();
        $results = $rag->search_similar( 'test', 1 );

        return is_array( $results );
    }

    /**
     * Ensure ROI calculator returns data.
     *
     * @return array{
     *     passed: bool,
     *     message: string,
     * }
     */
    public static function test_roi_calculations() {
        $sample = [
            'industry'               => 'banking',
            'hours_reconciliation'   => 1,
            'hours_cash_positioning' => 1,
            'num_banks'              => 1,
            'ftes'                   => 1,
        ];

        try {
            $roi = RTBCB_Calculator::calculate_roi( $sample );
        } catch ( Exception $e ) {
            $roi = [];
        }

        $passed = is_array( $roi ) && ! empty( $roi );

        return [
            'passed'  => $passed,
            'message' => $passed ? __( 'ROI calculations succeeded.', 'rtbcb' ) : __( 'ROI calculations failed.', 'rtbcb' ),
        ];
    }

    /**
     * Confirm portal vendor retrieval works.
     *
     * @return array{
     *     passed: bool,
     *     message: string,
     * }
     */
    public static function test_portal() {
        if ( ! has_filter( 'rt_portal_get_vendors' ) ) {
            return [
                'passed'  => false,
                'message' => __( 'Portal filters not available.', 'rtbcb' ),
            ];
        }

        $vendors = apply_filters( 'rt_portal_get_vendors', [] );
        $count   = is_array( $vendors ) ? count( $vendors ) : 0;
        $passed  = $count > 0;

        return [
            'passed'  => $passed,
            'message' => $passed
                ? sprintf( _n( '%d vendor retrieved.', '%d vendors retrieved.', $count, 'rtbcb' ), $count )
                : __( 'Portal connection returned no vendors.', 'rtbcb' ),
        ];
    }
}
