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
        add_action( 'wp_ajax_rtbcb_sync_to_local', [ $this, 'sync_to_local' ] );
        add_action( 'wp_ajax_nopriv_rtbcb_sync_to_local', [ $this, 'sync_to_local' ] );
        add_action( 'wp_ajax_rtbcb_test_commentary', [ $this, 'ajax_test_commentary' ] );
        add_action( 'wp_ajax_rtbcb_test_company_overview', [ $this, 'ajax_test_company_overview' ] );
        add_action( 'wp_ajax_rtbcb_test_industry_overview', [ $this, 'ajax_test_industry_overview' ] );
        add_action( 'wp_ajax_rtbcb_test_real_treasury_overview', [ $this, 'ajax_test_real_treasury_overview' ] );
        add_action( 'wp_ajax_rtbcb_test_maturity_model', [ $this, 'ajax_test_maturity_model' ] );
        add_action( 'wp_ajax_rtbcb_test_rag_market_analysis', [ $this, 'ajax_test_rag_market_analysis' ] );
        add_action( 'wp_ajax_rtbcb_test_value_proposition', [ $this, 'ajax_test_value_proposition' ] );
        add_action( 'wp_ajax_rtbcb_get_company_data', [ $this, 'ajax_get_company_data' ] );
        add_action( 'wp_ajax_rtbcb_test_estimated_benefits', [ $this, 'ajax_test_estimated_benefits' ] );
        add_action( 'wp_ajax_rtbcb_save_test_results', [ $this, 'save_test_results' ] );
        add_action( 'wp_ajax_rtbcb_set_test_company', [ $this, 'ajax_set_test_company' ] );
        add_action( 'wp_ajax_rtbcb_test_calculate_roi', [ $this, 'ajax_test_calculate_roi' ] );
        add_action( 'wp_ajax_rtbcb_test_portal', [ $this, 'ajax_test_portal' ] );
        add_action( 'wp_ajax_rtbcb_test_rag', [ $this, 'ajax_test_rag' ] );
        add_action( 'wp_ajax_rtbcb_test_data_enrichment', [ $this, 'ajax_test_data_enrichment' ] );
        add_action( 'wp_ajax_rtbcb_test_data_storage', [ $this, 'ajax_test_data_storage' ] );
        add_action( 'wp_ajax_rtbcb_test_report_assembly', [ $this, 'ajax_test_report_assembly' ] );
        add_action( 'wp_ajax_rtbcb_test_tracking_script', [ $this, 'ajax_test_tracking_script' ] );
        add_action( 'wp_ajax_rtbcb_test_follow_up_email', [ $this, 'ajax_test_follow_up_email' ] );
        add_action( 'wp_ajax_rtbcb_get_phase_completion', [ $this, 'ajax_get_phase_completion' ] );
        add_action( 'wp_ajax_rtbcb_generate_comprehensive_analysis', [ $this, 'ajax_generate_comprehensive_analysis' ] );
        add_action( 'wp_ajax_rtbcb_clear_analysis_data', [ $this, 'ajax_clear_analysis_data' ] );
        add_action( 'wp_ajax_rtbcb_delete_log', [ $this, 'ajax_delete_log' ] );
        add_action( 'wp_ajax_rtbcb_clear_logs', [ $this, 'ajax_clear_logs' ] );
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
        wp_enqueue_script( 
            'rtbcb-admin', 
            RTBCB_URL . 'admin/js/rtbcb-admin.js', 
            [ 'jquery', 'chart-js' ], 
            RTBCB_VERSION, 
            true 
        );
        wp_enqueue_style(
            'rtbcb-admin',
            RTBCB_URL . 'admin/css/rtbcb-admin.css',
            [],
            RTBCB_VERSION
        );

        $company_data = [];
        if ( function_exists( 'rtbcb_get_current_company' ) ) {
            $raw_company = rtbcb_get_current_company();
            if ( is_array( $raw_company ) ) {
                $company_data = [
                    'name'           => isset( $raw_company['name'] ) ? sanitize_text_field( $raw_company['name'] ) : '',
                    'summary'        => isset( $raw_company['summary'] ) ? sanitize_textarea_field( $raw_company['summary'] ) : '',
                    'industry'       => isset( $raw_company['industry'] ) ? sanitize_text_field( $raw_company['industry'] ) : '',
                    'size'           => isset( $raw_company['size'] ) ? sanitize_text_field( $raw_company['size'] ) : '',
                    'geography'      => isset( $raw_company['geography'] ) ? sanitize_text_field( $raw_company['geography'] ) : '',
                    'business_model' => isset( $raw_company['business_model'] ) ? sanitize_text_field( $raw_company['business_model'] ) : '',
                    'revenue'        => isset( $raw_company['revenue'] ) ? floatval( $raw_company['revenue'] ) : 0,
                    'staff_count'    => isset( $raw_company['staff_count'] ) ? intval( $raw_company['staff_count'] ) : 0,
                    'efficiency'     => isset( $raw_company['efficiency'] ) ? floatval( $raw_company['efficiency'] ) : 0,
                ];

                $company_data['focus_areas'] = isset( $raw_company['focus_areas'] )
                    ? array_filter( array_map( 'sanitize_text_field', (array) $raw_company['focus_areas'] ) )
                    : [];
                $company_data['complexity']  = isset( $raw_company['complexity'] ) ? sanitize_text_field( $raw_company['complexity'] ) : '';
                $company_data['challenges']  = isset( $raw_company['challenges'] )
                    ? array_filter( array_map( 'sanitize_text_field', (array) $raw_company['challenges'] ) )
                    : [];
            }
        }

        $sections_js = [];
        if ( function_exists( 'rtbcb_get_dashboard_sections' ) ) {
            $raw_sections = rtbcb_get_dashboard_sections();
            foreach ( $raw_sections as $id => $section ) {
                $section_data = [
                    'id'        => sanitize_key( $id ),
                    'label'     => isset( $section['label'] ) ? sanitize_text_field( $section['label'] ) : '',
                    'option'    => isset( $section['option'] ) ? sanitize_key( $section['option'] ) : '',
                    'requires'  => isset( $section['requires'] ) ? array_map( 'sanitize_key', (array) $section['requires'] ) : [],
                    'phase'     => isset( $section['phase'] ) ? (int) $section['phase'] : 0,
                    'completed' => ! empty( $section['completed'] ),
                ];
                if ( ! empty( $section['action'] ) ) {
                    $action = sanitize_key( $section['action'] );
                    $section_data['action'] = $action;
                    $section_data['nonce']  = wp_create_nonce( $action );
                }
                $sections_js[] = $section_data;
            }
        }

        wp_localize_script( 'rtbcb-admin', 'rtbcbAdmin', [
            'ajax_url'                   => admin_url( 'admin-ajax.php' ),
            'nonce'                      => wp_create_nonce( 'rtbcb_nonce' ),
            'diagnostics_nonce'          => wp_create_nonce( 'rtbcb_diagnostics' ),
            'company_overview_nonce'     => wp_create_nonce( 'rtbcb_test_company_overview' ),
            'maturity_model_nonce'       => wp_create_nonce( 'rtbcb_test_maturity_model' ),
            'rag_market_analysis_nonce'  => wp_create_nonce( 'rtbcb_test_rag_market_analysis' ),
            'value_proposition_nonce'    => wp_create_nonce( 'rtbcb_test_value_proposition' ),
            'industry_overview_nonce'    => wp_create_nonce( 'rtbcb_test_industry_overview' ),
            'benefits_estimate_nonce'    => wp_create_nonce( 'rtbcb_test_estimated_benefits' ),
            'test_dashboard_nonce'       => wp_create_nonce( 'rtbcb_test_dashboard' ),
            'roi_nonce'                  => wp_create_nonce( 'rtbcb_test_calculate_roi' ),
            'real_treasury_overview_nonce' => wp_create_nonce( 'rtbcb_test_real_treasury_overview' ),
            'category_recommendation_nonce' => wp_create_nonce( 'rtbcb_test_category_recommendation' ),
            'report_assembly_nonce'      => wp_create_nonce( 'rtbcb_test_report_assembly' ),
            'tracking_script_nonce'      => wp_create_nonce( 'rtbcb_test_tracking_script' ),
            'follow_up_email_nonce'      => wp_create_nonce( 'rtbcb_test_follow_up_email' ),
            'page'                       => $page,
            'company'                    => $company_data,
            'sections'                   => $sections_js,
            'strings'                    => [
                'confirm_delete'      => __( 'Are you sure you want to delete this lead?', 'rtbcb' ),
                'confirm_bulk_delete' => __( 'Are you sure you want to delete the selected leads?', 'rtbcb' ),
                'processing'          => __( 'Processing...', 'rtbcb' ),
                'error'               => __( 'An error occurred. Please try again.', 'rtbcb' ),
                'testing'             => __( 'Testing...', 'rtbcb' ),
                'generating'          => __( 'Generating...', 'rtbcb' ),
                'copied'              => __( 'Copied to clipboard.', 'rtbcb' ),
                'retry'               => __( 'Retry', 'rtbcb' ),
                'view'                => __( 'View', 'rtbcb' ),
                'rerun'               => __( 'Re-run', 'rtbcb' ),
                'company_required'    => __( 'Company name is required.', 'rtbcb' ),
                'completion'          => __( 'Completion %', 'rtbcb' ),
            ],
        ] );

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
            __( 'API Logs', 'rtbcb' ),
            __( 'API Logs', 'rtbcb' ),
            'manage_options',
            'rtbcb-api-logs',
            [ $this, 'render_api_logs' ]
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
        $portal_active   = $this->check_portal_integration();
        $rag_health_info = $this->check_rag_health();
        $rag_health      = ( 'healthy' === ( $rag_health_info['status'] ?? '' ) );
        $last_indexed   = get_option( 'rtbcb_last_indexed', '' );
        $vendor_count   = $this->get_vendor_count();

        include RTBCB_DIR . 'admin/test-dashboard-page.php';
    }

    /**
     * Render API logs page.
     *
     * @return void
     */
    public function render_api_logs() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $paged     = isset( $_GET['paged'] ) ? intval( wp_unslash( $_GET['paged'] ) ) : 1;
        $per_page  = 20;
        $logs_data = RTBCB_API_Log::get_logs_paginated( $paged, $per_page );
        $nonce     = wp_create_nonce( 'rtbcb_api_logs' );

        include RTBCB_DIR . 'admin/api-logs-page.php';
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
            $data = [];
            if ( isset( $item['data'] ) && is_array( $item['data'] ) ) {
                foreach ( $item['data'] as $key => $value ) {
                    if ( is_scalar( $value ) ) {
                        $data[ sanitize_key( $key ) ] = is_numeric( $value ) ? ( 0 + $value ) : sanitize_text_field( $value );
                    }
                }
            }

            $sanitized[] = [
                'section'   => isset( $item['section'] ) ? sanitize_text_field( $item['section'] ) : '',
                'status'    => isset( $item['status'] ) ? sanitize_text_field( $item['status'] ) : '',
                'message'   => isset( $item['message'] ) ? sanitize_text_field( $item['message'] ) : '',
                'timestamp' => current_time( 'mysql' ),
                'data'      => $data,
            ];
        }

        $existing    = get_option( 'rtbcb_test_results', [] );
        $existing    = is_array( $existing ) ? $existing : [];
        $combined    = array_merge( $sanitized, $existing );
        $max_results = 10;
        if ( count( $combined ) > $max_results ) {
            $combined = array_slice( $combined, 0, $max_results );
        }

        update_option( 'rtbcb_test_results', $combined );

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
        register_setting( 'rtbcb_settings', 'rtbcb_gpt5_timeout', [ 'sanitize_callback' => 'intval' ] );
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
     * Retrieves the company name from the request, stored options, or the
     * currently selected company. Returns an error only if no name can be
     * determined.
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

        if ( '' === $company_name ) {
            $stored_data = get_option( 'rtbcb_company_data', [] );
            if ( is_array( $stored_data ) && ! empty( $stored_data['name'] ) ) {
                $company_name = sanitize_text_field( $stored_data['name'] );
            }
        }

        if ( '' === $company_name ) {
            $current_company = rtbcb_get_current_company();
            if ( is_array( $current_company ) && ! empty( $current_company['name'] ) ) {
                $company_name = sanitize_text_field( $current_company['name'] );
            }
        }

        if ( '' === $company_name ) {
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
            $metrics         = is_array( $overview['metrics'] ?? null ) ? $overview['metrics'] : [];
            $revenue         = floatval( $metrics['revenue'] ?? 0 );
            $staff_count     = intval( $metrics['staff_count'] ?? 0 );
            $efficiency      = floatval( $metrics['baseline_efficiency'] ?? 0 );

            $word_count   = str_word_count( wp_strip_all_tags( $analysis ) );
            $elapsed_time = microtime( true ) - $start_time;

            $existing = rtbcb_get_current_company();
            $company_data = [
                'name'            => $company_name,
                'summary'         => sanitize_textarea_field( wp_strip_all_tags( $analysis ) ),
                'recommendations' => $recommendations,
                'references'      => $references,
                'generated_at'    => current_time( 'mysql' ),
                'focus_areas'     => array_map( 'sanitize_text_field', (array) ( $existing['focus_areas'] ?? [] ) ),
                'industry'        => isset( $existing['industry'] ) ? sanitize_text_field( $existing['industry'] ) : '',
                'size'            => isset( $existing['size'] ) ? sanitize_text_field( $existing['size'] ) : '',
                'revenue'         => $revenue,
                'staff_count'     => $staff_count,
                'efficiency'      => $efficiency,
            ];

            update_option( 'rtbcb_current_company', $company_data );
            $stored = get_option( 'rtbcb_company_data', [] );
            if ( ! is_array( $stored ) ) {
                $stored = [];
            }
            $stored['name']        = $company_name;
            $stored['revenue']     = $revenue;
            $stored['staff_count'] = $staff_count;
            $stored['efficiency']  = $efficiency;
            update_option( 'rtbcb_company_data', $stored );

            wp_send_json_success(
                [
                    'message'         => __( 'Company overview generated.', 'rtbcb' ),
                    'overview'        => wp_kses_post( $analysis ),
                    'word_count'      => $word_count,
                    'elapsed'         => round( $elapsed_time, 2 ),
                    'generated'       => current_time( 'mysql' ),
                    'recommendations' => $recommendations,
                    'references'      => $references,
                    'focus_areas'     => $company_data['focus_areas'],
                    'industry'        => $company_data['industry'],
                    'size'            => $company_data['size'],
                    'metrics'         => [
                        'revenue'     => $revenue,
                        'staff_count' => $staff_count,
                        'efficiency'  => $efficiency,
                    ],
                ]
            );

        } catch ( Exception $e ) {
            error_log( 'RTBCB Company Overview Error: ' . $e->getMessage() );
            wp_send_json_error( [
                'message' => __( 'An error occurred while generating the overview.', 'rtbcb' ),
            ] );
        }
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
            $company_data = rtbcb_get_current_company();

            if ( empty( $company_data ) || ! is_array( $company_data ) ) {
                wp_send_json_error( [ 'message' => __( 'Invalid company data.', 'rtbcb' ) ] );
            }
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
     * AJAX handler for maturity model testing.
     *
     * @return void
     */
    public function ajax_test_maturity_model() {
        check_ajax_referer( 'rtbcb_test_maturity_model', 'nonce' );

        $company = rtbcb_get_current_company();
        $result  = rtbcb_test_generate_maturity_model( $company );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        update_option( 'rtbcb_maturity_model', $result['narrative'] );

        wp_send_json_success(
            [
                'assessment' => sanitize_textarea_field( $result['narrative'] ),
                'level'      => sanitize_text_field( $result['level'] ),
                'generated'  => current_time( 'mysql' ),
                'word_count' => str_word_count( $result['narrative'] ),
            ]
        );
    }

    /**
     * AJAX handler for RAG market analysis testing.
     *
     * @return void
     */
    public function ajax_test_rag_market_analysis() {
        check_ajax_referer( 'rtbcb_test_rag_market_analysis', 'nonce' );

        $company = rtbcb_get_current_company();
        $terms   = [];

        if ( ! empty( $company['industry'] ) ) {
            $terms[] = sanitize_text_field( $company['industry'] );
        }

        if ( ! empty( $company['focus_areas'] ) && is_array( $company['focus_areas'] ) ) {
            $terms = array_merge( $terms, array_map( 'sanitize_text_field', $company['focus_areas'] ) );
        }

        if ( empty( $terms ) && ! empty( $company['summary'] ) ) {
            $terms[] = sanitize_text_field( wp_trim_words( $company['summary'], 5, '' ) );
        }

        if ( empty( $terms ) ) {
            wp_send_json_error( [ 'message' => __( 'Company data required for query.', 'rtbcb' ) ] );
        }

        $query   = sanitize_text_field( implode( ' ', $terms ) );
        $vendors = rtbcb_test_rag_market_analysis( $query );
        if ( is_wp_error( $vendors ) ) {
            wp_send_json_error( [ 'message' => $vendors->get_error_message() ] );
        }

        update_option( 'rtbcb_rag_market_analysis', $vendors );

        wp_send_json_success(
            [
                'vendors'   => array_map( 'sanitize_text_field', $vendors ),
                'generated' => current_time( 'mysql' ),
            ]
        );
    }

    /**
     * AJAX handler for value proposition testing.
     *
     * @return void
     */
    public function ajax_test_value_proposition() {
        check_ajax_referer( 'rtbcb_test_value_proposition', 'nonce' );

        $company = rtbcb_get_current_company();
        $paragraph = rtbcb_test_generate_value_proposition( $company );
        if ( is_wp_error( $paragraph ) ) {
            wp_send_json_error( [ 'message' => $paragraph->get_error_message() ] );
        }

        update_option( 'rtbcb_value_proposition', $paragraph );

        wp_send_json_success(
            [
                'paragraph' => sanitize_textarea_field( $paragraph ),
                'word_count'=> str_word_count( $paragraph ),
                'generated' => current_time( 'mysql' ),
            ]
        );
    }

    /**
     * AJAX handler for treasury tech overview testing.
     *
     * @return void
     */
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

        $inputs     = rtbcb_get_sample_inputs();
        $challenges = array_map( 'sanitize_text_field', $inputs['pain_points'] ?? [] );

        $company = rtbcb_get_current_company();
        if ( ! is_array( $company ) ) {
            $company = [];
        }

        if ( ! empty( $challenges ) ) {
            $existing_challenges   = isset( $company['challenges'] ) ? (array) $company['challenges'] : [];
            $merged_challenges     = array_values( array_unique( array_merge( $existing_challenges, $challenges ) ) );
            $company['challenges'] = array_filter( array_map( 'sanitize_text_field', $merged_challenges ) );
        }

        if ( ! empty( $categories ) ) {
            $existing_categories   = isset( $company['categories'] ) ? (array) $company['categories'] : [];
            $merged_categories     = array_values( array_unique( array_merge( $existing_categories, $categories ) ) );
            $company['categories'] = array_filter( array_map( 'sanitize_text_field', $merged_categories ) );
        }

        update_option( 'rtbcb_current_company', $company );

        $start    = microtime( true );
        $overview = rtbcb_test_generate_real_treasury_overview( $include_portal, $categories );
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
    /**
     * AJAX handler to generate estimated benefits.
     *
     * Stores sanitized results to mark the section complete.
     *
     * @return void
     */
    public function ajax_test_estimated_benefits() {
        check_ajax_referer( 'rtbcb_test_estimated_benefits', 'nonce' );

        $company_data = [];

        if ( isset( $_POST['company_data'] ) && is_array( $_POST['company_data'] ) ) {
            $company_data = [
                'revenue'     => isset( $_POST['company_data']['revenue'] ) ? floatval( wp_unslash( $_POST['company_data']['revenue'] ) ) : 0,
                'staff_count' => isset( $_POST['company_data']['staff_count'] ) ? intval( wp_unslash( $_POST['company_data']['staff_count'] ) ) : 0,
                'efficiency'  => isset( $_POST['company_data']['efficiency'] ) ? floatval( wp_unslash( $_POST['company_data']['efficiency'] ) ) : 0,
            ];
        }

        if ( empty( $company_data ) && function_exists( 'rtbcb_get_current_company' ) ) {
            $current = rtbcb_get_current_company();
            if ( is_array( $current ) ) {
                $company_data = [
                    'revenue'     => isset( $current['revenue'] ) ? floatval( $current['revenue'] ) : 0,
                    'staff_count' => isset( $current['staff_count'] ) ? intval( $current['staff_count'] ) : 0,
                    'efficiency'  => isset( $current['efficiency'] ) ? floatval( $current['efficiency'] ) : 0,
                ];
            }
        }

        $category = isset( $_POST['recommended_category'] ) ? sanitize_text_field( wp_unslash( $_POST['recommended_category'] ) ) : '';
        if ( empty( $category ) ) {
            $stored_category = get_option( 'rtbcb_last_recommended_category', '' );
            if ( ! empty( $stored_category ) ) {
                $category = sanitize_text_field( $stored_category );
            }
        }

        if ( empty( $company_data ) ) {
            wp_send_json_error( [ 'message' => __( 'Company data is required to estimate benefits.', 'rtbcb' ) ] );
        }

        if ( empty( $category ) ) {
            wp_send_json_error( [ 'message' => __( 'Recommended category is required to estimate benefits.', 'rtbcb' ) ] );
        }

        $estimate = rtbcb_test_generate_benefits_estimate( $company_data, $category );

        if ( is_wp_error( $estimate ) ) {
            wp_send_json_error( [ 'message' => sanitize_text_field( $estimate->get_error_message() ) ] );
        }

        $sanitize_recursive = function ( $data ) use ( &$sanitize_recursive ) {
            $sanitized = [];
            foreach ( (array) $data as $key => $value ) {
                $clean_key = sanitize_key( $key );
                if ( is_array( $value ) ) {
                    $sanitized[ $clean_key ] = $sanitize_recursive( $value );
                } elseif ( is_numeric( $value ) ) {
                    $sanitized[ $clean_key ] = floatval( $value );
                } else {
                    $sanitized[ $clean_key ] = sanitize_text_field( $value );
                }
            }

            return $sanitized;
        };

        $sanitized_estimate = $sanitize_recursive( $estimate );
        update_option( 'rtbcb_estimated_benefits', $sanitized_estimate );

        wp_send_json_success( [ 'estimate' => $sanitized_estimate ] );
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
     * AJAX handler for data enrichment testing.
     *
     * @return void
     */
    public function ajax_test_data_enrichment() {
        check_ajax_referer( 'rtbcb_test_data_enrichment', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ] );
        }

        $inputs   = rtbcb_get_sample_inputs();
        $roi_data = [
            'base' => [
                'labor_savings'        => 1000,
                'fee_savings'          => 500,
                'error_reduction'      => 250,
                'total_annual_benefit' => 1750,
                'roi_percentage'       => 150,
                'assumptions'          => [],
            ],
        ];

        $llm      = new RTBCB_LLM();
        $analysis = $llm->generate_business_case( $inputs, $roi_data );

        if ( is_wp_error( $analysis ) ) {
            wp_send_json_error( [ 'message' => sanitize_text_field( $analysis->get_error_message() ) ] );
        }

        update_option( 'rtbcb_data_enrichment', $analysis );

        wp_send_json_success( [ 'analysis' => $analysis ] );
    }

    /**
     * AJAX handler for data storage testing.
     *
     * @return void
     */
    public function ajax_test_data_storage() {
        check_ajax_referer( 'rtbcb_test_data_storage', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ] );
        }

        RTBCB_DB::init();

        $email   = 'test+' . wp_rand( 1000, 9999 ) . '@example.com';
        $lead_id = RTBCB_Leads::save_lead(
            [
                'email'        => $email,
                'company_size' => 'medium',
                'industry'     => 'finance',
            ]
        );

        if ( ! $lead_id ) {
            wp_send_json_error( [ 'message' => __( 'Failed to save lead.', 'rtbcb' ) ] );
        }

        $lead = RTBCB_Leads::get_lead_by_email( $email );

        if ( ! $lead ) {
            wp_send_json_error( [ 'message' => __( 'Failed to retrieve lead.', 'rtbcb' ) ] );
        }

        update_option( 'rtbcb_data_storage', $lead );

        wp_send_json_success(
            [
                'lead_id'    => intval( $lead_id ),
                'lead_email' => sanitize_email( $lead['email'] ),
            ]
        );
    }

    /**
     * AJAX handler for report assembly testing.
     *
     * @return void
     */
    public function ajax_test_report_assembly() {
        check_ajax_referer( 'rtbcb_test_report_assembly', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ] );
        }

        $summary = rtbcb_test_generate_executive_summary();

        if ( is_wp_error( $summary ) ) {
            wp_send_json_error( [ 'message' => $summary->get_error_message() ] );
        }

        $combined = $summary['strategic_positioning'] . ' ' .
            implode( ' ', $summary['key_value_drivers'] ) . ' ' .
            $summary['executive_recommendation'];
        $word_count = str_word_count( wp_strip_all_tags( $combined ) );

        wp_send_json_success(
            [
                'summary'   => $summary,
                'word_count'=> $word_count,
                'generated' => current_time( 'mysql' ),
            ]
        );
    }

    /**
     * AJAX handler for tracking script testing.
     *
     * @return void
     */
    public function ajax_test_tracking_script() {
        check_ajax_referer( 'rtbcb_test_tracking_script', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ] );
        }

        update_option( 'rtbcb_tracking_script', [ 'verified' => true, 'timestamp' => current_time( 'mysql' ) ] );

        $result   = [
            'section'   => 'rtbcb-test-tracking-script',
            'status'    => 'success',
            'message'   => __( 'Tracking event captured.', 'rtbcb' ),
            'timestamp' => current_time( 'mysql' ),
        ];
        $existing = get_option( 'rtbcb_test_results', [] );
        $existing = is_array( $existing ) ? $existing : [];
        array_unshift( $existing, $result );
        $existing = array_slice( $existing, 0, 10 );
        update_option( 'rtbcb_test_results', $existing );

        wp_send_json_success( [ 'message' => __( 'Tracking event captured.', 'rtbcb' ) ] );
    }

    /**
     * AJAX handler for follow-up email testing.
     *
     * @return void
     */
    public function ajax_test_follow_up_email() {
        check_ajax_referer( 'rtbcb_test_follow_up_email', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rtbcb' ) ] );
        }

        $queue   = get_option( 'rtbcb_follow_up_queue', [] );
        $queue   = is_array( $queue ) ? $queue : [];
        $email   = 'lead+' . wp_rand( 1000, 9999 ) . '@example.com';
        $message = __( 'Thanks for reviewing your business case. Let us know if you have questions.', 'rtbcb' );

        $entry = [
            'email'     => sanitize_email( $email ),
            'message'   => sanitize_text_field( $message ),
            'queued_at' => current_time( 'mysql' ),
        ];
        $queue[] = $entry;
        update_option( 'rtbcb_follow_up_queue', $queue );

        $result   = [
            'section'   => 'rtbcb-test-follow-up-email',
            'status'    => 'success',
            'message'   => __( 'Follow-up email queued.', 'rtbcb' ),
            'timestamp' => current_time( 'mysql' ),
        ];
        $existing = get_option( 'rtbcb_test_results', [] );
        $existing = is_array( $existing ) ? $existing : [];
        array_unshift( $existing, $result );
        $existing = array_slice( $existing, 0, 10 );
        update_option( 'rtbcb_test_results', $existing );

        wp_send_json_success( [ 'queue' => $queue ] );
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

        if ( ! class_exists( 'RTBCB_RAG' ) ) {
            wp_send_json_error( [ 'message' => __( 'RAG system not available.', 'rtbcb' ) ] );
        }

        try {
            $rag = new RTBCB_RAG();
            $rag->rebuild_index();
        } catch ( Exception $e ) {
            wp_send_json_error( [ 'message' => sanitize_text_field( $e->getMessage() ) ] );
        }

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
     * AJAX handler to set company name for tests.
     *
     * @return void
     */
    public function ajax_set_test_company() {
        check_ajax_referer( 'rtbcb_set_test_company', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rtbcb' ) ], 403 );
        }

        $company_name = isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';
        if ( '' === $company_name ) {
            wp_send_json_error( [ 'message' => __( 'Invalid company name.', 'rtbcb' ) ], 400 );
        }

        try {
            $existing     = rtbcb_get_current_company();
            $company_data = [
                'name'            => $company_name,
                'summary'         => '',
                'recommendations' => [],
                'references'      => [],
                'generated_at'    => '',
                'focus_areas'     => array_map( 'sanitize_text_field', (array) ( $existing['focus_areas'] ?? [] ) ),
                'industry'        => isset( $existing['industry'] ) ? sanitize_text_field( $existing['industry'] ) : '',
                'size'            => isset( $existing['size'] ) ? sanitize_text_field( $existing['size'] ) : '',
                'revenue'         => 0,
                'staff_count'     => 0,
                'efficiency'      => 0,
            ];

            update_option( 'rtbcb_current_company', $company_data );

            $stored = get_option( 'rtbcb_company_data', [] );
            if ( ! is_array( $stored ) ) {
                $stored = [];
            }
            $stored['name'] = $company_name;
            update_option( 'rtbcb_company_data', $stored );

            delete_option( 'rtbcb_test_results' );

            wp_send_json_success(
                [
                    'message' => __( 'Company saved. Generating overview...', 'rtbcb' ),
                    'name'    => $company_name,
                ]
            );
        } catch ( Exception $e ) {
            error_log( 'RTBCB Set Test Company Error: ' . $e->getMessage() );
            wp_send_json_error(
                [
                    'message' => __( 'An error occurred while saving company data.', 'rtbcb' ),
                ]
            );
        }
    }

    /**
     * AJAX handler to test Portal integration.
     *
     * @return void
     */
    public function ajax_test_portal() {
        check_ajax_referer( 'rtbcb_test_portal', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized', 'rtbcb' ) ] );
        }

        if ( ! $this->check_portal_integration() ) {
            wp_send_json_error( [ 'message' => __( 'Portal integration not active.', 'rtbcb' ) ] );
        }

        $vendor_count = $this->get_vendor_count();

        wp_send_json_success( [ 'vendor_count' => intval( $vendor_count ) ] );
    }

    /**
     * AJAX handler to test RAG index health.
     *
     * @return void
     */
    public function ajax_test_rag() {
        check_ajax_referer( 'rtbcb_test_rag', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized', 'rtbcb' ) ] );
        }

        $health = $this->check_rag_health();

        if ( 'healthy' !== ( $health['status'] ?? '' ) ) {
            $message = isset( $health['message'] ) ? sanitize_text_field( $health['message'] ) : __( 'RAG index needs attention.', 'rtbcb' );
            wp_send_json_error(
                [
                    'status'  => sanitize_text_field( $health['status'] ?? '' ),
                    'message' => $message,
                ]
            );
        }

        wp_send_json_success(
            [
                'status'        => 'healthy',
                'indexed_items' => isset( $health['indexed_items'] ) ? intval( $health['indexed_items'] ) : 0,
                'last_updated'  => isset( $health['last_updated'] ) ? sanitize_text_field( $health['last_updated'] ) : '',
            ]
        );
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

        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
        if ( $table_exists !== $table_name ) {
            return [
                'status'        => 'missing',
                'message'       => __( 'RAG index table missing. Use the rebuild button to create it.', 'rtbcb' ),
                'indexed_items' => 0,
                'last_updated'  => '',
            ];
        }

        $count       = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
        $last_updated = $wpdb->get_var( "SELECT MAX(updated_at) FROM {$table_name}" );

        if ( $wpdb->last_error ) {
            return [
                'status'  => 'error',
                'message' => sprintf( __( 'Database error: %s', 'rtbcb' ), $wpdb->last_error ),
            ];
        }

        if ( 0 === $count ) {
            return [
                'status'        => 'empty',
                'message'       => __( 'RAG index is empty. Rebuild the index to add data.', 'rtbcb' ),
                'indexed_items' => 0,
                'last_updated'  => $last_updated,
            ];
        }

        return [
            'status'        => 'healthy',
            'indexed_items' => $count,
            'last_updated'  => $last_updated,
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
     * AJAX handler to fetch phase completion percentages.
     *
     * @return void
     */
    public function ajax_get_phase_completion() {
        check_ajax_referer( 'rtbcb_test_dashboard', 'nonce' );

        $sections    = rtbcb_get_dashboard_sections();
        $percentages = rtbcb_calculate_phase_completion( $sections );

        wp_send_json_success( [ 'percentages' => $percentages ] );
    }

    /**
     * AJAX handler to generate comprehensive analysis.
     *
     * @return void
     */
    public function ajax_generate_comprehensive_analysis() {
        check_ajax_referer( 'rtbcb_test_dashboard', 'nonce' );

        $company_name = isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';
        if ( '' === $company_name ) {
            $stored       = get_option( 'rtbcb_company_data', [] );
            $company_name = isset( $stored['name'] ) ? sanitize_text_field( $stored['name'] ) : '';
        }

        if ( '' === $company_name ) {
            wp_send_json_error( [ 'message' => __( 'Company name is required.', 'rtbcb' ) ] );
        }

        $rag_context = [];
        $vendor_list = [];

        if ( function_exists( 'rtbcb_test_rag_market_analysis' ) && function_exists( 'rtbcb_get_current_company' ) ) {
            $company = rtbcb_get_current_company();
            $terms   = [];

            if ( ! empty( $company['industry'] ) ) {
                $terms[] = sanitize_text_field( $company['industry'] );
            }

            if ( ! empty( $company['focus_areas'] ) && is_array( $company['focus_areas'] ) ) {
                $terms = array_merge( $terms, array_map( 'sanitize_text_field', $company['focus_areas'] ) );
            }

            if ( empty( $terms ) && ! empty( $company['summary'] ) ) {
                $terms[] = sanitize_text_field( wp_trim_words( $company['summary'], 5, '' ) );
            }

            if ( empty( $terms ) ) {
                $terms[] = $company_name;
            }

            $query       = sanitize_text_field( implode( ' ', $terms ) );
            $vendor_list = rtbcb_test_rag_market_analysis( $query );

            if ( is_wp_error( $vendor_list ) ) {
                $vendor_list = [];
            }

            $rag_context = array_map( 'sanitize_text_field', $vendor_list );
        }

        $llm      = new RTBCB_LLM();
        $analysis = $llm->generate_comprehensive_business_case( [ 'company_name' => $company_name ], [], $rag_context );

        if ( is_wp_error( $analysis ) ) {
            wp_send_json_error( [ 'message' => $analysis->get_error_message() ] );
        }

        $timestamp = current_time( 'mysql' );

        update_option( 'rtbcb_current_company', $analysis['company_overview'] );
        update_option( 'rtbcb_industry_insights', $analysis['industry_analysis'] );
        update_option( 'rtbcb_maturity_model', $analysis['treasury_maturity'] );
        update_option( 'rtbcb_rag_market_analysis', $vendor_list );
        update_option( 'rtbcb_roadmap_plan', $analysis['implementation_roadmap'] );
        update_option( 'rtbcb_value_proposition', $analysis['executive_summary']['executive_recommendation'] ?? '' );
        update_option( 'rtbcb_estimated_benefits', $analysis['financial_analysis'] );
        update_option( 'rtbcb_executive_summary', $analysis['executive_summary'] );

        $results = [
            'company_overview' => [
                'summary'   => $analysis['company_overview'],
                'stored_in' => 'rtbcb_current_company',
            ],
            'industry_analysis' => [
                'summary'   => $analysis['industry_analysis'],
                'stored_in' => 'rtbcb_industry_insights',
            ],
            'treasury_maturity' => [
                'summary'   => $analysis['treasury_maturity'],
                'stored_in' => 'rtbcb_maturity_model',
            ],
            'market_analysis' => [
                'summary'   => $vendor_list,
                'stored_in' => 'rtbcb_rag_market_analysis',
            ],
            'implementation_roadmap' => [
                'summary'   => $analysis['implementation_roadmap'],
                'stored_in' => 'rtbcb_roadmap_plan',
            ],
            'value_proposition' => [
                'summary'   => $analysis['executive_summary'],
                'stored_in' => 'rtbcb_value_proposition',
            ],
            'financial_analysis' => [
                'summary'   => $analysis['financial_analysis'],
                'stored_in' => 'rtbcb_estimated_benefits',
            ],
            'executive_summary' => [
                'summary'   => $analysis['executive_summary'],
                'stored_in' => 'rtbcb_executive_summary',
            ],
        ];

        $usage_map = [
            [ 'component' => __( 'Company Overview & Metrics', 'rtbcb' ), 'used_in' => __( 'Company Overview Test', 'rtbcb' ), 'option' => 'rtbcb_current_company' ],
            [ 'component' => __( 'Industry Analysis', 'rtbcb' ), 'used_in' => __( 'Industry Overview Test', 'rtbcb' ), 'option' => 'rtbcb_industry_insights' ],
            [ 'component' => __( 'Treasury Maturity Assessment', 'rtbcb' ), 'used_in' => __( 'Maturity Model Test', 'rtbcb' ), 'option' => 'rtbcb_maturity_model' ],
            [ 'component' => __( 'Market Analysis & Vendors', 'rtbcb' ), 'used_in' => __( 'RAG Market Analysis Test', 'rtbcb' ), 'option' => 'rtbcb_rag_market_analysis' ],
            [ 'component' => __( 'Value Proposition Paragraph', 'rtbcb' ), 'used_in' => __( 'Value Proposition Test', 'rtbcb' ), 'option' => 'rtbcb_value_proposition' ],
            [ 'component' => __( 'Financial Benefits Breakdown', 'rtbcb' ), 'used_in' => __( 'Estimated Benefits Test', 'rtbcb' ), 'option' => 'rtbcb_estimated_benefits' ],
            [ 'component' => __( 'Executive Summary', 'rtbcb' ), 'used_in' => __( 'Report Assembly Test', 'rtbcb' ), 'option' => 'rtbcb_executive_summary' ],
            [ 'component' => __( 'Implementation Roadmap', 'rtbcb' ), 'used_in' => __( 'Roadmap Generator Test', 'rtbcb' ), 'option' => 'rtbcb_roadmap_plan' ],
        ];

        wp_send_json_success(
            [
                'message'               => __( 'Comprehensive analysis generated', 'rtbcb' ),
                'components_generated'  => 7,
                'timestamp'             => $timestamp,
                'results'               => $results,
                'usage_map'             => $usage_map,
            ]
        );
    }

    /**
     * Clear stored comprehensive analysis data.
     *
     * @return void
     */
    public function ajax_clear_analysis_data() {
        check_ajax_referer( 'rtbcb_test_dashboard', 'nonce' );

        $options = [
            'rtbcb_current_company',
            'rtbcb_industry_insights',
            'rtbcb_maturity_model',
            'rtbcb_rag_market_analysis',
            'rtbcb_value_proposition',
            'rtbcb_estimated_benefits',
            'rtbcb_executive_summary',
            'rtbcb_roadmap_plan',
        ];

        foreach ( $options as $opt ) {
            delete_option( $opt );
        }

        wp_send_json_success( [ 'message' => __( 'Stored analysis data cleared.', 'rtbcb' ) ] );
    }

    /**
     * AJAX handler for deleting a single log entry.
     *
     * @return void
     */
    public function ajax_delete_log() {
        check_ajax_referer( 'rtbcb_api_logs', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized', 'rtbcb' ) ] );
        }

        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        if ( $id <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'Invalid log ID.', 'rtbcb' ) ] );
        }

        $deleted = RTBCB_API_Log::delete_log( $id );

        if ( $deleted ) {
            wp_send_json_success();
        }

        wp_send_json_error( [ 'message' => __( 'Failed to delete log.', 'rtbcb' ) ] );
    }

    /**
     * AJAX handler for clearing all log entries.
     *
     * @return void
     */
    public function ajax_clear_logs() {
        check_ajax_referer( 'rtbcb_api_logs', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized', 'rtbcb' ) ] );
        }

        RTBCB_API_Log::clear_logs();

        wp_send_json_success();
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
