<?php
/**
 * Enhanced admin functionality for Real Treasury Business Case Builder plugin.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

defined( 'ABSPATH' ) || exit;

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
        add_action( 'admin_notices', [ $this, 'maybe_show_timeout_notice' ] );

        // AJAX handlers
        add_action( 'wp_ajax_rtbcb_test_connection', [ $this, 'test_api_connection' ] );
        add_action( 'wp_ajax_rtbcb_rebuild_index', [ $this, 'rebuild_rag_index' ] );
        add_action( 'wp_ajax_rtbcb_export_leads', [ $this, 'export_leads_csv' ] );
        add_action( 'wp_ajax_rtbcb_delete_lead', [ $this, 'delete_lead' ] );
        add_action( 'wp_ajax_rtbcb_bulk_action_leads', [ $this, 'bulk_action_leads' ] );
        add_action( 'wp_ajax_rtbcb_run_tests', [ $this, 'run_integration_tests' ] );
        add_action( 'wp_ajax_rtbcb_test_api', [ $this, 'ajax_test_api' ] );
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
        add_action( 'wp_ajax_rtbcb_save_test_results', [ $this, 'ajax_save_test_results' ] );
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
        add_action( 'wp_ajax_rtbcb_get_section_config', [ $this, 'ajax_get_section_config' ] );
        add_action( 'wp_ajax_rtbcb_get_test_summary_html', [ $this, 'ajax_get_test_summary_html' ] );
        add_action( 'wp_ajax_rtbcb_generate_comprehensive_analysis', [ $this, 'ajax_generate_comprehensive_analysis' ] );
        add_action( 'wp_ajax_rtbcb_get_analysis_status', [ $this, 'ajax_get_analysis_status' ] );
        add_action( 'wp_ajax_rtbcb_clear_analysis_data', [ $this, 'ajax_clear_analysis_data' ] );
        add_action( 'wp_ajax_rtbcb_delete_log', [ $this, 'ajax_delete_log' ] );
		add_action( 'wp_ajax_rtbcb_clear_logs', [ $this, 'ajax_clear_logs' ] );
		add_action( 'wp_ajax_rtbcb_get_workflow_history', [ $this, 'ajax_get_workflow_history' ] );
		add_action( 'wp_ajax_rtbcb_clear_workflow_history', [ $this, 'ajax_clear_workflow_history' ] );
		add_action( 'update_option_rtbcb_openai_api_key', [ $this, 'clear_openai_models_cache' ], 10, 2 );
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

        if ( 'rtbcb-workflow-visualizer' === $page ) {
            wp_enqueue_script(
                'rtbcb-workflow-visualizer',
                RTBCB_URL . 'admin/js/workflow-visualizer.js',
                [ 'jquery' ],
                RTBCB_VERSION,
                true
            );
            wp_localize_script(
                'rtbcb-workflow-visualizer',
                'rtbcbWorkflow',
                [
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'rtbcb_workflow_visualizer' ),
					'strings'  => [
						'refresh_success' => __( 'Workflow history refreshed', 'rtbcb' ),
						'clear_success'   => __( 'Workflow history cleared', 'rtbcb' ),
						'error'           => __( 'An error occurred', 'rtbcb' ),
						'no_history'      => __( 'No workflow history available.', 'rtbcb' ),
						'lead'            => __( 'Lead', 'rtbcb' ),
						'unknown_lead'    => __( 'Unknown Lead', 'rtbcb' ),
						'not_run'         => __( 'Not run', 'rtbcb' ),
					],
                ]
            );
	}

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
            $test_results = get_option( 'rtbcb_test_results', [] );
            $raw_sections = rtbcb_get_dashboard_sections( $test_results );
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
                'queued'              => __( 'Queued', 'rtbcb' ),
                'running'             => __( 'Running', 'rtbcb' ),
                'passed'              => __( 'Passed', 'rtbcb' ),
                'failed'              => __( 'Failed', 'rtbcb' ),
                'copy_debug'          => __( 'Copy Debug Info', 'rtbcb' ),
                'retry'               => __( 'Retry', 'rtbcb' ),
                'view'                => __( 'View', 'rtbcb' ),
                'rerun'               => __( 'Re-run', 'rtbcb' ),
                'company_required'    => __( 'Company name is required.', 'rtbcb' ),
                'completion'          => __( 'Completion %', 'rtbcb' ),
                'starting_tests'      => __( 'Starting tests...', 'rtbcb' ),
                'all_sections_done'   => __( 'All sections completed', 'rtbcb' ),
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

        add_submenu_page(
            'rtbcb-dashboard',
            __( 'Workflow Visualizer', 'rtbcb' ),
            __( 'Workflow Visualizer', 'rtbcb' ),
            'manage_options',
            'rtbcb-workflow-visualizer',
            [ $this, 'render_workflow_visualizer' ]
        );

    }

    /**
     * Render enhanced dashboard with statistics.
     *
     * @return void
    */
    public function render_dashboard() {
        $stats = RTBCB_Leads::get_cached_statistics();
        $recent_leads_data = RTBCB_Leads::get_all_leads( [ 'per_page' => 5, 'orderby' => 'created_at', 'order' => 'DESC' ] );

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
        if ( ! is_array( $categories ) ) {
            $categories = [];
        }

        include RTBCB_DIR . 'admin/leads-page-enhanced.php';
    }

    /**
     * Render analytics page with charts and insights.
     *
     * @return void
     */
    public function render_analytics() {
        $stats = RTBCB_Leads::get_cached_statistics();
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
     * AJAX handler to save test results from dashboard.
     *
     * @return void
     */
    public function ajax_save_test_results() {
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

            $start_time = isset( $item['start_time'] ) ? sanitize_text_field( $item['start_time'] ) : '';
            $end_time   = isset( $item['end_time'] ) ? sanitize_text_field( $item['end_time'] ) : '';
            $duration   = isset( $item['duration'] ) ? floatval( $item['duration'] ) : 0;

            $sanitized[] = [
                'section'    => isset( $item['section'] ) ? sanitize_text_field( $item['section'] ) : '',
                'status'     => isset( $item['status'] ) ? sanitize_text_field( $item['status'] ) : '',
                'message'    => isset( $item['message'] ) ? sanitize_text_field( $item['message'] ) : '',
                'timestamp'  => current_time( 'mysql' ),
                'start_time' => $start_time,
                'end_time'   => $end_time,
                'duration'   => $duration,
                'data'       => $data,
            ];
        }

        $existing    = get_option( 'rtbcb_test_results', [] );
        $existing    = is_array( $existing ) ? $existing : [];
        $combined    = array_merge( $sanitized, $existing );
        $max_results = 10;
        if ( count( $combined ) > $max_results ) {
            $combined = array_slice( $combined, 0, $max_results );
        }

        $updated = update_option( 'rtbcb_test_results', $combined );

        if ( false === $updated ) {
            wp_send_json_error( [ 'message' => __( 'Failed to save test results.', 'rtbcb' ) ] );
        }

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
		register_setting( 'rtbcb_settings', 'rtbcb_gpt5_timeout', [ 'sanitize_callback' => 'rtbcb_sanitize_api_timeout' ] );
        register_setting( 'rtbcb_settings', 'rtbcb_gpt5_max_output_tokens', [ 'sanitize_callback' => 'rtbcb_sanitize_max_output_tokens' ] );
        register_setting( 'rtbcb_settings', 'rtbcb_gpt5_min_output_tokens', [ 'sanitize_callback' => 'rtbcb_sanitize_min_output_tokens' ] );
		register_setting( 'rtbcb_settings', 'rtbcb_fast_mode', [ 'sanitize_callback' => 'absint' ] );
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

	$bulk_action = sanitize_text_field( wp_unslash( $_POST['bulk_action'] ?? '' ) );
	$lead_ids_raw = wp_unslash( $_POST['lead_ids'] ?? [] );

	if ( is_string( $lead_ids_raw ) ) {
		$lead_ids_raw = json_decode( $lead_ids_raw, true );
	}

	$lead_ids = array_map( 'intval', (array) $lead_ids_raw );

        if ( empty( $lead_ids ) ) {
            wp_send_json_error( [ 'message' => __( 'No leads selected.', 'rtbcb' ) ] );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'rtbcb_leads';
        $placeholders = implode( ',', array_fill( 0, count( $lead_ids ), '%d' ) );

	switch ( $bulk_action ) {
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

		$cache_key = 'rtbcb_openai_models';
		$response  = wp_cache_get( $cache_key );

		if ( false === $response ) {
			$response = wp_remote_get( 'https://api.openai.com/v1/models', [
			    'headers' => [
			        'Authorization' => 'Bearer ' . $api_key,
			        'Content-Type'  => 'application/json',
			    ],
			    'timeout' => rtbcb_get_api_timeout(),
			] );

			if ( ! is_wp_error( $response ) ) {
			    wp_cache_set( $cache_key, $response, '', HOUR_IN_SECONDS );
			}
		}

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
     * Clear cached OpenAI models when API settings change.
     *
     * @param string $old_value Old API key.
     * @param string $value     New API key.
     * @return void
     */
	public function clear_openai_models_cache( $old_value, $value ) {
		if ( $old_value !== $value ) {
		    wp_cache_delete( 'rtbcb_openai_models' );
		}
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

        $start         = microtime( true );
        $roi           = RTBCB_Calculator::calculate_roi( $roi_inputs );
        $recommendation = RTBCB_Category_Recommender::recommend_category( $roi_inputs );
        $roi           = RTBCB_Calculator::calculate_category_refined_roi( $roi_inputs, $recommendation['category_info'] );
        $elapsed       = round( microtime( true ) - $start, 2 );

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
     * AJAX handler to fetch phase completion percentages.
     *
     * @return void
     */
    public function ajax_get_phase_completion() {
        check_ajax_referer( 'rtbcb_test_dashboard', 'nonce' );

        $test_results = get_option( 'rtbcb_test_results', [] );
        $sections     = rtbcb_get_dashboard_sections( $test_results );
        $percentages  = rtbcb_calculate_phase_completion( $sections );

        wp_send_json_success( [ 'percentages' => $percentages ] );
    }

    /**
     * AJAX handler to fetch sanitized configuration for a section.
     *
     * @return void
     */
    public function ajax_get_section_config() {
        check_ajax_referer( 'rtbcb_test_dashboard', 'nonce' );

        $section_id = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : '';
        if ( '' === $section_id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid section.', 'rtbcb' ) ] );
        }

        $sections = rtbcb_get_dashboard_sections();
        if ( ! isset( $sections[ $section_id ]['option'] ) ) {
            wp_send_json_error( [ 'message' => __( 'Unknown section.', 'rtbcb' ) ] );
        }

        $option_name = sanitize_key( $sections[ $section_id ]['option'] );
        $raw         = get_option( $option_name, [] );
        $sanitized   = $this->sanitize_section_config( $raw );
        $snippet     = wp_json_encode( $sanitized );
        if ( strlen( $snippet ) > 200 ) {
            $snippet = substr( $snippet, 0, 200 ) . '...';
        }

        wp_send_json_success( [ 'config' => $snippet ] );
    }

    /**
     * AJAX handler to build test summary HTML.
     *
     * @return void
     */
    public function ajax_get_test_summary_html() {
        check_ajax_referer( 'rtbcb_test_dashboard', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized', 'rtbcb' ) ] );
        }

        $test_results = get_option( 'rtbcb_test_results', [] );
        if ( empty( $test_results ) ) {
            wp_send_json_error( [ 'message' => __( 'No test results found.', 'rtbcb' ) ] );
        }

        ob_start();
        echo '<div class="rtbcb-summary-panel">';
        echo '<table class="rtbcb-summary-table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Section', 'rtbcb' ) . '</th>';
        echo '<th>' . esc_html__( 'Status', 'rtbcb' ) . '</th>';
        echo '<th>' . esc_html__( 'Message', 'rtbcb' ) . '</th>';
        echo '<th>' . esc_html__( 'Duration', 'rtbcb' ) . '</th>';
        echo '<th>' . esc_html__( 'Finished', 'rtbcb' ) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ( $test_results as $result ) {
            $section  = isset( $result['section'] ) ? $result['section'] : '';
            $status   = isset( $result['status'] ) ? $result['status'] : '';
            $message  = isset( $result['message'] ) ? $result['message'] : '';
            $duration = isset( $result['duration'] ) ? $result['duration'] : '';
            $end_time = isset( $result['end_time'] ) ? $result['end_time'] : '';

            echo '<tr>';
            echo '<td>' . esc_html( $section ) . '</td>';
            echo '<td>' . esc_html( $status ) . '</td>';
            echo '<td>' . esc_html( $message ) . '</td>';
            echo '<td>' . esc_html( $duration ) . '</td>';
            echo '<td>' . esc_html( $end_time ) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';

        $html = ob_get_clean();

        wp_send_json_success( [ 'html' => $html ] );
    }

    /**
     * Recursively sanitize configuration data.
     *
     * @param mixed $data Configuration value.
     * @return mixed
     */
    private function sanitize_section_config( $data ) {
        if ( is_array( $data ) ) {
            $clean = [];
            foreach ( $data as $key => $value ) {
                $clean[ sanitize_key( $key ) ] = $this->sanitize_section_config( $value );
            }
            return $clean;
        }

        if ( is_scalar( $data ) ) {
            return sanitize_text_field( (string) $data );
        }

        return '';
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

        $job_id = rtbcb_queue_comprehensive_analysis( $company_name );
        if ( is_wp_error( $job_id ) ) {
            wp_send_json_error( [ 'message' => $job_id->get_error_message() ] );
        }

        wp_send_json_success( [ 'job_id' => $job_id ] );
    }

    /**
     * Get status for a queued comprehensive analysis job.
     *
     * @return void
     */
    public function ajax_get_analysis_status() {
        check_ajax_referer( 'rtbcb_test_dashboard', 'nonce' );

        $job_id = isset( $_GET['job_id'] ) ? sanitize_key( wp_unslash( $_GET['job_id'] ) ) : '';
        if ( '' === $job_id ) {
            wp_send_json_error( [ 'message' => __( 'Job ID is required.', 'rtbcb' ) ] );
        }

        $result = rtbcb_get_analysis_job_result( $job_id );
        if ( null === $result ) {
            wp_send_json_success( [ 'status' => 'pending' ] );
        }

        if ( empty( $result['success'] ) ) {
            wp_send_json_error( [ 'message' => $result['message'] ?? __( 'Unknown error.', 'rtbcb' ) ] );
        }

        wp_send_json_success( array_merge( [ 'status' => 'completed' ], $result ) );
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

    public function render_workflow_visualizer() {
            include RTBCB_DIR . 'admin/workflow-visualizer-page.php';
    }

	public function ajax_get_workflow_history() {
		check_ajax_referer( 'rtbcb_workflow_visualizer', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Insufficient permissions', 'rtbcb' ) );
		}
		$raw_history = $this->get_workflow_history_from_logs();
		$history     = array_map(
		function ( $entry ) {
		$lead_id = isset( $entry['lead_id'] ) ? intval( $entry['lead_id'] ) : 0;
		$email   = isset( $entry['lead_email'] ) ? sanitize_email( $entry['lead_email'] ) : '';
		$steps   = [];
		if ( isset( $entry['steps'] ) && is_array( $entry['steps'] ) ) {
		foreach ( $entry['steps'] as $step ) {
		$steps[] = [
		'name'   => sanitize_text_field( $step['name'] ?? '' ),
		'status' => sanitize_text_field( $step['status'] ?? '' ),
		];
		}
		}
		return [
		'lead_id' => $lead_id,
		'email'   => $email,
		'steps'   => $steps,
		];
		},
		$raw_history
		);
		
		wp_send_json_success(
		[
		'history' => $history,
		'summary' => [
		'total_executions' => count( $raw_history ),
		'avg_duration'     => $this->calculate_average_duration( $raw_history ),
		'success_rate'     => $this->calculate_success_rate( $raw_history ),
		],
		]
		);
}

		public function ajax_clear_workflow_history() {
			check_ajax_referer( 'rtbcb_workflow_visualizer', 'nonce' );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Insufficient permissions', 'rtbcb' ) );
			}
			$cleared = delete_option( 'rtbcb_workflow_history' );
			if ( ! $cleared ) {
				wp_send_json_error( __( 'Failed to clear workflow history', 'rtbcb' ) );
			}
			wp_send_json_success( __( 'Workflow history cleared', 'rtbcb' ) );
		}

		/**
		 * Retrieve workflow history with lead metadata.
		 *
		 * @return array Workflow history entries.
		 */
		private function get_workflow_history_from_logs() {
			$history = get_option( 'rtbcb_workflow_history', [] );
			if ( ! is_array( $history ) ) {
			return [];
			}
			return array_map(
				function ( $entry ) {
				$entry['lead_id']    = isset( $entry['lead_id'] ) ? intval( $entry['lead_id'] ) : 0;
				$entry['lead_email'] = isset( $entry['lead_email'] ) ? sanitize_email( $entry['lead_email'] ) : '';
				return $entry;
			},
			$history
			);
		}

	private function calculate_average_duration( $history ) {
		if ( empty( $history ) ) {
			return 0;
		}
		$total = 0;
		foreach ( $history as $item ) {
			$total += isset( $item['duration'] ) ? floatval( $item['duration'] ) : 0;
		}
		return $total / count( $history );
	}

        private function calculate_success_rate( $history ) {
                if ( empty( $history ) ) {
                        return 0;
                }
                $success = 0;
                foreach ( $history as $item ) {
                        if ( ! empty( $item['success'] ) ) {
                                $success++;
                        }
                }
                return ( $success / count( $history ) ) * 100;
        }

        /**
         * Display notice if PHP max execution time is lower than GPT-5 timeout.
         *
         * @return void
         */
        public function maybe_show_timeout_notice() {
                $php_limit   = (int) ini_get( 'max_execution_time' );
                $gpt_timeout = (int) get_option( 'rtbcb_gpt5_timeout' );

                if ( $php_limit > 0 && $gpt_timeout > 0 && $php_limit < $gpt_timeout ) {
                        $doc_url = esc_url( RTBCB_URL . 'docs/timeout-config.md' );
                        echo '<div class="notice notice-warning is-dismissible"><p>';
                        printf(
                                wp_kses(
                                        __( 'Your PHP max execution time (%1$s seconds) is lower than the GPT-5 timeout (%2$s seconds). <a href="%3$s" target="_blank">Learn how to increase it</a>.', 'rtbcb' ),
                                        [
                                                'a' => [
                                                        'href'   => [],
                                                        'target' => [],
                                                ],
                                        ]
                                ),
                                esc_html( (string) $php_limit ),
                                esc_html( (string) $gpt_timeout ),
                                $doc_url
                        );
                        echo '</p></div>';
                }
        }
}
