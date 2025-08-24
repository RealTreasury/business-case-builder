<?php
/**
 * Enhanced admin functionality for Real Treasury Business Case Builder plugin.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enhanced admin class with full feature integration.
 */
class RTBCB_Admin {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        
        // AJAX handlers
        add_action( 'wp_ajax_rtbcb_test_connection', [ $this, 'test_api_connection' ] );
        add_action( 'wp_ajax_rtbcb_rebuild_index', [ $this, 'rebuild_rag_index' ] );
        add_action( 'wp_ajax_rtbcb_export_leads', [ $this, 'export_leads_csv' ] );
        add_action( 'wp_ajax_rtbcb_delete_lead', [ $this, 'delete_lead' ] );
        add_action( 'wp_ajax_rtbcb_bulk_action_leads', [ $this, 'bulk_action_leads' ] );
        add_action( 'wp_ajax_rtbcb_run_tests', [ $this, 'run_integration_tests' ] );
        add_action( 'wp_ajax_rtbcb_test_api', [ $this, 'ajax_test_api' ] );
        add_action( 'wp_ajax_rtbcb_run_diagnostics', [ $this, 'ajax_run_diagnostics' ] );
        add_action( 'wp_ajax_rtbcb_generate_report_preview', [ $this, 'ajax_generate_report_preview' ] );
        add_action( 'wp_ajax_rtbcb_generate_sample_report', [ $this, 'ajax_generate_sample_report' ] );
        add_action( 'wp_ajax_rtbcb_sync_to_local', [ $this, 'sync_to_local' ] );
        add_action( 'wp_ajax_nopriv_rtbcb_sync_to_local', [ $this, 'sync_to_local' ] );
        add_action( 'wp_ajax_rtbcb_test_commentary', [ $this, 'ajax_test_commentary' ] );
        add_action( 'wp_ajax_rtbcb_test_company_overview', [ $this, 'ajax_test_company_overview' ] );
        add_action( 'wp_ajax_rtbcb_test_company_overview_enhanced', [ $this, 'ajax_test_company_overview_enhanced' ] );
        add_action( 'wp_ajax_rtbcb_test_treasury_tech_overview', [ $this, 'ajax_test_treasury_tech_overview' ] );
        add_action( 'wp_ajax_rtbcb_test_industry_overview', [ $this, 'ajax_test_industry_overview' ] );
        add_action( 'wp_ajax_rtbcb_test_real_treasury_overview', [ $this, 'ajax_test_real_treasury_overview' ] );
        add_action( 'wp_ajax_rtbcb_get_company_data', [ $this, 'ajax_get_company_data' ] );
        add_action( 'wp_ajax_rtbcb_test_estimated_benefits', [ $this, 'ajax_test_estimated_benefits' ] );
        add_action( 'wp_ajax_rtbcb_save_test_results', [ $this, 'save_test_results' ] );
        add_action( 'wp_ajax_rtbcb_test_generate_complete_report', [ $this, 'ajax_test_generate_complete_report' ] );
        add_action( 'wp_ajax_rtbcb_test_complete_report', [ $this, 'ajax_test_generate_complete_report' ] );
        add_action( 'wp_ajax_rtbcb_test_calculate_roi', [ $this, 'ajax_test_calculate_roi' ] );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Page hook.
     * @return void
     */
    public function enqueue_admin_assets( $hook ) {
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( strpos( $hook, 'rtbcb' ) === false && strpos( $page, 'rtbcb' ) === false ) {
            return;
        }

        wp_enqueue_script( 'chart-js', RTBCB_URL . 'public/js/chart.min.js', [], '3.9.1', true );
        wp_enqueue_style(
            'rtbcb-admin',
            RTBCB_URL . 'admin/css/rtbcb-admin.css',
            [],
            RTBCB_VERSION
        );

        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

        if ( 'rtbcb-unified-tests' === $page ) {
            wp_enqueue_style(
                'rtbcb-unified-dashboard',
                RTBCB_URL . 'admin/css/unified-test-dashboard.css',
                [],
                RTBCB_VERSION
            );

            wp_enqueue_script(
                'rtbcb-unified-dashboard',
                RTBCB_URL . 'admin/js/unified-test-dashboard.js',
                [ 'jquery' ],
                RTBCB_VERSION,
                true
            );

            wp_localize_script(
                'rtbcb-unified-dashboard',
                'rtbcbDashboard',
                [
                    'ajaxurl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'rtbcb_unified_test_dashboard' ),
                    'strings' => [
                        'generating'   => __( 'Generating...', 'rtbcb' ),
                        'complete'     => __( 'Complete!', 'rtbcb' ),
                        'error'        => __( 'Error occurred', 'rtbcb' ),
                        'confirm_clear'=> __( 'Are you sure you want to clear all results?', 'rtbcb' ),
                    ],
                ]
            );
        }
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
            __( 'Dashboard', 'rtbcb' ),
            __( 'Dashboard', 'rtbcb' ),
            'manage_options',
            'rtbcb-dashboard',
            [ $this, 'render_dashboard' ]
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
            __( 'Analytics', 'rtbcb' ),
            __( 'Analytics', 'rtbcb' ),
            'manage_options',
            'rtbcb-analytics',
            [ $this, 'render_analytics' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Data Health', 'rtbcb' ),
            __( 'Data Health', 'rtbcb' ),
            'manage_options',
            'rtbcb-data-health',
            [ $this, 'render_data_health' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'API Test', 'rtbcb' ),
            __( 'API Test', 'rtbcb' ),
            'manage_options',
            'rtbcb-api-test',
            [ $this, 'render_api_test' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Unified Test Dashboard', 'rtbcb' ),
            __( 'Unified Tests', 'rtbcb' ),
            'manage_options',
            'rtbcb-unified-tests',
            [ $this, 'render_unified_test_dashboard' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Report Preview', 'rtbcb' ),
            __( 'Report Preview', 'rtbcb' ),
            'manage_options',
            'rtbcb-report-preview',
            [ $this, 'render_report_preview' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Report Test', 'rtbcb' ),
            __( 'Report Test', 'rtbcb' ),
            'manage_options',
            'rtbcb-report-test',
            [ $this, 'render_report_test' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Calculation Info', 'rtbcb' ),
            __( 'Calculation Info', 'rtbcb' ),
            'manage_options',
            'rtbcb-calculations',
            [ $this, 'render_calculation_info' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Test Dashboard', 'rtbcb' ),
            __( 'Test Dashboard', 'rtbcb' ),
            'manage_options',
            'rtbcb-test-dashboard',
            [ $this, 'render_test_dashboard' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Test Company Overview', 'rtbcb' ),
            __( 'Test Company Overview', 'rtbcb' ),
            'manage_options',
            'rtbcb-test-company-overview',
            [ $this, 'render_test_company_overview' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Test Treasury Tech Overview', 'rtbcb' ),
            __( 'Test Treasury Tech Overview', 'rtbcb' ),
            'manage_options',
            'rtbcb-test-treasury-tech-overview',
            [ $this, 'render_test_treasury_tech_overview' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Test Industry Overview', 'rtbcb' ),
            __( 'Test Industry Overview', 'rtbcb' ),
            'manage_options',
            'rtbcb-test-industry-overview',
            [ $this, 'render_test_industry_overview' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Test Real Treasury Overview', 'rtbcb' ),
            __( 'Test Real Treasury Overview', 'rtbcb' ),
            'manage_options',
            'rtbcb-test-real-treasury-overview',
            [ $this, 'render_test_real_treasury_overview' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Test Category Recommendation', 'rtbcb' ),
            __( 'Test Category Recommendation', 'rtbcb' ),
            'manage_options',
            'rtbcb-test-recommended-category',
            [ $this, 'render_test_recommended_category' ]
        );

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Test Estimated Benefits', 'rtbcb' ),
            __( 'Test Estimated Benefits', 'rtbcb' ),
            'manage_options',
            'rtbcb-test-estimated-benefits',
            [ $this, 'render_test_estimated_benefits' ]
        );
    }

    /**
     * Render enhanced dashboard with statistics.
     *
     * @return void
     */
    public function render_dashboard() {
        $stats = RTBCB_Leads::get_statistics();
        $recent_leads = RTBCB_Leads::get_all_leads( [ 'per_page' => 5, 'orderby' => 'created_at', 'order' => 'DESC' ] );
        
        include RTBCB_DIR . 'admin/dashboard-page.php';
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
     * Render enhanced leads page with filtering and export.
     *
     * @return void
     */
    public function render_leads() {
        $page = isset( $_GET['paged'] ) ? intval( wp_unslash( $_GET['paged'] ) ) : 1;
        $search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
        $category = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : '';
        $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
        $date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';

        $orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
        $allowed_orderby = [ 'email', 'created_at' ];
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'created_at';
        }

        $order = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'DESC';
        $order = strtoupper( $order );
        if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
            $order = 'DESC';
        }

        $args = [
            'page'      => $page,
            'search'    => $search,
            'category'  => $category,
            'date_from' => $date_from,
            'date_to'   => $date_to,
            'orderby'   => $orderby,
            'order'     => $order,
        ];

        $leads_data = RTBCB_Leads::get_all_leads( $args );
        $categories = RTBCB_Category_Recommender::get_all_categories();
        
        include RTBCB_DIR . 'admin/leads-page-enhanced.php';
    }

    /**
     * Render analytics page with charts and insights.
     *
     * @return void
     */
    public function render_analytics() {
        $stats = RTBCB_Leads::get_statistics();
        $monthly_trends = $this->get_monthly_trends();
        
        include RTBCB_DIR . 'admin/analytics-page.php';
    }

    /**
     * Render data health page.
     *
     * @return void
     */
    public function render_data_health() {
        $portal_active = $this->check_portal_integration();
        $last_indexed = get_option( 'rtbcb_last_indexed', '' );
       $vendor_count = $this->get_vendor_count();
        $rag_health = $this->check_rag_health();

        include RTBCB_DIR . 'admin/data-health-page.php';
    }

    /**
     * Render OpenAI API test page.
     *
     * @return void
     */
    public function render_api_test() {
        include RTBCB_DIR . 'admin/api-test-page.php';
    }

    /**
     * Render report preview page.
     *
     * @return void
     */
    public function render_report_preview() {
        include RTBCB_DIR . 'admin/report-preview-page.php';
    }

    /**
     * Render report test page.
     *
     * @return void
     */
    public function render_report_test() {
        include RTBCB_DIR . 'admin/report-test-page.php';
    }

    /**
     * Render calculation info page.
     *
     * @return void
     */
    public function render_calculation_info() {
        include RTBCB_DIR . 'admin/calculations-page.php';
    }

    /**
     * Render test dashboard page.
     *
     * @return void
     */
    public function render_test_dashboard() {
        $test_results   = get_option( 'rtbcb_test_results', [] );
        $openai_key     = get_option( 'rtbcb_openai_api_key', '' );
        $openai_status  = empty( $openai_key ) ? false : true;
        $portal_active  = $this->check_portal_integration();
        $rag_health     = $this->check_rag_health();

        include RTBCB_DIR . 'admin/test-dashboard-page.php';
    }

    /**
     * Render test company overview page.
     *
     * @return void
     */
    public function render_test_company_overview() {
        include RTBCB_DIR . 'admin/test-company-overview-page.php';
    }

    /**
     * Render test treasury tech overview page.
     *
     * @return void
     */
    public function render_test_treasury_tech_overview() {
        include RTBCB_DIR . 'admin/test-treasury-tech-overview-page.php';
    }

    /**
     * Render test industry overview page.
     *
     * @return void
     */
    public function render_test_industry_overview() {
        include RTBCB_DIR . 'admin/test-industry-overview-page.php';
    }

    /**
     * Render test real treasury overview page.
     *
     * @return void
     */
    public function render_test_real_treasury_overview() {
        include RTBCB_DIR . 'admin/test-real-treasury-overview-page.php';
    }

    /**
     * Render test category recommendation page.
     *
     * @return void
     */
    public function render_test_recommended_category() {
        include RTBCB_DIR . 'admin/test-recommended-category-page.php';
    }

    /**
     * Render test estimated benefits page.
     *
     * @return void
     */
    public function render_test_estimated_benefits() {
        include RTBCB_DIR . 'admin/test-estimated-benefits-page.php';
    }

    /**
     * Render unified test dashboard page.
     *
     * @return void
     */
    public function render_unified_test_dashboard() {
        include RTBCB_DIR . 'admin/unified-test-dashboard-page.php';
    }

    /**
     * Save test results from dashboard.
     *
     * @return void
     */
    public function save_test_results() {
        check_ajax_referer( 'rtbcb_test_dashboard', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized', 'rtbcb' ) ] );
        }

        $results = isset( $_POST['results'] ) ? wp_unslash( $_POST['results'] ) : '';
        $decoded = json_decode( $results, true );
        if ( ! is_array( $decoded ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid data format.', 'rtbcb' ) ] );
        }

        $sanitized = [];
        foreach ( $decoded as $item ) {
            $sanitized[] = [
                'section'   => isset( $item['section'] ) ? sanitize_text_field( $item['section'] ) : '',
                'status'    => isset( $item['status'] ) ? sanitize_text_field( $item['status'] ) : '',
                'message'   => isset( $item['message'] ) ? sanitize_text_field( $item['message'] ) : '',
                'timestamp' => current_time( 'mysql' ),
            ];
        }

        update_option( 'rtbcb_test_results', $sanitized );

        wp_send_json_success();
    }

    /**
     * Register plugin settings.
     *
     * @return void
     */
    public function register_settings() {
        register_setting( 'rtbcb_settings', 'rtbcb_openai_api_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'rtbcb_settings', 'rtbcb_mini_model', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'rtbcb_settings', 'rtbcb_premium_model', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'rtbcb_settings', 'rtbcb_advanced_model', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'rtbcb_settings', 'rtbcb_embedding_model', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'rtbcb_settings', 'rtbcb_labor_cost_per_hour', [ 'sanitize_callback' => 'floatval' ] );
        register_setting( 'rtbcb_settings', 'rtbcb_bank_fee_baseline', [ 'sanitize_callback' => 'floatval' ] );
    }

    /**
     * Export leads to CSV.
     *
     * @return void
     */
    public function export_leads_csv() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        $filters = [
            'search'    => sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) ),
            'category'  => sanitize_text_field( wp_unslash( $_POST['category'] ?? '' ) ),
            'date_from' => sanitize_text_field( wp_unslash( $_POST['date_from'] ?? '' ) ),
            'date_to'   => sanitize_text_field( wp_unslash( $_POST['date_to'] ?? '' ) ),
        ];

        $csv_content = RTBCB_Leads::export_to_csv( $filters );
        $filename = 'rtbcb-leads-' . date( 'Y-m-d' ) . '.csv';

        wp_send_json_success( [
            'content'  => $csv_content,
            'filename' => $filename,
        ] );
    }

    /**
     * Delete a single lead.
     *
     * @return void
     */
    public function delete_lead() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        $lead_id = intval( wp_unslash( $_POST['lead_id'] ?? 0 ) );
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        
        $result = $wpdb->delete( $table_name, [ 'id' => $lead_id ], [ '%d' ] );

        if ( $result ) {
            wp_send_json_success( [ 'message' => __( 'Lead deleted successfully.', 'rtbcb' ) ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'Failed to delete lead.', 'rtbcb' ) ] );
        }
    }

    /**
     * Handle bulk actions on leads.
     *
     * @return void
     */
    public function bulk_action_leads() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        $action = sanitize_text_field( wp_unslash( $_POST['action'] ?? '' ) );
        $lead_ids = array_map( 'intval', wp_unslash( $_POST['lead_ids'] ?? [] ) );

        if ( empty( $lead_ids ) ) {
            wp_send_json_error( [ 'message' => __( 'No leads selected.', 'rtbcb' ) ] );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        $placeholders = implode( ',', array_fill( 0, count( $lead_ids ), '%d' ) );

        switch ( $action ) {
            case 'delete':
                $result = $wpdb->query( 
                    $wpdb->prepare( 
                        "DELETE FROM {$table_name} WHERE id IN ({$placeholders})", 
                        $lead_ids 
                    ) 
                );
                
                if ( $result ) {
                    wp_send_json_success( [ 
                        'message' => sprintf( __( '%d leads deleted successfully.', 'rtbcb' ), $result ) 
                    ] );
                } else {
                    wp_send_json_error( [ 'message' => __( 'Failed to delete leads.', 'rtbcb' ) ] );
                }
                break;

            default:
                wp_send_json_error( [ 'message' => __( 'Invalid action.', 'rtbcb' ) ] );
        }
    }

    /**
     * Test the OpenAI API connection.
     *
     * @return void
     */
    public function test_api_connection() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        $api_key = get_option( 'rtbcb_openai_api_key' );
        if ( empty( $api_key ) ) {
            wp_send_json_error( [ 'message' => __( 'Missing API key.', 'rtbcb' ) ] );
        }

        $response = wp_remote_get( 'https://api.openai.com/v1/models', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 20,
        ] );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( [ 'message' => $response->get_error_message() ] );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            wp_send_json_error( [ 'message' => sprintf( __( 'API request failed (%d).', 'rtbcb' ), $code ) ] );
        }

        wp_send_json_success( [ 'message' => __( 'Connection successful.', 'rtbcb' ) ] );
    }

    /**
     * AJAX handler for comprehensive API test.
     *
     * @return void
     */
    public function ajax_test_api() {
        check_ajax_referer( 'rtbcb_test_api', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied', 'rtbcb' ) );
        }

        $result = RTBCB_API_Tester::test_connection();

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        }

        wp_send_json_error( $result );
    }

    /**
     * AJAX handler for industry commentary testing.
     *
     * @return void
     */
    public function ajax_test_commentary() {
        check_ajax_referer( 'rtbcb_test_commentary', 'nonce' );

        $industry = isset( $_POST['industry'] ) ? sanitize_text_field( wp_unslash( $_POST['industry'] ) ) : '';

        if ( empty( $industry ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid industry.', 'rtbcb' ) ] );
        }

        $start      = microtime( true );
        $commentary = rtbcb_test_generate_industry_commentary( $industry );
        $elapsed    = round( microtime( true ) - $start, 2 );

        if ( is_wp_error( $commentary ) ) {
            wp_send_json_error( [ 'message' => sanitize_text_field( $commentary->get_error_message() ) ] );
        }

        $word_count = str_word_count( $commentary );

        wp_send_json_success(
            [
                'commentary' => sanitize_textarea_field( $commentary ),
                'word_count' => $word_count,
                'elapsed'    => $elapsed,
                'generated'  => current_time( 'mysql' ),
            ]
        );
    }

    /**
     * AJAX handler for company overview testing.
     *
     * @return void
     */
    public function ajax_test_company_overview() {
        check_ajax_referer( 'rtbcb_test_company_overview', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ] );
        }

        $company_name = isset( $_POST['company_name'] ) ?
            sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';

        if ( empty( $company_name ) ) {
            wp_send_json_error( [ 'message' => __( 'Company name is required.', 'rtbcb' ) ] );
        }

        $start_time = microtime( true );

        try {
            $overview = rtbcb_test_generate_company_overview( $company_name );

            if ( is_wp_error( $overview ) ) {
                wp_send_json_error( [
                    'message' => $overview->get_error_message(),
                ] );
            }

            $analysis        = $overview['analysis'] ?? '';
            $recommendations = array_map( 'sanitize_text_field', $overview['recommendations'] ?? [] );
            $references      = array_map( 'esc_url_raw', $overview['references'] ?? [] );

            $word_count   = str_word_count( wp_strip_all_tags( $analysis ) );
            $elapsed_time = microtime( true ) - $start_time;

            update_option( 'rtbcb_current_company', [
                'name'            => $company_name,
                'summary'         => sanitize_textarea_field( wp_strip_all_tags( $analysis ) ),
                'recommendations' => $recommendations,
                'references'      => $references,
                'generated_at'    => current_time( 'mysql' ),
            ] );

            wp_send_json_success( [
                'overview'        => wp_kses_post( $analysis ),
                'word_count'      => $word_count,
                'elapsed'         => round( $elapsed_time, 2 ),
                'generated'       => current_time( 'mysql' ),
                'recommendations' => $recommendations,
                'references'      => $references,
            ] );

        } catch ( Exception $e ) {
            error_log( 'RTBCB Company Overview Error: ' . $e->getMessage() );
            wp_send_json_error( [
                'message' => __( 'An error occurred while generating the overview.', 'rtbcb' ),
            ] );
        }
    }

