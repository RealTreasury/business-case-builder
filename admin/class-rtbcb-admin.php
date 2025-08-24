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
        add_action( 'wp_ajax_rtbcb_generate_report_preview', [ $this, 'ajax_generate_report_preview' ] );
        add_action( 'wp_ajax_rtbcb_sync_to_local', [ $this, 'sync_to_local' ] );
        add_action( 'wp_ajax_nopriv_rtbcb_sync_to_local', [ $this, 'sync_to_local' ] );
        add_action( 'wp_ajax_rtbcb_test_commentary', [ $this, 'ajax_test_commentary' ] );
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
                    'nonces'  => [
                        'dashboard' => wp_create_nonce( 'rtbcb_unified_test_dashboard' ),
                        'llm'       => wp_create_nonce( 'rtbcb_llm_testing' ),
                        'apiHealth' => wp_create_nonce( 'rtbcb_api_health_tests' ),
                        'reportPreview' => wp_create_nonce( 'rtbcb_generate_preview_report' ),
                        'dataHealth' => wp_create_nonce( 'rtbcb_data_health_checks' ),
                    ],
                    'strings' => [
                        'generating'     => __( 'Generating...', 'rtbcb' ),
                        'complete'       => __( 'Complete!', 'rtbcb' ),
                        'error'          => __( 'Error occurred', 'rtbcb' ),
                        'confirm_clear'  => __( 'Are you sure you want to clear all results?', 'rtbcb' ),
                        'running'        => __( 'Running...', 'rtbcb' ),
                        'retrieving'     => __( 'Retrieving...', 'rtbcb' ),
                        'notTested'      => __( 'Not tested', 'rtbcb' ),
                        'allOperational' => __( 'All systems operational', 'rtbcb' ),
                        'errorsDetected' => __( '%d errors detected', 'rtbcb' ),
                        'passed'         => __( 'Passed', 'rtbcb' ),
                        'failed'         => __( 'Failed', 'rtbcb' ),
                        'settings'       => __( 'Settings', 'rtbcb' ),
                        'noResults'      => __( 'No results found', 'rtbcb' ),
                        'indexRebuilt'   => __( 'Index rebuilt successfully.', 'rtbcb' ),
                        'rebuildFailed'  => __( 'Index rebuild failed.', 'rtbcb' ),
                        'noChecks'       => __( 'No checks run yet.', 'rtbcb' ),
                        'lastIndexed'    => __( 'Last indexed: %s', 'rtbcb' ),
                        'entries'        => __( 'Entries: %d', 'rtbcb' ),
                        'settingsSaved'  => __( 'Settings saved.', 'rtbcb' ),
                    ],
                    'models'  => [
                        'mini'     => get_option( 'rtbcb_mini_model', 'gpt-4o-mini' ),
                        'premium'  => get_option( 'rtbcb_premium_model', 'gpt-4o' ),
                        'advanced' => get_option( 'rtbcb_advanced_model', 'o1-preview' ),
                    ],
                    'apiHealth' => [
                        'lastResults' => get_option( 'rtbcb_last_api_test', [] ),
                    ],
                    'urls'     => [
                        'settings' => admin_url( 'admin.php?page=rtbcb-unified-tests#settings' ),
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
            __( 'Calculation Info', 'rtbcb' ),
            __( 'Calculation Info', 'rtbcb' ),
            'manage_options',
            'rtbcb-calculations',
            [ $this, 'render_calculation_info' ]
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
     * Render report preview page.
     *
     * @return void
     */
    public function render_report_preview() {
        include RTBCB_DIR . 'admin/report-preview-page.php';
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
     * Render unified test dashboard page.
     *
     * @return void
     */
    public function render_unified_test_dashboard() {
        include RTBCB_DIR . 'admin/unified-test-dashboard-page.php';
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
     */    /**
     * AJAX handler for treasury tech overview testing.
     *
     * @return void
     */    /**
     * AJAX handler for real treasury overview testing.
     *
     * @return void
     */    /**
     * AJAX handler to retrieve stored company data.
     *
     * @return void
     */    /**
     * AJAX handler for testing estimated benefits.
     *
     * @return void
     */    /**
     * AJAX handler to calculate ROI from sample inputs.
     *
     * @return void
     */    /**
     * AJAX handler to generate complete report.
     *
     * @return void
     */    /**
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
     * AJAX handler for ROI calculation testing.
     *
     * @return void
     */    /**
     * AJAX handler for sensitivity analysis.
     *
     * @return void
     */    /**
     * Convert form data to calculator input format.
     *
     * @param array $form_data Form data from frontend.
     * @return array Calculator inputs.
     */
    private function convert_form_data_to_calculator_inputs( $form_data ) {
        $inputs = [
            // Company information.
            'company_size'         => isset( $form_data['roi-company-size'] ) ? sanitize_text_field( $form_data['roi-company-size'] ) : 'medium',
            'industry'             => isset( $form_data['roi-industry'] ) ? sanitize_text_field( $form_data['roi-industry'] ) : 'manufacturing',
            'annual_revenue'       => floatval( $form_data['roi-annual-revenue'] ?? 0 ),

            // Treasury operations.
            'treasury_staff_count' => intval( $form_data['roi-treasury-staff'] ?? 0 ),
            'average_salary'       => floatval( $form_data['roi-avg-salary'] ?? 0 ),
            'hours_reconciliation' => floatval( $form_data['roi-hours-reconciliation'] ?? 0 ),
            'hours_reporting'      => floatval( $form_data['roi-hours-reporting'] ?? 0 ),
            'hours_analysis'       => floatval( $form_data['roi-hours-analysis'] ?? 0 ),

            // Banking and fees.
            'num_banks'            => intval( $form_data['roi-num-banks'] ?? 0 ),
            'monthly_bank_fees'    => floatval( $form_data['roi-monthly-bank-fees'] ?? 0 ),
            'wire_transfer_volume' => intval( $form_data['roi-wire-transfer-volume'] ?? 0 ),
            'avg_wire_fee'         => floatval( $form_data['roi-avg-wire-fee'] ?? 0 ),

            // Risk and efficiency.
            'error_frequency'      => intval( $form_data['roi-error-frequency'] ?? 0 ),
            'avg_error_cost'       => floatval( $form_data['roi-avg-error-cost'] ?? 0 ),
            'compliance_hours'     => floatval( $form_data['roi-compliance-hours'] ?? 0 ),
            'system_integration'   => isset( $form_data['roi-system-integration'] ) ? sanitize_text_field( $form_data['roi-system-integration'] ) : 'partial',

            // Calculated fields.
            'total_daily_hours'    => floatval( $form_data['roi-hours-reconciliation'] ?? 0 ) +
                floatval( $form_data['roi-hours-reporting'] ?? 0 ) +
                floatval( $form_data['roi-hours-analysis'] ?? 0 ),
            'annual_wire_fees'     => floatval( $form_data['roi-wire-transfer-volume'] ?? 0 ) *
                floatval( $form_data['roi-avg-wire-fee'] ?? 0 ) * 12,
            'annual_error_cost'    => floatval( $form_data['roi-error-frequency'] ?? 0 ) *
                floatval( $form_data['roi-avg-error-cost'] ?? 0 ) * 52,
        ];

        return $inputs;
    }

    /**
     * Validate ROI calculation inputs.
     *
     * @param array $inputs Calculator inputs.
     * @return true|WP_Error True if valid, WP_Error if invalid.
     */
    private function validate_roi_inputs( $inputs ) {
        $required_fields = [
            'annual_revenue'       => __( 'Annual revenue is required', 'rtbcb' ),
            'treasury_staff_count' => __( 'Treasury staff count is required', 'rtbcb' ),
            'average_salary'       => __( 'Average salary is required', 'rtbcb' ),
            'hours_reconciliation' => __( 'Reconciliation hours are required', 'rtbcb' ),
        ];

        foreach ( $required_fields as $field => $message ) {
            if ( ! isset( $inputs[ $field ] ) || $inputs[ $field ] <= 0 ) {
                return new WP_Error( 'invalid_input', $message );
            }
        }

        // Validate reasonable ranges.
        if ( $inputs['annual_revenue'] > 100000000000 ) { // $100B limit.
            return new WP_Error( 'invalid_input', __( 'Annual revenue seems unreasonably high', 'rtbcb' ) );
        }

        if ( $inputs['treasury_staff_count'] > 1000 ) {
            return new WP_Error( 'invalid_input', __( 'Treasury staff count seems unreasonably high', 'rtbcb' ) );
        }

        if ( $inputs['total_daily_hours'] > 24 ) {
            return new WP_Error( 'invalid_input', __( 'Total daily hours cannot exceed 24', 'rtbcb' ) );
        }

        return true;
    }

    /**
     * Adjust inputs for different scenarios (conservative, realistic, optimistic).
     *
     * @param array  $base_inputs Base calculator inputs.
     * @param string $scenario    Scenario name.
     * @return array Adjusted inputs.
     */
    private function adjust_inputs_for_scenario( $base_inputs, $scenario ) {
        $adjustments = [
            'conservative' => [
                'efficiency_improvement'  => 0.15,
                'error_reduction'         => 0.30,
                'fee_optimization'        => 0.10,
                'time_savings_multiplier' => 0.8,
            ],
            'realistic'    => [
                'efficiency_improvement'  => 0.25,
                'error_reduction'         => 0.50,
                'fee_optimization'        => 0.15,
                'time_savings_multiplier' => 1.0,
            ],
            'optimistic'   => [
                'efficiency_improvement'  => 0.40,
                'error_reduction'         => 0.70,
                'fee_optimization'        => 0.25,
                'time_savings_multiplier' => 1.2,
            ],
        ];

        $scenario_adjustments = $adjustments[ $scenario ] ?? $adjustments['realistic'];

        return array_merge( $base_inputs, $scenario_adjustments );
    }

    /**
     * Format ROI calculation result for frontend consumption.
     *
     * @param array  $roi_result Raw ROI calculation result.
     * @param string $scenario   Scenario name.
     * @return array Formatted result.
     */
    private function format_roi_result( $roi_result, $scenario ) {
        return [
            'scenario'       => $scenario,
            'roi_percentage' => round( $roi_result['roi_percentage'] ?? 0, 1 ),
            'annual_benefit' => round( $roi_result['annual_benefit'] ?? 0, 0 ),
            'annual_cost'    => round( $roi_result['annual_cost'] ?? 0, 0 ),
            'net_benefit'    => round( ( $roi_result['annual_benefit'] ?? 0 ) - ( $roi_result['annual_cost'] ?? 0 ), 0 ),
            'payback_months' => round( $roi_result['payback_months'] ?? 0, 1 ),
            'npv_3_years'    => round( $roi_result['npv_3_years'] ?? 0, 0 ),
            'irr'            => round( $roi_result['irr'] ?? 0, 1 ),
        ];
    }

    /**
     * Calculate detailed cost-benefit breakdown.
     *
     * @param array $inputs Calculator inputs.
     * @return array Detailed breakdown.
     */
    private function calculate_detailed_breakdown( $inputs ) {
        // Calculate annual labor cost.
        $annual_labor_cost = $inputs['treasury_staff_count'] * $inputs['average_salary'];
        $daily_labor_cost  = $annual_labor_cost / 365;
        $hourly_labor_cost = $daily_labor_cost / 8; // Assume 8-hour workday.

        // Benefits calculations.
        $labor_savings    = $inputs['total_daily_hours'] * $hourly_labor_cost * 365 * 0.25; // 25% time savings.
        $fee_savings      = ( $inputs['monthly_bank_fees'] * 12 + $inputs['annual_wire_fees'] ) * 0.15; // 15% fee reduction.
        $error_reduction  = $inputs['annual_error_cost'] * 0.50; // 50% error reduction.
        $efficiency_gains = $annual_labor_cost * 0.05; // 5% overall efficiency gain.

        // Costs calculations (example pricing).
        $company_size_multiplier = $this->get_company_size_multiplier( $inputs['company_size'] );
        $software_cost           = 50000 * $company_size_multiplier; // Base software cost.
        $implementation_cost     = $software_cost * 0.5; // 50% of software cost for implementation.
        $training_cost           = $inputs['treasury_staff_count'] * 2000; // $2k per person for training.
        $maintenance_cost        = $software_cost * 0.20; // 20% of software cost annually.

        return [
            'labor_savings'      => round( $labor_savings, 0 ),
            'fee_savings'        => round( $fee_savings, 0 ),
            'error_reduction'    => round( $error_reduction, 0 ),
            'efficiency_gains'   => round( $efficiency_gains, 0 ),
            'total_benefits'     => round( $labor_savings + $fee_savings + $error_reduction + $efficiency_gains, 0 ),
            'software_cost'      => round( $software_cost, 0 ),
            'implementation_cost'=> round( $implementation_cost, 0 ),
            'training_cost'      => round( $training_cost, 0 ),
            'maintenance_cost'   => round( $maintenance_cost, 0 ),
            'total_costs'        => round( $software_cost + $implementation_cost + $training_cost + $maintenance_cost, 0 ),
        ];
    }

    /**
     * Generate ROI assumptions based on inputs.
     *
     * @param array $inputs Calculator inputs.
     * @return array List of assumptions.
     */
    private function generate_roi_assumptions( $inputs ) {
        $assumptions = [
            sprintf(
                __( 'Treasury staff will save %d hours per day through automation', 'rtbcb' ),
                round( $inputs['total_daily_hours'] * 0.25, 1 )
            ),
            sprintf(
                __( 'Banking fees will be reduced by 15%% through optimization (current: %s)', 'rtbcb' ),
                '$' . number_format( $inputs['monthly_bank_fees'] * 12 )
            ),
            sprintf(
                __( 'Error frequency will be reduced by 50%% (current: %d per week)', 'rtbcb' ),
                $inputs['error_frequency']
            ),
            __( 'Implementation will be completed within 6 months', 'rtbcb' ),
            __( 'Staff utilization efficiency will improve by 5%', 'rtbcb' ),
        ];

        switch ( $inputs['industry'] ) {
            case 'manufacturing':
                $assumptions[] = __( 'Working capital optimization will improve cash flow by 2%', 'rtbcb' );
                break;
            case 'technology':
                $assumptions[] = __( 'Rapid scaling will require automated treasury processes', 'rtbcb' );
                break;
            case 'financial-services':
                $assumptions[] = __( 'Regulatory compliance will be streamlined through automation', 'rtbcb' );
                break;
        }

        return $assumptions;
    }

    /**
     * Perform sensitivity analysis on key variables.
     *
     * @param array $base_inputs Base calculator inputs.
     * @param array $base_roi    Base ROI results.
     * @return array Sensitivity analysis results.
     */
    private function perform_sensitivity_analysis( $base_inputs, $base_roi ) {
        $sensitive_variables = [
            'treasury_staff_count' => 'Treasury Staff Count',
            'average_salary'       => 'Average Salary',
            'hours_reconciliation' => 'Reconciliation Hours',
            'monthly_bank_fees'    => 'Monthly Bank Fees',
            'error_frequency'      => 'Error Frequency',
            'avg_error_cost'       => 'Average Error Cost',
        ];

        $base_roi_percentage = $base_roi['realistic']['roi_percentage'] ?? 0;
        $variations          = [ -0.2, -0.1, 0.1, 0.2 ];

        $sensitivity_results = [
            'base_roi'   => $base_roi_percentage,
            'sensitivity'=> [],
        ];

        foreach ( $sensitive_variables as $variable => $label ) {
            $base_value       = $base_inputs[ $variable ];
            $variable_results = [
                'base_value' => $base_value,
                'base'       => $base_roi_percentage,
                'sensitivity'=> 0,
            ];

            $roi_changes = [];

            foreach ( $variations as $variation ) {
                $modified_inputs               = $base_inputs;
                $modified_inputs[ $variable ] = $base_value * ( 1 + $variation );

                // Quick ROI calculation for sensitivity.
                $quick_roi     = $this->quick_roi_calculation( $modified_inputs );
                $variation_key = ( $variation > 0 ? '+' : '' ) . ( $variation * 100 ) . '%';
                $variable_results[ $variation_key ] = round( $quick_roi, 1 );
                $roi_changes[] = abs( $quick_roi - $base_roi_percentage ) / abs( $variation * 100 );
            }

            // Calculate sensitivity (average change in ROI per 1% change in variable).
            $variable_results['sensitivity'] = round( array_sum( $roi_changes ) / count( $roi_changes ), 2 );

            $sensitivity_results['sensitivity'][ $variable ] = $variable_results;
        }

        return $sensitivity_results;
    }

    /**
     * Quick ROI calculation for sensitivity analysis.
     *
     * @param array $inputs Modified inputs.
     * @return float ROI percentage.
     */
    private function quick_roi_calculation( $inputs ) {
        // Simplified ROI calculation for speed.
        $annual_labor_cost = $inputs['treasury_staff_count'] * $inputs['average_salary'];
        $hourly_labor_cost = $annual_labor_cost / ( 365 * 8 );

        $benefits = $inputs['total_daily_hours'] * $hourly_labor_cost * 365 * 0.25 +
            ( $inputs['monthly_bank_fees'] * 12 ) * 0.15 +
            $inputs['error_frequency'] * $inputs['avg_error_cost'] * 52 * 0.50;

        $costs = 50000 * $this->get_company_size_multiplier( $inputs['company_size'] ) * 1.9; // Total annual cost.

        return $costs > 0 ? ( $benefits / $costs ) * 100 : 0;
    }

    /**
     * Get company size multiplier for pricing.
     *
     * @param string $company_size Company size category.
     * @return float Multiplier.
     */
    private function get_company_size_multiplier( $company_size ) {
        $multipliers = [
            'startup'    => 0.5,
            'small'      => 0.8,
            'medium'     => 1.0,
            'large'      => 1.5,
            'enterprise' => 2.5,
        ];

        return $multipliers[ $company_size ] ?? 1.0;
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

}
