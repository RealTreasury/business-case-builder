<?php
/**
 * Integration test utilities.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Class RTBCB_Tests.
 */
class RTBCB_Tests {
    /**
     * Run all integration tests.
     *
     * @return array
     */
    public static function run_integration_tests() {
        $results = [];

        $results['portal_integration'] = self::test_portal_integration();
        $results['roi_calculations']  = self::test_roi_calculations();
        $results['llm_integration']   = self::test_llm_integration();
        $results['rag_index']         = self::test_rag_index();

        return $results;
    }

    /**
     * Verify portal integration hooks return data.
     *
     * @return array Test result.
     */
    private static function test_portal_integration() {
        $vendors = apply_filters( 'rt_portal_get_vendors', [] );

        if ( is_array( $vendors ) ) {
            return [
                'passed'  => true,
                'message' => sprintf( __( 'Portal returned %d vendors.', 'rtbcb' ), count( $vendors ) ),
            ];
        }

        return [
            'passed'  => false,
            'message' => __( 'Portal did not return expected data.', 'rtbcb' ),
        ];
    }

    /**
     * Ensure ROI calculator static method is available.
     *
     * @return array Test result.
     */
    private static function test_roi_calculations() {
        if ( ! class_exists( 'RTBCB_Calculator' ) || ! is_callable( [ 'RTBCB_Calculator', 'calculate_roi' ] ) ) {
            return [
                'passed'  => false,
                'message' => __( 'ROI calculator unavailable.', 'rtbcb' ),
            ];
        }

        return [
            'passed'  => true,
            'message' => __( 'ROI calculator static method available.', 'rtbcb' ),
        ];
    }

    /**
     * Check LLM integration readiness.
     *
     * @return array Test result.
     */
    private static function test_llm_integration() {
        if ( ! class_exists( 'RTBCB_LLM' ) ) {
            return [
                'passed'  => false,
                'message' => __( 'LLM integration class missing.', 'rtbcb' ),
            ];
        }

        if ( ! method_exists( 'RTBCB_Router', 'route_model' ) ) {
            return [
                'passed'  => false,
                'message' => __( 'Model routing not available.', 'rtbcb' ),
            ];
        }

        $llm = new RTBCB_LLM();

        $user_inputs = [
            'company_name' => 'Acme Corp',
            'company_size' => '$50M-$500M',
            'industry'     => 'manufacturing',
            'job_title'    => 'Treasury Manager',
            'pain_points'  => [ 'manual_processes', 'bank_fees' ],
        ];

        $roi_data = [
            'base' => [
                'labor_savings'        => 50000,
                'fee_savings'          => 10000,
                'error_reduction'      => 20000,
                'total_annual_benefit' => 80000,
                'roi_percentage'       => 160,
                'assumptions'          => [],
            ],
        ];

        $response = $llm->generate_business_case( $user_inputs, $roi_data );

        if ( is_wp_error( $response ) ) {
            return [
                'passed'  => false,
                'message' => $response->get_error_message(),
            ];
        }

        $passed  = is_array( $response );
        $message = $passed ? __( 'LLM call executed.', 'rtbcb' ) : __( 'LLM call failed.', 'rtbcb' );

        return [
            'passed'  => $passed,
            'message' => $message,
        ];
    }

    /**
     * Verify RAG index search runs.
     *
     * @return array Test result.
     */
    private static function test_rag_index() {
        if ( ! class_exists( 'RTBCB_RAG' ) ) {
            return [
                'passed'  => false,
                'message' => __( 'RAG class missing.', 'rtbcb' ),
            ];
        }

        $rag     = new RTBCB_RAG();
        $results = $rag->search_similar( 'test', 1 );

        $passed  = is_array( $results );
        $message = $passed ? __( 'RAG search executed.', 'rtbcb' ) : __( 'RAG search failed.', 'rtbcb' );

        return [
            'passed'  => $passed,
            'message' => $message,
        ];
    }
}