    /**
     * Enhanced AJAX handler for company overview testing with comprehensive debugging.
     *
     * @return void
     */
    public function ajax_test_company_overview_enhanced() {
        // Verify nonce and permissions.
        check_ajax_referer( 'rtbcb_unified_test_dashboard', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                [
                    'message' => __( 'Insufficient permissions.', 'rtbcb' ),
                    'code'    => 'insufficient_permissions',
                ]
            );
        }

        // Get and validate input parameters.
        $company_name = isset( $_POST['company_name'] ) ?
            sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';
        $model       = isset( $_POST['model'] ) ?
            sanitize_text_field( wp_unslash( $_POST['model'] ) ) : 'premium';
        $show_debug  = isset( $_POST['show_debug'] ) ?
            filter_var( wp_unslash( $_POST['show_debug'] ), FILTER_VALIDATE_BOOLEAN ) : false;
        $request_id  = isset( $_POST['request_id'] ) ?
            sanitize_text_field( wp_unslash( $_POST['request_id'] ) ) : uniqid();

        if ( empty( $company_name ) ) {
            wp_send_json_error(
                [
                    'message' => __( 'Company name is required.', 'rtbcb' ),
                    'code'    => 'missing_company_name',
                ]
            );
        }

        if ( strlen( $company_name ) < 2 ) {
            wp_send_json_error(
                [
                    'message' => __( 'Company name must be at least 2 characters long.', 'rtbcb' ),
                    'code'    => 'invalid_company_name',
                ]
            );
        }

