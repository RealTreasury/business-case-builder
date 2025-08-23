<?php
/**
 * Async job processing for RTBCB.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles asynchronous background processing for company overview generation.
 */
class RTBCB_Async_Jobs {
    /**
     * Initialize hooks.
     *
     * @return void
     */
    public static function init() {
        add_action( 'wp_ajax_rtbcb_start_company_overview', [ __CLASS__, 'start_company_overview' ] );
        add_action( 'wp_ajax_rtbcb_check_job_status', [ __CLASS__, 'check_job_status' ] );
        add_action( 'rtbcb_process_company_overview_async', [ __CLASS__, 'process_company_overview' ], 10, 2 );
    }

    /**
     * Start async company overview generation.
     *
     * @return void
     */
    public static function start_company_overview() {
        check_ajax_referer( 'rtbcb_test_company_overview', 'nonce' );

        $company_name = isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';
        if ( empty( $company_name ) ) {
            wp_die( esc_html__( 'Invalid company name.', 'rtbcb' ) );
        }

        $job_id = wp_generate_uuid4();

        set_transient(
            "rtbcb_job_{$job_id}",
            [
                'status'       => 'processing',
                'company_name' => $company_name,
                'started'      => time(),
                'type'         => 'company_overview',
            ],
            600
        );

        wp_schedule_single_event( time() + 1, 'rtbcb_process_company_overview_async', [ $job_id, $company_name ] );

        wp_send_json_success(
            [
                'job_id'  => $job_id,
                'status'  => 'started',
                'message' => __( 'Analysis started. Please wait...', 'rtbcb' ),
            ]
        );
    }

    /**
     * Check job status.
     *
     * @return void
     */
    public static function check_job_status() {
        check_ajax_referer( 'rtbcb_test_company_overview', 'nonce' );

        $job_id   = isset( $_POST['job_id'] ) ? sanitize_text_field( wp_unslash( $_POST['job_id'] ) ) : '';
        $job_data = get_transient( "rtbcb_job_{$job_id}" );

        if ( ! $job_data ) {
            wp_send_json_error( [ 'message' => __( 'Job not found', 'rtbcb' ) ] );
        }

        wp_send_json_success( $job_data );
    }

    /**
     * Process company overview in background.
     *
     * @param string $job_id       Job identifier.
     * @param string $company_name Company name.
     *
     * @return void
     */
    public static function process_company_overview( $job_id, $company_name ) {
        error_log( "RTBCB: Starting background processing for job {$job_id}" );

        $job_data             = get_transient( "rtbcb_job_{$job_id}" ) ?: [];
        $job_data['status']   = 'processing';
        $job_data['progress'] = __( 'Analyzing company data...', 'rtbcb' );
        set_transient( "rtbcb_job_{$job_id}", $job_data, 600 );

        try {
            $llm    = new RTBCB_LLM();
            $result = $llm->generate_company_overview( $company_name );

            if ( is_wp_error( $result ) ) {
                $job_data['status'] = 'failed';
                $job_data['error']  = $result->get_error_message();
            } else {
                $job_data['status']    = 'completed';
                $job_data['result']    = $result;
                $job_data['completed'] = time();
            }
        } catch ( Exception $e ) {
            error_log( 'RTBCB: Background job failed: ' . $e->getMessage() );
            $job_data['status'] = 'failed';
            $job_data['error']  = sprintf(
                /* translators: %s: error message. */
                __( 'Processing failed: %s', 'rtbcb' ),
                $e->getMessage()
            );
        }

        set_transient( "rtbcb_job_{$job_id}", $job_data, 600 );
        error_log( 'RTBCB: Background job ' . $job_id . ' completed with status: ' . $job_data['status'] );
    }
}

RTBCB_Async_Jobs::init();
