<?php
/**
 * Admin functionality for Real Treasury Business Case Builder plugin.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles admin menus, settings, and AJAX actions.
 */
class RTBCB_Admin {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'wp_ajax_rtbcb_test_connection', [ $this, 'test_api_connection' ] );
        add_action( 'wp_ajax_rtbcb_rebuild_index', [ $this, 'rebuild_rag_index' ] );
        add_action( 'wp_ajax_rtbcb_generate_case', [ $this, 'handle_generate_case' ] );
        add_action( 'wp_ajax_nopriv_rtbcb_generate_case', [ $this, 'handle_generate_case' ] );
    }

    /**
     * Register admin menu and submenus.
     *
     * @return void
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Business Case Builder', 'rtbcb' ),
            __( 'Real Treasury', 'rtbcb' ),
            'manage_options',
            'rtbcb-dashboard',
            [ $this, 'render_dashboard' ],
            'dashicons-calculator',
            30
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Settings', 'rtbcb' ),
            __( 'Settings', 'rtbcb' ),
            'manage_options',
            'rtbcb-settings',
            [ $this, 'render_settings' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Leads', 'rtbcb' ),
            __( 'Leads', 'rtbcb' ),
            'manage_options',
            'rtbcb-leads',
            [ $this, 'render_leads' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Data Health', 'rtbcb' ),
            __( 'Data Health', 'rtbcb' ),
            'manage_options',
            'rtbcb-data-health',
            [ $this, 'render_data_health' ]
        );
    }

    /**
     * Render settings page.
     *
     * @return void
     */
    public function render_settings() {
        include RTBCB_DIR . 'admin/settings-page.php';
    }

    /**
     * Render leads page.
     *
     * @return void
     */
    public function render_leads() {
        // $leads = RTBCB_Leads::get_all_leads(); // Uncomment when RTBCB_Leads is built.
        include RTBCB_DIR . 'admin/leads-page.php';
    }

    /**
     * Render data health page.
     *
     * @return void
     */
    public function render_data_health() {
        $status        = $this->get_system_health();
        $portal_active = $status['portal'];
        $last_indexed  = get_option( 'rtbcb_last_indexed', '' );
        include RTBCB_DIR . 'admin/data-health-page.php';
    }

    /**
     * Render dashboard.
     *
     * @return void
     */
    public function render_dashboard() {
        echo '<h1>' . esc_html__( 'Business Case Builder Dashboard', 'rtbcb' ) . '</h1>';

        $count = intval( get_option( 'rtbcb_pdf_count', 0 ) );
        echo '<p>' . sprintf( esc_html__( 'PDF reports generated: %d', 'rtbcb' ), $count ) . '</p>';

        $status = $this->get_system_health();
        echo '<h2>' . esc_html__( 'System Status', 'rtbcb' ) . '</h2>';
        echo '<ul>';
        echo '<li>' . esc_html__( 'OpenAI API:', 'rtbcb' ) . ' ' . ( $status['openai'] ? esc_html__( 'Connected', 'rtbcb' ) : esc_html__( 'Disconnected', 'rtbcb' ) ) . '</li>';
        echo '<li>' . esc_html__( 'Portal Integration:', 'rtbcb' ) . ' ' . ( $status['portal'] ? esc_html__( 'Active', 'rtbcb' ) : esc_html__( 'Inactive', 'rtbcb' ) ) . '</li>';
        echo '<li>' . esc_html__( 'RAG Index:', 'rtbcb' ) . ' ' . ( $status['rag'] ? esc_html__( 'Built', 'rtbcb' ) : esc_html__( 'Missing', 'rtbcb' ) ) . '</li>';
        echo '<li>' . esc_html__( 'PDF Library:', 'rtbcb' ) . ' ' . ( $status['pdf'] ? esc_html__( 'Available', 'rtbcb' ) : esc_html__( 'Unavailable', 'rtbcb' ) ) . '</li>';
        echo '</ul>';
    }

    /**
     * Register plugin settings.
     *
     * @return void
     */
    public function register_settings() {
        register_setting(
            'rtbcb_settings',
            'rtbcb_openai_api_key',
            [
                'sanitize_callback' => 'sanitize_text_field',
            ]
        );

        register_setting(
            'rtbcb_settings',
            'rtbcb_mini_model',
            [
                'sanitize_callback' => 'sanitize_text_field',
            ]
        );

        register_setting(
            'rtbcb_settings',
            'rtbcb_premium_model',
            [
                'sanitize_callback' => 'sanitize_text_field',
            ]
        );

        register_setting(
            'rtbcb_settings',
            'rtbcb_embedding_model',
            [
                'sanitize_callback' => 'sanitize_text_field',
            ]
        );
    }

    /**
     * Test the OpenAI API connection.
     *
     * @return void
     */
    public function test_api_connection() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                [ 'message' => __( 'Permission denied.', 'rtbcb' ) ],
                403
            );
        }

        $api_key = get_option( 'rtbcb_openai_api_key' );
        if ( empty( $api_key ) ) {
            wp_send_json_error(
                [ 'message' => __( 'Missing API key.', 'rtbcb' ) ]
            );
        }

        $response = wp_remote_get(
            'https://api.openai.com/v1/models',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'timeout' => 20,
            ]
        );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error(
                [ 'message' => $response->get_error_message() ]
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            wp_send_json_error(
                [ 'message' => sprintf( __( 'API request failed (%d).', 'rtbcb' ), $code ) ]
            );
        }

        wp_send_json_success(
            [ 'message' => __( 'Connection successful.', 'rtbcb' ) ]
        );
    }

    /**
     * Rebuild the RAG index.
     *
     * @return void
     */
    public function rebuild_rag_index() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                [ 'message' => __( 'Permission denied.', 'rtbcb' ) ],
                403
            );
        }

        require_once RTBCB_DIR . 'inc/class-rtbcb-rag.php';

        $rag = new RTBCB_RAG();
        $rag->rebuild_index();

        wp_send_json_success(
            [ 'message' => __( 'RAG index rebuilt.', 'rtbcb' ) ]
        );
    }

    /**
     * Handle AJAX request to generate a business case.
     *
     * @return void
     */
    public function handle_generate_case() {
        try {
            check_ajax_referer( 'rtbcb_nonce', 'rtbcb_nonce' );

            $company_size           = isset( $_POST['company_size'] ) ? sanitize_text_field( wp_unslash( $_POST['company_size'] ) ) : '';
            $pain_points            = isset( $_POST['pain_points'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['pain_points'] ) ) : [];
            $hours_reconciliation   = isset( $_POST['hours_reconciliation'] ) ? floatval( wp_unslash( $_POST['hours_reconciliation'] ) ) : 0;
            $hours_cash_positioning = isset( $_POST['hours_cash_positioning'] ) ? floatval( wp_unslash( $_POST['hours_cash_positioning'] ) ) : 0;
            $num_banks              = isset( $_POST['num_banks'] ) ? intval( wp_unslash( $_POST['num_banks'] ) ) : 0;
            $ftes                   = isset( $_POST['ftes'] ) ? intval( wp_unslash( $_POST['ftes'] ) ) : 0;
            $email                  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

            $user_inputs = [
                'company_size'           => $company_size,
                'pain_points'            => $pain_points,
                'hours_reconciliation'   => $hours_reconciliation,
                'hours_cash_positioning' => $hours_cash_positioning,
                'num_banks'              => $num_banks,
                'ftes'                   => $ftes,
                'email'                  => $email,
            ];
            error_log( 'RTBCB: Inputs sanitized' );

            $scenarios = RTBCB_Calculator::calculate_roi( $user_inputs );
            error_log( 'RTBCB: ROI calculated' );

            $rag            = new RTBCB_RAG();
            $context_chunks = [];
            foreach ( $pain_points as $point ) {
                $context_chunks = array_merge( $context_chunks, $rag->search_similar( $point ) );
            }
            error_log( 'RTBCB: RAG search complete' );

            $llm       = new RTBCB_LLM();
            $narrative = $llm->generate_business_case( $user_inputs, $scenarios, $context_chunks );
            error_log( 'RTBCB: LLM generation complete' );

            if ( isset( $narrative['error'] ) ) {
                wp_send_json_error( $narrative['error'] );
            }

            $recommendation = [];
            if ( class_exists( 'RTBCB_Category_Recommender' ) ) {
                try {
                    $recommendation = RTBCB_Category_Recommender::recommend_category( $user_inputs );
                } catch ( Exception $e ) {
                    error_log( 'RTBCB: Recommendation failed - ' . $e->getMessage() );
                }
            }

            $download_url = '';
            $file_path    = '';
            $report_data  = [
                'user_inputs'    => $user_inputs,
                'scenarios'      => $scenarios,
                'narrative'      => $narrative,
                'recommendation' => $recommendation,
            ];

            try {
                $pdf        = new RTBCB_PDF( $report_data );
                $file_path  = $pdf->generate_business_case();
                $download_url = RTBCB_PDF::get_download_url( $file_path );

                $count = intval( get_option( 'rtbcb_pdf_count', 0 ) );
                update_option( 'rtbcb_pdf_count', $count + 1 );
            } catch ( Exception $e ) {
                error_log( 'RTBCB: PDF generation failed - ' . $e->getMessage() );
            }

            $lead_data = [
                'email'                => $email,
                'company_size'         => $company_size,
                'pain_points'          => $pain_points,
                'hours_reconciliation' => $hours_reconciliation,
                'hours_cash_positioning'=> $hours_cash_positioning,
                'num_banks'            => $num_banks,
                'ftes'                 => $ftes,
                'recommended_category' => $recommendation['recommended'] ?? '',
                'roi_low'              => $scenarios['low']['total_annual_benefit'],
                'roi_base'             => $scenarios['base']['total_annual_benefit'],
                'roi_high'             => $scenarios['high']['total_annual_benefit'],
                'pdf_generated'        => $download_url ? 1 : 0,
                'pdf_path'             => $file_path,
            ];

            if ( class_exists( 'RTBCB_Leads' ) ) {
                try {
                    RTBCB_Leads::save_lead( $lead_data );
                } catch ( Exception $e ) {
                    error_log( 'RTBCB: Lead save failed - ' . $e->getMessage() );
                }
            }

            wp_send_json_success(
                [
                    'scenarios'    => $scenarios,
                    'narrative'    => $narrative,
                    'download_url' => $download_url,
                    'recommendation' => $recommendation,
                ]
            );
        } catch ( Exception $e ) {
            error_log( 'RTBCB: Error generating case - ' . $e->getMessage() );
            wp_send_json_error( __( 'Failed to generate business case.', 'rtbcb' ) );
        }
    }

    /**
     * Check if Portal integration hooks exist.
     *
     * @return bool
     */
    private function check_portal_integration() {
        return (bool) ( has_filter( 'rt_portal_get_vendors' ) || has_filter( 'rt_portal_get_vendor_notes' ) );
    }

    /**
     * Run system health checks.
     *
     * @return array
     */
    private function get_system_health() {
        return [
            'openai' => $this->check_openai_connection(),
            'portal' => $this->check_portal_integration(),
            'rag'    => (bool) get_option( 'rtbcb_last_indexed', '' ),
            'pdf'    => class_exists( '\\Mpdf\\Mpdf' ),
        ];
    }

    /**
     * Verify OpenAI API connectivity.
     *
     * @return bool
     */
    private function check_openai_connection() {
        $api_key = get_option( 'rtbcb_openai_api_key' );
        if ( empty( $api_key ) ) {
            return false;
        }

        $response = wp_remote_get(
            'https://api.openai.com/v1/models',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                ],
                'timeout' => 10,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        return 200 === wp_remote_retrieve_response_code( $response );
    }
}