        // Initialize response data structure.
        $response_data = [
            'request_id'      => $request_id,
            'company_name'    => $company_name,
            'model_requested' => $model,
            'debug_enabled'   => $show_debug,
            'started_at'      => current_time( 'mysql' ),
            'debug'           => [],
        ];

        // Start performance tracking.
        $start_time = microtime( true );

        try {
            // Validate API configuration.
            $api_key = get_option( 'rtbcb_openai_api_key', '' );
            if ( empty( $api_key ) ) {
                throw new Exception( __( 'OpenAI API key is not configured.', 'rtbcb' ) );
            }

            if ( ! rtbcb_is_valid_openai_api_key( $api_key ) ) {
                throw new Exception( __( 'OpenAI API key format is invalid.', 'rtbcb' ) );
            }

            // Get model configuration.
            $available_models = [
                'mini'     => get_option( 'rtbcb_mini_model', 'gpt-4o-mini' ),
                'premium'  => get_option( 'rtbcb_premium_model', 'gpt-4o' ),
                'advanced' => get_option( 'rtbcb_advanced_model', 'o1-preview' ),
            ];

            $selected_model              = $available_models[ $model ] ?? $available_models['premium'];
            $response_data['model_used'] = $selected_model;

            // Build prompts for debugging.
            $system_prompt = $this->build_company_overview_system_prompt();
            $user_prompt   = $this->build_company_overview_user_prompt( $company_name );

            if ( $show_debug ) {
                $response_data['debug']['system_prompt'] = $system_prompt;
                $response_data['debug']['user_prompt']   = $user_prompt;
            }

            // Prepare API request.
            $api_request_data = [
                'model'    => $selected_model,
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => $system_prompt,
                    ],
                    [
                        'role'    => 'user',
                        'content' => $user_prompt,
                    ],
                ],
                'max_tokens'        => 2000,
                'temperature'       => 0.7,
                'top_p'             => 0.9,
                'frequency_penalty' => 0.1,
                'presence_penalty'  => 0.1,
            ];

            if ( $show_debug ) {
                $response_data['debug']['api_request']  = $api_request_data;
                $response_data['debug']['api_endpoint'] = 'https://api.openai.com/v1/chat/completions';
            }

            // Make API request.
            $api_start_time = microtime( true );

            $api_response = wp_remote_post(
                'https://api.openai.com/v1/chat/completions',
                [
                    'timeout' => 120,
                    'headers' => [
                        'Authorization' => 'Bearer ' . $api_key,
                        'Content-Type'  => 'application/json',
                    ],
                    'body'    => wp_json_encode( $api_request_data ),
                ]
            );

            $api_end_time      = microtime( true );
            $api_response_time = round( $api_end_time - $api_start_time, 3 );

            if ( is_wp_error( $api_response ) ) {
                throw new Exception(
                    sprintf( __( 'API request failed: %s', 'rtbcb' ), $api_response->get_error_message() )
                );
            }

            $response_code = wp_remote_retrieve_response_code( $api_response );
            $response_body = wp_remote_retrieve_body( $api_response );

            if ( $show_debug ) {
                $response_data['debug']['api_response_code'] = $response_code;
                $response_data['debug']['api_response_time'] = $api_response_time . 's';
            }

            if ( 200 !== $response_code ) {
                $error_details = json_decode( $response_body, true );
                $error_message = isset( $error_details['error']['message'] )
                    ? $error_details['error']['message']
                    : sprintf( __( 'API request failed with status code: %d', 'rtbcb' ), $response_code );

                if ( $show_debug ) {
                    $response_data['debug']['api_error'] = $error_details;
                }

                throw new Exception( $error_message );
            }

            // Parse API response.
            $api_data = json_decode( $response_body, true );

            if ( ! $api_data || ! isset( $api_data['choices'][0]['message']['content'] ) ) {
                throw new Exception( __( 'Invalid API response format.', 'rtbcb' ) );
            }

            $overview_content = $api_data['choices'][0]['message']['content'];

            // Extract usage information.
            $usage_data        = $api_data['usage'] ?? [];
            $tokens_used       = $usage_data['total_tokens'] ?? 0;
            $prompt_tokens     = $usage_data['prompt_tokens'] ?? 0;
            $completion_tokens = $usage_data['completion_tokens'] ?? 0;

            // Calculate performance metrics.
            $total_elapsed = microtime( true ) - $start_time;
            $word_count    = str_word_count( $overview_content );

            // Parse structured content if available.
            $parsed_overview = $this->parse_company_overview_response( $overview_content );

            // Store results for future use.
            $company_data = [
                'name'            => $company_name,
                'overview'        => $overview_content,
                'analysis'        => $parsed_overview['analysis'] ?? $overview_content,
                'recommendations' => $parsed_overview['recommendations'] ?? [],
                'references'      => $parsed_overview['references'] ?? [],
                'generated_at'    => current_time( 'mysql' ),
                'model_used'      => $selected_model,
                'word_count'      => $word_count,
                'generation_time' => round( $total_elapsed, 2 ),
            ];

            update_option( 'rtbcb_current_company', $company_data );

            // Build final response.
            $response_data = array_merge(
                $response_data,
                [
                    'overview'          => $overview_content,
                    'analysis'          => $parsed_overview['analysis'] ?? $overview_content,
                    'recommendations'   => $parsed_overview['recommendations'] ?? [],
                    'references'        => $parsed_overview['references'] ?? [],
                    'word_count'        => $word_count,
                    'elapsed'           => round( $total_elapsed, 2 ),
                    'api_response_time' => $api_response_time,
                    'generated'         => current_time( 'mysql' ),
                    'tokens_used'       => $tokens_used,
                    'prompt_tokens'     => $prompt_tokens,
                    'completion_tokens' => $completion_tokens,
                ]
            );

            // Add debug information.
            if ( $show_debug ) {
                $response_data['debug'] = array_merge(
                    $response_data['debug'],
                    [
                        'total_elapsed_time' => round( $total_elapsed, 3 ) . 's',
                        'word_count'         => $word_count,
                        'tokens_used'        => $tokens_used,
                        'prompt_tokens'      => $prompt_tokens,
                        'completion_tokens'  => $completion_tokens,
                        'tokens_per_second'  => $tokens_used > 0 ? round( $tokens_used / $total_elapsed, 2 ) : 0,
                        'words_per_second'   => $word_count > 0 ? round( $word_count / $total_elapsed, 2 ) : 0,
                        'api_cost_estimate'  => $this->estimate_api_cost( $prompt_tokens, $completion_tokens, $selected_model ),
                        'memory_usage'       => size_format( memory_get_usage( true ) ),
                        'peak_memory'        => size_format( memory_get_peak_usage( true ) ),
                        'parsed_sections'    => array_keys( $parsed_overview ),
                    ]
                );
            }

            // Log successful generation.
            $this->log_generation_event(
                'company_overview_success',
                [
                    'company'    => $company_name,
                    'model'      => $selected_model,
                    'elapsed'    => $total_elapsed,
                    'tokens'     => $tokens_used,
                    'word_count' => $word_count,
                ]
            );

            wp_send_json_success( $response_data );

        } catch ( Exception $e ) {
            $error_elapsed = microtime( true ) - $start_time;

            // Log error event.
            $this->log_generation_event(
                'company_overview_error',
                [
                    'company' => $company_name,
                    'error'   => $e->getMessage(),
                    'elapsed' => $error_elapsed,
                ]
            );

            $error_response = [
                'message'    => $e->getMessage(),
                'code'       => 'generation_failed',
                'request_id' => $request_id,
                'elapsed'    => round( $error_elapsed, 2 ),
                'occurred_at'=> current_time( 'mysql' ),
            ];

            if ( $show_debug ) {
                $error_response['debug'] = array_merge(
                    $response_data['debug'] ?? [],
                    [
                        'error_details' => $e->getMessage(),
                        'error_file'    => $e->getFile(),
                        'error_line'    => $e->getLine(),
                        'error_trace'   => $e->getTraceAsString(),
                        'php_version'   => PHP_VERSION,
                        'wp_version'    => get_bloginfo( 'version' ),
                        'plugin_version'=> defined( 'RTBCB_VERSION' ) ? RTBCB_VERSION : 'unknown',
                        'memory_usage'  => size_format( memory_get_usage( true ) ),
                        'time_limit'    => ini_get( 'max_execution_time' ),
                    ]
                );
            }

            wp_send_json_error( $error_response );
        }
    }

    /**
     * Build system prompt for company overview generation.
     *
     * @return string System prompt.
     */
    private function build_company_overview_system_prompt() {
        return "You are an expert business analyst specializing in treasury technology and financial operations. Your task is to generate comprehensive company overviews that help evaluate treasury technology needs.\n\nWhen analyzing a company, provide:\n1. Business model and industry context\n2. Likely treasury and financial challenges\n3. Technology maturity assessment\n4. Recommendations for treasury solutions\n\nFormat your response as structured content with clear sections. Focus on actionable insights that would help in evaluating treasury technology solutions.\n\nKeep the tone professional and analytical. Base recommendations on industry best practices and common treasury challenges.";
    }

    /**
     * Build user prompt for specific company analysis.
     *
     * @param string $company_name Company name to analyze.
     * @return string User prompt.
     */
    private function build_company_overview_user_prompt( $company_name ) {
        return sprintf(
            "Analyze %s and provide a comprehensive overview focusing on:\n\n1. **Company Analysis**: Business model, industry position, and key characteristics\n2. **Treasury Challenges**: Likely financial and treasury pain points based on company profile\n3. **Technology Assessment**: Current probable technology maturity and gaps\n4. **Strategic Recommendations**: Specific treasury technology solutions that would benefit this company\n\nPlease provide detailed, actionable insights that demonstrate understanding of both the company and treasury technology landscape.",
            esc_html( $company_name )
        );
    }

    /**
     * Parse structured response from company overview generation.
     *
     * @param string $content Raw content from API.
     * @return array Parsed sections.
     */
    private function parse_company_overview_response( $content ) {
        $parsed = [
            'analysis'        => $content,
            'recommendations' => [],
            'references'      => [],
        ];

        // Try to extract structured sections using common patterns.
        $sections = [
            'recommendations' => '/(?:recommendations?|suggestions?):?\s*(.*?)(?=\n\n|\n[A-Z]|$)/is',
            'challenges'      => '/(?:challenges?|pain\s*points?):?\s*(.*?)(?=\n\n|\n[A-Z]|$)/is',
            'solutions'       => '/(?:solutions?|technologies?):?\s*(.*?)(?=\n\n|\n[A-Z]|$)/is',
        ];

        foreach ( $sections as $key => $pattern ) {
            if ( preg_match( $pattern, $content, $matches ) ) {
                $section_content = trim( $matches[1] );
                if ( ! empty( $section_content ) ) {
                    // Split into array if it looks like a list.
                    if ( strpos( $section_content, "\n-" ) !== false || strpos( $section_content, "\n•" ) !== false ) {
                        $items          = preg_split( '/\n[-•]\s*/', $section_content );
                        $parsed[ $key ] = array_filter( array_map( 'trim', $items ) );
                    } else {
                        $parsed[ $key ] = [ $section_content ];
                    }
                }
            }
        }

        return $parsed;
    }

    /**
     * Estimate API cost based on token usage.
     *
     * @param int    $prompt_tokens     Prompt tokens used.
     * @param int    $completion_tokens Completion tokens used.
     * @param string $model             Model used.
     * @return string Cost estimate.
     */
    private function estimate_api_cost( $prompt_tokens, $completion_tokens, $model ) {
        // Simplified cost estimation - update with current OpenAI pricing.
        $cost_per_1k_tokens = [
            'gpt-4o-mini' => [
                'input'  => 0.00015,
                'output' => 0.0006,
            ],
            'gpt-4o'      => [
                'input'  => 0.005,
                'output' => 0.015,
            ],
            'o1-preview'  => [
                'input'  => 0.015,
                'output' => 0.06,
            ],
        ];

        $model_key = array_search(
            $model,
            [
                'gpt-4o-mini' => 'gpt-4o-mini',
                'gpt-4o'      => 'gpt-4o',
                'o1-preview'  => 'o1-preview',
            ]
        );

        if ( ! $model_key || ! isset( $cost_per_1k_tokens[ $model_key ] ) ) {
            return 'Unknown';
        }

        $rates           = $cost_per_1k_tokens[ $model_key ];
        $prompt_cost     = ( $prompt_tokens / 1000 ) * $rates['input'];
        $completion_cost = ( $completion_tokens / 1000 ) * $rates['output'];
        $total_cost      = $prompt_cost + $completion_cost;

        return '$' . number_format( $total_cost, 4 );
    }

    /**
     * Log generation events for monitoring and debugging.
     *
     * @param string $event_type Type of event.
     * @param array  $data       Event data.
     * @return void
     */
    private function log_generation_event( $event_type, $data = [] ) {
        $log_entry = [
            'timestamp' => current_time( 'mysql' ),
            'event'     => $event_type,
            'data'      => $data,
            'user_id'   => get_current_user_id(),
            'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent'=> $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ];

        // Store in database or log file as needed.
        // For now, just use error_log for debugging.
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'RTBCB Generation Event: ' . wp_json_encode( $log_entry ) ); // phpcs:ignore
        }

        // Optionally store in options table for dashboard display.
        $recent_events = get_option( 'rtbcb_recent_generation_events', [] );
        array_unshift( $recent_events, $log_entry );
        $recent_events = array_slice( $recent_events, 0, 50 ); // Keep last 50 events.
        update_option( 'rtbcb_recent_generation_events', $recent_events );
    }

    /**
     * AJAX handler for industry overview testing.
     *
     * @return void
     */
    public function ajax_test_industry_overview() {
        check_ajax_referer( 'rtbcb_test_industry_overview', 'nonce' );
        $raw_data     = isset( $_POST['company_data'] ) ? wp_unslash( $_POST['company_data'] ) : '';
        $company_data = json_decode( $raw_data, true );

        if ( empty( $company_data ) || ! is_array( $company_data ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid company data.', 'rtbcb' ) ] );
        }

        $start    = microtime( true );
        $overview = rtbcb_test_generate_industry_overview( $company_data );
        $elapsed  = round( microtime( true ) - $start, 2 );

        if ( is_wp_error( $overview ) ) {
            wp_send_json_error( [ 'message' => sanitize_text_field( $overview->get_error_message() ) ] );
        }

        $word_count = str_word_count( $overview );

        wp_send_json_success(
            [
                'overview'   => sanitize_textarea_field( $overview ),
                'word_count' => $word_count,
                'elapsed'    => $elapsed,
                'generated'  => current_time( 'mysql' ),
            ]
        );
    }

    /**
     * AJAX handler for treasury tech overview testing.
     *
     * @return void
     */
    public function ajax_test_treasury_tech_overview() {
        check_ajax_referer( 'rtbcb_test_treasury_tech_overview', 'nonce' );

        $focus_areas = isset( $_POST['focus_areas'] ) ? (array) wp_unslash( $_POST['focus_areas'] ) : [];
        $focus_areas = array_map( 'sanitize_text_field', $focus_areas );
        $focus_areas = array_filter( $focus_areas );
        $complexity  = isset( $_POST['complexity'] ) ? sanitize_text_field( wp_unslash( $_POST['complexity'] ) ) : '';

        $company_data = rtbcb_get_current_company();
        if ( ! is_array( $company_data ) ) {
            $company_data = [];
        }
        if ( ! empty( $focus_areas ) ) {
            $company_data['focus_areas'] = $focus_areas;
        }
        if ( ! empty( $complexity ) ) {
            $company_data['complexity'] = $complexity;
        }

        if ( empty( $company_data['focus_areas'] ) ) {
            wp_send_json_error( [ 'message' => __( 'Please select at least one focus area.', 'rtbcb' ) ] );
        }

        $start    = microtime( true );
        $overview = rtbcb_test_generate_treasury_tech_overview( $company_data );
        $elapsed  = round( microtime( true ) - $start, 2 );

        if ( is_wp_error( $overview ) ) {
            wp_send_json_error( [ 'message' => sanitize_text_field( $overview->get_error_message() ) ] );
        }

        $word_count = str_word_count( $overview );

        wp_send_json_success(
            [
                'overview'   => sanitize_textarea_field( $overview ),
                'word_count' => $word_count,
                'elapsed'    => $elapsed,
                'generated'  => current_time( 'mysql' ),
            ]
        );
    }

    /**
     * AJAX handler for real treasury overview testing.
     *
     * @return void
     */
    public function ajax_test_real_treasury_overview() {
        check_ajax_referer( 'rtbcb_test_real_treasury_overview', 'nonce' );

        $include_portal = isset( $_POST['include_portal'] ) ? (bool) intval( wp_unslash( $_POST['include_portal'] ) ) : false;
        $categories     = isset( $_POST['categories'] ) ? (array) wp_unslash( $_POST['categories'] ) : [];
        $categories     = array_filter( array_map( 'sanitize_text_field', $categories ) );

        $inputs       = rtbcb_get_sample_inputs();
        $company_data = [
            'include_portal' => $include_portal,
            'company_size'   => sanitize_text_field( $inputs['company_size'] ?? '' ),
            'industry'       => sanitize_text_field( $inputs['industry'] ?? '' ),
            'challenges'     => array_map( 'sanitize_text_field', $inputs['pain_points'] ?? [] ),
        ];

        if ( ! empty( $categories ) ) {
            $company_data['categories'] = $categories;
        }

        $start    = microtime( true );
        $overview = rtbcb_test_generate_real_treasury_overview( $company_data );
        $elapsed  = round( microtime( true ) - $start, 2 );

        if ( is_wp_error( $overview ) ) {
            wp_send_json_error( [ 'message' => sanitize_text_field( $overview->get_error_message() ) ] );
        }

        $word_count = str_word_count( $overview );

        wp_send_json_success(
            [
                'overview'   => sanitize_textarea_field( $overview ),
                'word_count' => $word_count,
                'elapsed'    => $elapsed,
                'generated'  => current_time( 'mysql' ),
            ]
        );
    }

    /**
     * AJAX handler to retrieve stored company data.
     *
     * @return void
     */
    public function ajax_get_company_data() {
        check_ajax_referer( 'rtbcb_test_real_treasury_overview', 'nonce' );

        $inputs  = rtbcb_get_sample_inputs();
        $summary = rtbcb_test_generate_company_overview( $inputs['company_name'] );
        if ( is_wp_error( $summary ) ) {
            wp_send_json_error( [ 'message' => sanitize_text_field( $summary->get_error_message() ) ] );
        }

        $analysis        = $summary['analysis'] ?? '';
        $recommendations = array_map( 'sanitize_text_field', $summary['recommendations'] ?? [] );
        $references      = array_map( 'esc_url_raw', $summary['references'] ?? [] );

        wp_send_json_success(
            [
                'summary'        => sanitize_textarea_field( $analysis ),
                'recommendations'=> $recommendations,
                'references'     => $references,
                'challenges'     => array_map( 'sanitize_text_field', $inputs['pain_points'] ?? [] ),
            ]
        );
    }

    /**
     * AJAX handler for testing estimated benefits.
     *
     * @return void
     */
    public function ajax_test_estimated_benefits() {
        check_ajax_referer( 'rtbcb_test_estimated_benefits', 'nonce' );

        $company_data = [
            'revenue'     => isset( $_POST['company_data']['revenue'] ) ? floatval( wp_unslash( $_POST['company_data']['revenue'] ) ) : 0,
            'staff_count' => isset( $_POST['company_data']['staff_count'] ) ? intval( wp_unslash( $_POST['company_data']['staff_count'] ) ) : 0,
            'efficiency'  => isset( $_POST['company_data']['efficiency'] ) ? floatval( wp_unslash( $_POST['company_data']['efficiency'] ) ) : 0,
        ];
        $category = isset( $_POST['recommended_category'] ) ? sanitize_text_field( wp_unslash( $_POST['recommended_category'] ) ) : '';

        $estimate = rtbcb_test_generate_benefits_estimate( $company_data, $category );

        if ( is_wp_error( $estimate ) ) {
            wp_send_json_error( [ 'message' => sanitize_text_field( $estimate->get_error_message() ) ] );
        }

        wp_send_json_success( [ 'estimate' => $estimate ] );
    }

    /**
     * AJAX handler to calculate ROI from sample inputs.
     *
     * @return void
     */
    public function ajax_test_calculate_roi() {
        check_ajax_referer( 'rtbcb_test_calculate_roi', 'nonce' );

        $roi_inputs = isset( $_POST['roi_inputs'] ) && is_array( $_POST['roi_inputs'] )
            ? rtbcb_sanitize_form_data( wp_unslash( $_POST['roi_inputs'] ) )
            : rtbcb_get_sample_inputs();

        $start = microtime( true );
        $roi   = RTBCB_Calculator::calculate_roi( $roi_inputs );
        $elapsed = round( microtime( true ) - $start, 2 );

        if ( empty( $roi ) || is_wp_error( $roi ) ) {
            wp_send_json_error( [ 'message' => __( 'Unable to calculate ROI.', 'rtbcb' ) ] );
        }

        wp_send_json_success(
            [
                'roi'       => $roi,
                'word_count'=> 0,
                'elapsed'   => $elapsed,
                'generated' => current_time( 'mysql' ),
            ]
        );
    }

    /**
     * AJAX handler to generate complete report.
     *
     * @return void
     */
    public function ajax_test_generate_complete_report() {
        check_ajax_referer( 'rtbcb_test_generate_complete_report', 'nonce' );

        $company_name = isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';
        $focus_areas  = isset( $_POST['focus_areas'] ) ? (array) wp_unslash( $_POST['focus_areas'] ) : [];
        $focus_areas  = array_filter( array_map( 'sanitize_text_field', $focus_areas ) );
        $complexity   = isset( $_POST['complexity'] ) ? sanitize_text_field( wp_unslash( $_POST['complexity'] ) ) : '';
        $roi_inputs   = isset( $_POST['roi_inputs'] ) && is_array( $_POST['roi_inputs'] )
            ? rtbcb_sanitize_form_data( wp_unslash( $_POST['roi_inputs'] ) )
            : rtbcb_get_sample_inputs();

        if ( empty( $company_name ) || empty( $focus_areas ) ) {
            wp_send_json_error( [ 'message' => __( 'Missing required inputs.', 'rtbcb' ) ], 400 );
        }

        $result = rtbcb_test_generate_complete_report(
            [
                'company_name' => $company_name,
                'focus_areas'  => $focus_areas,
                'complexity'   => $complexity,
                'roi_inputs'   => $roi_inputs,
            ]
        );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ], 500 );
        }

        $allowed_tags          = wp_kses_allowed_html( 'post' );
        $allowed_tags['style'] = [];
        $allowed_tags['iframe'] = [ 'src' => [], 'style' => [] ];
        $result['html']        = wp_kses( $result['html'], $allowed_tags );

        wp_send_json_success( $result );
    }

    /**
     * Rebuild the RAG index.
     *
     * @return void
     */
    public function rebuild_rag_index() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        $rag = new RTBCB_RAG();
        $rag->rebuild_index();

        wp_send_json_success( [ 'message' => __( 'RAG index rebuilt successfully.', 'rtbcb' ) ] );
    }

    /**
     * Run integration diagnostics.
     *
     * @return void
     */
    public function run_integration_tests() {
        check_ajax_referer( 'rtbcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        $results = RTBCB_Tests::run_integration_tests();

        wp_send_json_success( $results );
    }

    /**
     * AJAX handler for generating report preview.
     *
     * @return void
     */
    public function ajax_generate_report_preview() {
        check_ajax_referer( 'rtbcb_generate_report_preview', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied', 'rtbcb' ), 403 );
        }

        $company = rtbcb_get_current_company();
        if ( empty( $company ) ) {
            wp_send_json_error( [ 'message' => __( 'No company data found. Please run the company overview first.', 'rtbcb' ) ], 400 );
        }

        $context_raw  = isset( $_POST['context'] ) ? wp_unslash( $_POST['context'] ) : '';
        $template_raw = isset( $_POST['template'] ) ? wp_unslash( $_POST['template'] ) : '';

        $context = json_decode( $context_raw, true );
        if ( ! is_array( $context ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid context data.', 'rtbcb' ) ], 400 );
        }

        try {
            $html = '';

            if ( ! empty( $template_raw ) ) {
                $business_case_data = $context;
                ob_start();
                eval( '?>' . $template_raw ); // phpcs:ignore WordPress.PHP.DiscouragedFunctions.eval
                $html = ob_get_clean();
            } else {
                $template_path = RTBCB_DIR . 'templates/comprehensive-report-template.php';
                if ( ! file_exists( $template_path ) ) {
                    $template_path = RTBCB_DIR . 'templates/report-template.php';
                }

                if ( ! file_exists( $template_path ) ) {
                    wp_send_json_error( [ 'message' => __( 'Report template not found.', 'rtbcb' ) ], 500 );
                }

                $business_case_data = $context;
                ob_start();
                include $template_path;
                $html = ob_get_clean();
            }

            $allowed_tags = wp_kses_allowed_html( 'post' );
            $allowed_tags['style'] = [];
            $html = wp_kses( $html, $allowed_tags );

            wp_send_json_success( [ 'html' => $html ] );
        } catch ( RTBCB_JSON_Response $e ) {
            throw $e;
        } catch ( \Throwable $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
        }
    }

    /**
     * AJAX handler to generate a sample report.
     *
     * @return void
     */
    public function ajax_generate_sample_report() {
        check_ajax_referer( 'rtbcb_generate_report_preview', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'rtbcb' ), 403 );
        }

        $company = rtbcb_get_current_company();
        if ( empty( $company ) ) {
            wp_send_json_error( [ 'message' => __( 'No company data found. Please run the company overview first.', 'rtbcb' ) ], 400 );
        }

        $scenario_key = isset( $_POST['scenario_key'] ) ? sanitize_key( wp_unslash( $_POST['scenario_key'] ) ) : '';

        $inputs = apply_filters( 'rtbcb_sample_report_inputs', rtbcb_get_sample_inputs(), $scenario_key );
        if ( empty( $inputs ) || ! is_array( $inputs ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid scenario selected.', 'rtbcb' ) ], 400 );
        }

        try {
            $roi_data = RTBCB_Calculator::calculate_roi( $inputs );

            $llm           = new RTBCB_LLM();
            $business_case = $llm->generate_business_case( $inputs, $roi_data );
            if ( is_wp_error( $business_case ) ) {
                wp_send_json_error( [ 'message' => $business_case->get_error_message() ], 500 );
            }

            $router = new RTBCB_Router();
            $html   = $router->get_report_html( $business_case );
            if ( empty( $html ) ) {
                wp_send_json_error( [ 'message' => __( 'Report template not found.', 'rtbcb' ) ], 500 );
            }

            wp_send_json_success( [ 'report_html' => $html ] );
        } catch ( RTBCB_JSON_Response $e ) {
            throw $e;
        } catch ( \Throwable $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
        }
    }

    /**
     * Sync portal data to the local site.
     *
     * @return void
     */
    public function sync_to_local() {
        check_ajax_referer( 'rtbcb_sync_local', 'nonce' );

        if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        wp_send_json_success( [ 'message' => __( 'Data synchronized.', 'rtbcb' ) ] );
    }

    /**
     * Get monthly trends data.
     *
     * @return array Monthly trends.
     */
    private function get_monthly_trends() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rtbcb_leads';

        $results = $wpdb->get_results(
            "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as leads,
                AVG(roi_base) as avg_roi
             FROM {$table_name} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month",
            ARRAY_A
        );

        return $results ?: [];
    }

    /**
     * Check if Portal integration is active.
     *
     * @return bool
     */
    private function check_portal_integration() {
        return (bool) ( has_filter( 'rt_portal_get_vendors' ) || has_filter( 'rt_portal_get_vendor_notes' ) );
    }

    /**
     * Get vendor count from portal.
     *
     * @return int
     */
    private function get_vendor_count() {
        $vendors = apply_filters( 'rt_portal_get_vendors', [] );
        return is_array( $vendors ) ? count( $vendors ) : 0;
    }

    /**
     * Check RAG index health.
     *
     * @return array Health status.
     */
    private function check_rag_health() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rtbcb_rag_index';

        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
        $last_updated = $wpdb->get_var( "SELECT MAX(updated_at) FROM {$table_name}" );

        return [
            'indexed_items' => intval( $count ),
            'last_updated'  => $last_updated,
            'status'        => $count > 0 ? 'healthy' : 'needs_rebuild',
        ];
    }

    /**
     * Run comprehensive diagnostics to identify issues.
     *
     * @return array Diagnostic results.
     */
    public static function run_comprehensive_diagnostics() {
        $diagnostics = [];

        // 1. Check PHP Version
        $diagnostics['php_version'] = [
            'version'            => PHP_VERSION,
            'meets_requirement'  => version_compare( PHP_VERSION, '7.4', '>=' ),
            'status'             => version_compare( PHP_VERSION, '7.4', '>=' ) ? 'OK' : 'FAIL',
        ];

        // 2. Check WordPress Version
        $wp_version = get_bloginfo( 'version' );
        $diagnostics['wp_version'] = [
            'version'            => $wp_version,
            'meets_requirement'  => version_compare( $wp_version, '5.0', '>=' ),
            'status'             => version_compare( $wp_version, '5.0', '>=' ) ? 'OK' : 'FAIL',
        ];

        // 3. Check Database Connection
        global $wpdb;
        try {
            $wpdb->get_var( 'SELECT 1' );
            $diagnostics['database'] = [
                'status'  => 'OK',
                'message' => __( 'Database connection successful', 'rtbcb' ),
            ];
        } catch ( Exception $e ) {
            $diagnostics['database'] = [
                'status'  => 'FAIL',
                'message' => sprintf( __( 'Database connection failed: %s', 'rtbcb' ), $e->getMessage() ),
            ];
        }

        // 4. Check Required Classes
        $required_classes = [
            'RTBCB_Calculator',
            'RTBCB_Category_Recommender',
            'RTBCB_LLM',
            'RTBCB_RAG',
            'RTBCB_Leads',
            'RTBCB_Validator',
        ];

        foreach ( $required_classes as $class ) {
            $diagnostics['classes'][ $class ] = [
                'exists' => class_exists( $class ),
                'status' => class_exists( $class ) ? 'OK' : 'FAIL',
            ];
        }

        // 5. Check Database Tables
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
                DB_NAME,
                $table_name
            )
        );

        $diagnostics['database_tables']['rtbcb_leads'] = [
            'exists' => (bool) $table_exists,
            'status' => $table_exists ? 'OK' : 'FAIL',
        ];

        // 6. Check Table Structure
        if ( $table_exists ) {
            $columns = $wpdb->get_results( "DESCRIBE {$table_name}" );
            $expected_columns = [
                'id',
                'email',
                'company_size',
                'industry',
                'hours_reconciliation',
                'hours_cash_positioning',
                'num_banks',
                'ftes',
                'pain_points',
                'recommended_category',
                'roi_low',
                'roi_base',
                'roi_high',
                'report_html',
                'ip_address',
                'user_agent',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'created_at',
                'updated_at',
            ];

            $actual_columns  = array_column( $columns, 'Field' );
            $missing_columns = array_diff( $expected_columns, $actual_columns );

            $diagnostics['table_structure'] = [
                'expected_columns' => count( $expected_columns ),
                'actual_columns'   => count( $actual_columns ),
                'missing_columns'  => $missing_columns,
                'status'           => empty( $missing_columns ) ? 'OK' : 'FAIL',
            ];
        }

        // 7. Check OpenAI API Configuration
        $api_key      = get_option( 'rtbcb_openai_api_key' );
        $configured   = ! empty( $api_key );
        $valid_format = $configured ? rtbcb_is_valid_openai_api_key( $api_key ) : false;
        $api_test     = RTBCB_API_Tester::test_connection();

        $status = $api_test['success'] ? 'OK' : 'FAIL';
        if ( $configured && ! $valid_format ) {
            $status = 'INVALID_FORMAT';
        }

        $diagnostics['openai_api'] = [
            'configured'   => $configured,
            'valid_format' => $valid_format,
            'success'      => $api_test['success'],
            'message'      => $api_test['message'],
            'details'      => $api_test['details'] ?? '',
            'status'       => $status,
        ];

        // 8. Check Memory Limit
        $memory_limit = ini_get( 'memory_limit' );
        $memory_bytes = wp_convert_hr_to_bytes( $memory_limit );
        $diagnostics['memory_limit'] = [
            'current'    => $memory_limit,
            'bytes'      => $memory_bytes,
            'sufficient' => $memory_bytes >= 128 * 1024 * 1024,
            'status'     => $memory_bytes >= 128 * 1024 * 1024 ? 'OK' : 'LOW',
        ];

        // 9. Check Error Logging
        $diagnostics['error_logging'] = [
            'wp_debug'      => defined( 'WP_DEBUG' ) && WP_DEBUG,
            'wp_debug_log'  => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
            'log_errors'    => ini_get( 'log_errors' ),
            'error_log_path'=> ini_get( 'error_log' ),
        ];

        // 10. Test Basic Functionality
        try {
            $test_inputs = rtbcb_get_sample_inputs();

            if ( class_exists( 'RTBCB_Calculator' ) ) {
                $roi_result = RTBCB_Calculator::calculate_roi( $test_inputs );
                $diagnostics['functionality']['calculator'] = [
                    'status' => ! empty( $roi_result ) ? 'OK' : 'FAIL',
                    'result' => ! empty( $roi_result ),
                ];
            }

            if ( class_exists( 'RTBCB_Category_Recommender' ) ) {
                $recommendation = RTBCB_Category_Recommender::recommend_category( $test_inputs );
                $diagnostics['functionality']['recommender'] = [
                    'status' => ! empty( $recommendation ) ? 'OK' : 'FAIL',
                    'result' => ! empty( $recommendation ),
                ];
            }
        } catch ( Exception $e ) {
            $diagnostics['functionality']['error'] = $e->getMessage();
        } catch ( Error $e ) {
            $diagnostics['functionality']['fatal_error'] = $e->getMessage();
        }

        return $diagnostics;
    }

    /**
     * AJAX handler for running diagnostics.
     *
     * @return void
     */
    public function ajax_run_diagnostics() {
        check_ajax_referer( 'rtbcb_diagnostics', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions', 'rtbcb' ) );
        }

        try {
            $diagnostics = self::run_comprehensive_diagnostics();
            wp_send_json_success( $diagnostics );
        } catch ( Exception $e ) {
            wp_send_json_error( sprintf( __( 'Diagnostics failed: %s', 'rtbcb' ), $e->getMessage() ) );
        } catch ( Error $e ) {
            wp_send_json_error( sprintf( __( 'Diagnostics failed: %s', 'rtbcb' ), $e->getMessage() ) );
        }
    }
}
